<?php

declare(strict_types=1);

namespace Smithy\Examples\PetStore\Server;

/**
 * Modeled service error returned by the local example server.
 */
final class ServiceError extends \RuntimeException
{
    /**
     * @param array<string, string> $fields
     */
    public function __construct(
        public readonly string $shapeId,
        public readonly int $statusCode,
        string $message,
        public readonly array $fields = []
    ) {
        parent::__construct($message);
    }
}
