<?php

declare(strict_types=1);

namespace Smithy\Http\Client;

use Smithy\Async\CancellationInterface;
use Smithy\Async\CancelledException;
use Smithy\Http\Curl\CurlExchange;
use Smithy\Http\Curl\CurlResponseState;
use Psr\Http\Message\RequestInterface;

/**
 * Track a pending asynchronous HTTP request.
 *
 * @internal
 */
final class PendingRequest
{
    public ?int $cancellationSubscription = null;
    public ?CancelledException $cancellationReason = null;
    public int $downloadedBytes = 0;
    public int $uploadedBytes = 0;
    public readonly float $startedAt;
    private ?\Closure $resume;
    private ?\Closure $throw;

    /**
     * Create pending request state.
     */
    public function __construct(
        /** The Exchange (and its underlying cURL handle) for this request. */
        public readonly CurlExchange $exchange,

        /** The original PSR-7 request (for error reporting). */
        public readonly RequestInterface $request,

        /** Callback to resume the suspended fiber with the result. */
        \Closure $resume,

        /** Callback to resume the suspended fiber by throwing. */
        \Closure $throw,

        /** Transport options applied to this request. */
        public readonly HttpRequestOptions $options,

        /** Response state shared with cURL callbacks. */
        public readonly CurlResponseState $responseState,

        /** Request body size captured before the transfer starts. */
        public readonly ?int $requestBodySize,

        /** Upload state for streamed request bodies, if any. */
        public readonly ?UploadState $uploadState = null,

        /** Signal used to cancel this request. */
        public readonly ?CancellationInterface $cancellation = null
    )
    {
        $this->startedAt = \microtime(true);
        $this->resume = $resume;
        $this->throw = $throw;
    }

    /**
     * Resume the suspended request and release its continuations.
     *
     * @return void
     */
    public function resumeWith(mixed $value): void
    {
        $resume = $this->resume;
        $this->resume = null;
        $this->throw = null;
        $resume?->__invoke($value);
    }

    /**
     * Fail the suspended request and release its continuations.
     *
     * @return void
     */
    public function resumeThrowing(\Throwable $error): void
    {
        $throw = $this->throw;
        $this->resume = null;
        $this->throw = null;
        $throw?->__invoke($error);
    }

    /**
     * Get the raw cURL handle.
     */
    public function getHandle(): \CurlHandle
    {
        return $this->exchange->getHandle();
    }
}
