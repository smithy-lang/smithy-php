<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\ProtocolTests\RestJson\Exception\ErrorEventException;
use Smithy\ProtocolTests\RestJson\Schemas;

abstract readonly class EventStream implements UnionInterface
{
    public const SHAPE_ID = 'aws.protocoltests.restjson#EventStream';

    abstract public function getMemberIndex(): int;

    abstract public function tag(): string;

    abstract public function value(): mixed;

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeUnion(self::schema(), $this, $idx);
    }

    public static function headers(HeadersEvent $value): self
    {
        return new EventStreamHeaders($value);
    }

    public static function blobPayload(BlobPayloadEvent $value): self
    {
        return new EventStreamBlobPayload($value);
    }

    public static function stringPayload(StringPayloadEvent $value): self
    {
        return new EventStreamStringPayload($value);
    }

    public static function structurePayload(StructurePayloadEvent $value): self
    {
        return new EventStreamStructurePayload($value);
    }

    public static function unionPayload(UnionPayloadEvent $value): self
    {
        return new EventStreamUnionPayload($value);
    }

    public static function headersAndExplicitPayload(HeadersAndExplicitPayloadEvent $value): self
    {
        return new EventStreamHeadersAndExplicitPayload($value);
    }

    public static function headersAndImplicitPayload(HeadersAndImplicitPayloadEvent $value): self
    {
        return new EventStreamHeadersAndImplicitPayload($value);
    }

    public static function error(ErrorEventException $value): self
    {
        return new EventStreamError($value);
    }

    /**
     * Create an unknown variant for forward-compatibility.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new EventStreamUnknown($tag, $value);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::eventStream();
    }

    /**
     * Get the shape schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }


    /**
     * Create a union variant from a tag and value.
     *
     * @param string $tag The variant name
     * @param mixed $value The variant value
     * @return self
     */
    public static function create(string $tag, mixed $value): self
    {
        return match ($tag) {
            'headers' => new EventStreamHeaders($value instanceof HeadersEvent ? $value : ArrayShapeSerializer::fromArray(HeadersEvent::class, $value)),
            'blobPayload' => new EventStreamBlobPayload($value instanceof BlobPayloadEvent ? $value : ArrayShapeSerializer::fromArray(BlobPayloadEvent::class, $value)),
            'stringPayload' => new EventStreamStringPayload($value instanceof StringPayloadEvent ? $value : ArrayShapeSerializer::fromArray(StringPayloadEvent::class, $value)),
            'structurePayload' => new EventStreamStructurePayload($value instanceof StructurePayloadEvent ? $value : ArrayShapeSerializer::fromArray(StructurePayloadEvent::class, $value)),
            'unionPayload' => new EventStreamUnionPayload($value instanceof UnionPayloadEvent ? $value : ArrayShapeSerializer::fromArray(UnionPayloadEvent::class, $value)),
            'headersAndExplicitPayload' => new EventStreamHeadersAndExplicitPayload($value instanceof HeadersAndExplicitPayloadEvent ? $value : ArrayShapeSerializer::fromArray(HeadersAndExplicitPayloadEvent::class, $value)),
            'headersAndImplicitPayload' => new EventStreamHeadersAndImplicitPayload($value instanceof HeadersAndImplicitPayloadEvent ? $value : ArrayShapeSerializer::fromArray(HeadersAndImplicitPayloadEvent::class, $value)),
            'error' => new EventStreamError($value instanceof ErrorEventException ? $value : ArrayShapeSerializer::fromArray(ErrorEventException::class, $value)),
            default => new EventStreamUnknown($tag, $value),
        };
    }

    /**
     * Create a known union variant from its schema member index.
     *
     * @param int $index The variant member index
     * @param mixed $value The variant value
     * @return self
     */
    public static function createByIndex(int $index, mixed $value): self
    {
        return match ($index) {
            0 => new EventStreamHeaders($value instanceof HeadersEvent ? $value : ArrayShapeSerializer::fromArray(HeadersEvent::class, $value)),
            1 => new EventStreamBlobPayload($value instanceof BlobPayloadEvent ? $value : ArrayShapeSerializer::fromArray(BlobPayloadEvent::class, $value)),
            2 => new EventStreamStringPayload($value instanceof StringPayloadEvent ? $value : ArrayShapeSerializer::fromArray(StringPayloadEvent::class, $value)),
            3 => new EventStreamStructurePayload($value instanceof StructurePayloadEvent ? $value : ArrayShapeSerializer::fromArray(StructurePayloadEvent::class, $value)),
            4 => new EventStreamUnionPayload($value instanceof UnionPayloadEvent ? $value : ArrayShapeSerializer::fromArray(UnionPayloadEvent::class, $value)),
            5 => new EventStreamHeadersAndExplicitPayload($value instanceof HeadersAndExplicitPayloadEvent ? $value : ArrayShapeSerializer::fromArray(HeadersAndExplicitPayloadEvent::class, $value)),
            6 => new EventStreamHeadersAndImplicitPayload($value instanceof HeadersAndImplicitPayloadEvent ? $value : ArrayShapeSerializer::fromArray(HeadersAndImplicitPayloadEvent::class, $value)),
            7 => new EventStreamError($value instanceof ErrorEventException ? $value : ArrayShapeSerializer::fromArray(ErrorEventException::class, $value)),
            default => throw new \InvalidArgumentException('Unknown EventStream variant index: ' . $index),
        };
    }
}
