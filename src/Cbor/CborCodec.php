<?php

declare(strict_types=1);

namespace Smithy\Cbor;

use Smithy\Core\Serde\AbstractCodec;
use Smithy\Core\Serde\ShapeDeserializerInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;

/**
 * Create CBOR serializers and deserializers for Smithy shapes.
 */
final class CborCodec extends AbstractCodec
{
    /** @var list<CborSerializer> Reusable internal serializers. */
    private array $serializerPool = [];

    /**
     * Create a CBOR serializer.
     */
    public function createSerializer(): ShapeSerializerInterface
    {
        return new CborSerializer();
    }

    /**
     * Create a CBOR deserializer.
     */
    public function createDeserializer(string $source): ShapeDeserializerInterface
    {
        return new CborDeserializer($source);
    }

    /**
     * Borrow a reusable serializer.
     *
     * @internal
     */
    public function acquireSerializer(): CborSerializer
    {
        $serializer = \array_pop($this->serializerPool)
            ?? new CborSerializer();
        $serializer->reset();

        return $serializer;
    }

    /**
     * Return a borrowed serializer to the pool.
     *
     * @internal
     *
     * @return void
     */
    public function releaseSerializer(CborSerializer $serializer): void
    {
        $serializer->reset();
        $this->serializerPool[] = $serializer;
    }
}
