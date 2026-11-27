<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Interceptor;

use Psr\Log\LoggerInterface;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse
};

/**
 * Write PSR-3 records for client calls and their attempts.
 *
 * The interceptor uses read hooks only. It records:
 *
 *  - `debug` when a call starts, when the first attempt starts, and when
 *    each attempt ends
 *  - `warning` when a retry attempt starts, with the error that caused it
 *  - `info` when a call succeeds
 *  - `error` when a call fails
 *
 * Messages use PSR-3 `{placeholder}` syntax. The context array carries the
 * values: `service`, `operation`, `attempt`, `attempts`, `durationMs`,
 * `method`, `url`, `statusCode`, `errorType`, `errorMessage`, and
 * `exception`. The URL has no query string, fragment, or user info. Message
 * bodies are never logged.
 *
 * Header logging is off by default. When on, request and response headers
 * are added as `requestHeaders` and `responseHeaders`, and the values of
 * {@see DEFAULT_REDACTED_HEADERS} and any extra redacted names are replaced
 * with {@see REDACTED}.
 *
 * {@see LogContextProviderInterface} instances add fields to every record.
 *
 * One instance can serve concurrent calls: per-call timing and attempt
 * counts are kept per fiber.
 */
final class LoggingInterceptor extends AbstractInterceptor
{
    public const MESSAGE_CALL_STARTED = 'Starting {operation} call to {service}';

    public const MESSAGE_ATTEMPT_STARTED = 'Starting {operation} attempt {attempt}';

    public const MESSAGE_ATTEMPT_RETRYING =
        'Retrying {operation} as attempt {attempt} after {errorType}: {errorMessage}';

    public const MESSAGE_ATTEMPT_SUCCEEDED =
        'Attempt {attempt} of {operation} succeeded in {durationMs} ms';

    public const MESSAGE_ATTEMPT_FAILED =
        'Attempt {attempt} of {operation} failed in {durationMs} ms with '
        . '{errorType}: {errorMessage}';

    public const MESSAGE_CALL_SUCCEEDED =
        '{operation} call to {service} succeeded after {attempts} attempt(s) '
        . 'in {durationMs} ms';

    public const MESSAGE_CALL_FAILED =
        '{operation} call to {service} failed after {attempts} attempt(s) '
        . 'in {durationMs} ms with {errorType}: {errorMessage}';

    /** Replacement for redacted header values. */
    public const REDACTED = '[REDACTED]';

    /** Header names whose values are always redacted. */
    public const DEFAULT_REDACTED_HEADERS = [
        'authorization',
        'proxy-authorization',
        'cookie',
        'set-cookie'
    ];

    /** @var array<string, true> Lower-case header names to redact. */
    private readonly array $redactedHeaders;

    /**
     * Per-fiber stacks of active call state. A stack lets a call made from
     * inside another call's hook keep its own state.
     *
     * @var \WeakMap<object, list<array{
     *     start: float,
     *     attempt: int,
     *     attemptStart: float,
     *     lastError: ?\Throwable
     * }>>
     */
    private \WeakMap $calls;

    /** Stands in for the fiber when code runs outside any fiber. */
    private readonly object $mainKey;

    /**
     * @param LoggerInterface $logger Receives every record.
     * @param list<LogContextProviderInterface> $contextProviders Add fields
     *        to every record.
     * @param list<string> $redactedHeaders Header names redacted in addition
     *        to {@see DEFAULT_REDACTED_HEADERS}. Matched case-insensitively.
     * @param bool $logHeaders Add redacted request and response headers to
     *        attempt records.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $contextProviders = [],
        array $redactedHeaders = [],
        private readonly bool $logHeaders = false
    ) {
        $redacted = [];
        foreach ([...self::DEFAULT_REDACTED_HEADERS, ...$redactedHeaders] as $name) {
            $redacted[\strtolower($name)] = true;
        }
        $this->redactedHeaders = $redacted;
        $this->calls = new \WeakMap();
        $this->mainKey = new \stdClass();
    }

    /** @inheritDoc */
    public function readBeforeExecution(InputContext $ctx): void
    {
        $now = self::now();
        $this->pushCall([
            'start' => $now,
            'attempt' => 0,
            'attemptStart' => $now,
            'lastError' => null
        ]);

        $this->logger->debug(
            self::MESSAGE_CALL_STARTED,
            $this->context($ctx, null, self::callContext($ctx))
        );
    }

    /** @inheritDoc */
    public function readBeforeAttempt(RequestContext $ctx): void
    {
        $state = $this->popCall();
        ++$state['attempt'];
        $state['attemptStart'] = self::now();
        $this->pushCall($state);

        $fields = [
            ...self::callContext($ctx),
            'attempt' => $state['attempt']
        ];
        $retryError = $state['attempt'] > 1 ? $state['lastError'] : null;
        if ($retryError === null) {
            $this->logger->debug(
                self::MESSAGE_ATTEMPT_STARTED,
                $this->context($ctx, null, $fields)
            );

            return;
        }

        $this->logger->warning(
            self::MESSAGE_ATTEMPT_RETRYING,
            $this->context(
                $ctx,
                $retryError,
                [...$fields, ...self::errorContext($retryError)]
            )
        );
    }

