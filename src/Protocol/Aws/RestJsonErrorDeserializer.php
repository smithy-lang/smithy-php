<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Protocol\Aws;

use Psr\Http\Message\ResponseInterface;
use Smithy\Core\Serde\Schema;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Protocol\HttpBindingDeserializer;
use Smithy\Protocol\HttpStructBindingExtension;
use Smithy\Protocol\HttpStructBindingExtensionProvider;

/**
 * Deserialize Rest JSON errors with HTTP-bound members.
 */
final class RestJsonErrorDeserializer extends AbstractAwsErrorDeserializer
{
    /**
     * @return array{
     *     discriminator: ?string,
     *     message: string,
     *     body: string,
     *     requestId: ?string
     * }
     */
    protected function parseError(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $document = [];
        if ($body !== '') {
            $decoded = \json_decode(
                $body,
                true,
                512,
                \JSON_THROW_ON_ERROR
            );
            if (\is_array($decoded)) {
                $document = $decoded;
            }
        }

        // Rest JSON prefers the header discriminator over body fields.
        $rawType = $response->getHeaderLine('x-amzn-errortype');
        if ($rawType === '') {
            $candidate = $document['__type'] ?? $document['code'] ?? null;
            $rawType = \is_string($candidate) ? $candidate : 'Unknown';
        }

        $requestId = $response->getHeaderLine('x-amzn-requestid');
        if ($requestId === '') {
            $requestId = $response->getHeaderLine('x-amz-request-id');
        }

        return [
            'discriminator' => self::normalizeErrorType($rawType),
            'message' => (string) (
                $document['message']
                ?? $document['Message']
                ?? ''
            ),
            'body' => $body,
            'requestId' => $requestId !== '' ? $requestId : null
        ];
    }

    /**
     * @param array<int, mixed> $members Positional member values.
     */
    protected function deserializeMembers(
        Schema $schema,
        array &$members,
        ResponseInterface $response,
        string $body
    ): void
    {
        static $bindingKey = null;
        $bindingKey ??= HttpStructBindingExtensionProvider::$KEY
            ?? HttpStructBindingExtensionProvider::key();

        /** @var HttpStructBindingExtension|null $bindingInfo */
        $bindingInfo = $schema->getExtension($bindingKey);
        if ($bindingInfo === null) {
            parent::deserializeMembers(
                $schema,
                $members,
                $response,
                $body
            );

            return;
        }

        $deserializer = new HttpBindingDeserializer(
            $this->codec,
            $bindingInfo,
            self::normalizeHeaders($response),
            new StringRequestStream($body),
            $response->getStatusCode()
        );
        $deserializer->deserialize($schema, $members);
    }

    private static function normalizeErrorType(string $raw): string
    {
        $colonPos = \strpos($raw, ':');
        if ($colonPos !== false) {
            $raw = \substr($raw, 0, $colonPos);
        }

        $hashPos = \strrpos($raw, '#');
        if ($hashPos !== false) {
            $raw = \substr($raw, $hashPos + 1);
        }

        return $raw;
    }
}
