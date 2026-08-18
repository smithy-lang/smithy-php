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
    private readonly JsonCodecSettings $settings;

    public function __construct(?JsonCodecSettings $settings = null)
    {
        $this->settings = $settings ?? new JsonCodecSettings();
    }

    /**
     * Create a JSON serializer.
     */
    public function createSerializer(): ShapeSerializerInterface
    {
        return new JsonSerializer($this->settings);
    }

    /**
     * Create a JSON deserializer.
     */
    public function createDeserializer(string $source): ShapeDeserializerInterface
    {
        return new JsonDeserializer($source, $this->settings);
    }

    /**
     * Borrow a reusable serializer.
     *
     * @internal
     */
    public function acquireSerializer(): JsonSerializer
    {
        $serializer = \array_pop($this->serializerPool)
            ?? new JsonSerializer($this->settings);
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