    /** @inheritDoc */
    public function readAfterAttempt(
        OutputContext $ctx,
        ?\Throwable $error
    ): void
    {
        $state = $this->popCall();
        $state['lastError'] = $error;
        $this->pushCall($state);

        $fields = [
            ...self::callContext($ctx),
            'attempt' => $state['attempt'],
            'durationMs' => self::elapsedMs($state['attemptStart']),
            ...$this->messageContext($ctx)
        ];
        if ($error === null) {
            $this->logger->debug(
                self::MESSAGE_ATTEMPT_SUCCEEDED,
                $this->context($ctx, null, $fields)
            );

            return;
        }

        $this->logger->debug(
            self::MESSAGE_ATTEMPT_FAILED,
            $this->context(
                $ctx,
                $error,
                [...$fields, ...self::errorContext($error)]
            )
        );
    }

    /** @inheritDoc */
    public function readAfterExecution(
        OutputContext $ctx,
        ?\Throwable $error
    ): void
    {
        $state = $this->popCall();

        $fields = [
            ...self::callContext($ctx),
            'attempts' => $state['attempt'],
            'durationMs' => self::elapsedMs($state['start'])
        ];
        if ($error === null) {
            $this->logger->info(
                self::MESSAGE_CALL_SUCCEEDED,
                $this->context($ctx, null, $fields)
            );

            return;
        }

        $this->logger->error(
            self::MESSAGE_CALL_FAILED,
            $this->context(
                $ctx,
                $error,
                [
                    ...$fields,
                    ...self::errorContext($error),
                    'exception' => $error
                ]
            )
        );
    }

    /**
     * Merge provider fields under the interceptor's own fields.
     *
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function context(
        InputContext|RequestContext|OutputContext $ctx,
        ?\Throwable $error,
        array $fields
    ): array
    {
        foreach ($this->contextProviders as $provider) {
            $fields = [...$provider->getLogContext($ctx, $error), ...$fields];
        }

        return $fields;
    }

    /**
     * Return the HTTP method, URL, status code, and optional headers of an
     * attempt. Other transports contribute no fields.
     *
     * @return array<string, mixed>
     */
    private function messageContext(OutputContext $ctx): array
    {
        $fields = [];
        $request = $ctx->request;
        if ($request instanceof HttpTransportRequest) {
            $uri = $request->request->getUri()
                ->withQuery('')
                ->withFragment('')
                ->withUserInfo('');
            $fields['method'] = $request->request->getMethod();
            $fields['url'] = (string) $uri;
            if ($this->logHeaders) {
                $fields['requestHeaders'] = $this->redact(
                    $request->request->getHeaders()
                );
            }
        }

        $response = $ctx->response;
        if ($response instanceof HttpTransportResponse) {
            $fields['statusCode'] = $response->response->getStatusCode();
            if ($this->logHeaders) {
                $fields['responseHeaders'] = $this->redact(
                    $response->response->getHeaders()
                );
            }
        }

        return $fields;
    }

    /**
     * @param array<string, array<string>> $headers
     *
     * @return array<string, array<string>>
     */
    private function redact(array $headers): array
    {
        foreach ($headers as $name => $values) {
            if (isset($this->redactedHeaders[\strtolower((string) $name)])) {
                $headers[$name] = \array_fill(0, \count($values), self::REDACTED);
            }
        }

        return $headers;
    }

    /**
     * @param array{
     *     start: float,
     *     attempt: int,
     *     attemptStart: float,
     *     lastError: ?\Throwable
     * } $state
     */
    private function pushCall(array $state): void
    {
        $key = \Fiber::getCurrent() ?? $this->mainKey;
        $stack = $this->calls[$key] ?? [];
        $stack[] = $state;
        $this->calls[$key] = $stack;
    }

    /**
     * Remove and return the current call's state. A hook that runs without
     * a recorded call start gets fresh state.
     *
     * @return array{
     *     start: float,
     *     attempt: int,
     *     attemptStart: float,
     *     lastError: ?\Throwable
     * }
     */
    private function popCall(): array
    {
        $key = \Fiber::getCurrent() ?? $this->mainKey;
        $stack = $this->calls[$key] ?? [];
        $state = \array_pop($stack);
        if ($stack === []) {
            unset($this->calls[$key]);
        } else {
            $this->calls[$key] = $stack;
        }

        $now = self::now();

        return $state ?? [
            'start' => $now,
            'attempt' => 0,
            'attemptStart' => $now,
            'lastError' => null
        ];
    }

    /**
     * @return array{service: string, operation: string}
     */
    private static function callContext(
        InputContext|RequestContext|OutputContext $ctx
    ): array
    {
        return [
            'service' => self::serviceId($ctx->operation, $ctx),
            'operation' => $ctx->operation->getName()
        ];
    }

    private static function serviceId(
        OperationInterface $operation,
        InputContext|RequestContext|OutputContext $ctx
    ): string
    {
        return $operation->getServiceId()
            ?? $ctx->config->service?->getId()
            ?? 'unknown';
    }

    /**
     * @return array{errorType: string, errorMessage: string}
     */
    private static function errorContext(\Throwable $error): array
    {
        return [
            'errorType' => $error instanceof SerializableStructInterface
                ? $error->getSchema()->id
                : $error::class,
            'errorMessage' => $error->getMessage()
        ];
    }

    private static function now(): float
    {
        return (float) \hrtime(true);
    }

    private static function elapsedMs(float $start): float
    {
        return \round((self::now() - $start) / 1e6, 3);
    }
}
