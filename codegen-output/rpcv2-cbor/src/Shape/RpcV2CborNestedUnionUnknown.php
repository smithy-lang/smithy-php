<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use JsonSerializable;
use Smithy\Core\Shape\SensitiveStringableTrait;

/**
 * Unknown variant for forward-compatibility.
 */
/** @internal */
final readonly class RpcV2CborNestedUnionUnknown extends RpcV2CborNestedUnion implements JsonSerializable
{
    use SensitiveStringableTrait;

    public function __construct(private string $unknownTag, private mixed $unknownValue) {}

    public function getMemberIndex(): int
    {
        return -1;
    }

    public function tag(): string
    {
        return $this->unknownTag;
    }

    public function value(): mixed
    {
        return $this->unknownValue;
    }
}
