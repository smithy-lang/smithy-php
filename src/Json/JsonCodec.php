<?php

declare(strict_types=1);

namespace Smithy\Json;

use Smithy\Core\Serde\AbstractCodec;
use Smithy\Core\Serde\ShapeDeserializerInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;

/**
 * Provide JSON serialization and deserialization for Smithy shapes.
 */
final class JsonCodec extends AbstractCodec
{
    /** @var list<JsonSerializer> Reusable internal serializers. */
    private array $serializerPool = [];

    public function __construct(
        /**
         * Honor `smithy.api#jsonName` when encoding and decoding member
         * names.
         */
        private readonly bool $honorJsonName = true
    ) {}

    /**
     * Create a JSON serializer.
     */
    public function createSerializer(): ShapeSerializerInterface
    {
        return new JsonSerializer($this->honorJsonName);
    }

    /**
     * Create a JSON deserializer.
     */
    public function createDeserializer(string $source): ShapeDeserializerInterface
    {
        return new JsonDeserializer($source, $this->honorJsonName);
    }

    /**
     * Borrow a reusable serializer.
     *
     * @internal
     */
    public function acquireSerializer(): JsonSerializer
    {
        $serializer = \array_pop($this->serializerPool)
            ?? new JsonSerializer($this->honorJsonName);
        $serializer->reset();

        return $serializer;
    }

    /**
     * Return a borrowed serializer to the pool.
     *
     * @internal
     */
    public function releaseSerializer(JsonSerializer $serializer): void
    {
        $serializer->reset();
        $this->serializerPool[] = $serializer;
    }
}
