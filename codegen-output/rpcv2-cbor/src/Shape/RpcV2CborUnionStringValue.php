<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

/** @internal */
final readonly class RpcV2CborUnionStringValue extends RpcV2CborUnion
{
    public function __construct(public string $value) {}

    public function getMemberIndex(): int
    {
        return 0;
    }

    public function tag(): string
    {
        return 'stringValue';
    }

    public function value(): string
    {
        return $this->value;
    }
}
