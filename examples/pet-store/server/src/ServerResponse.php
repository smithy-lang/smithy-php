<?php

declare(strict_types=1);

namespace Smithy\Examples\PetStore\Server;

/**
 * HTTP response emitted by the local PetStore server.
 */
final class ServerResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly string $body
    ) {}
}
