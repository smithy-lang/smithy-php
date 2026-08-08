<?php

declare(strict_types=1);

namespace Smithy\Cbor;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableShapeInterface;
use Smithy\Core\Serde\ShapeDeserializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\BigDecimal;
use Smithy\Core\Shape\BigInteger;

/**
 * Deserialize CBOR payloads.
 */
final class CborDeserializer implements ShapeDeserializerInterface
{
    private const MAJOR_POSINT = 0;
    private const MAJOR_NEGINT = 1;
    private const MAJOR_BYTESTRING = 2;
    private const MAJOR_TEXTSTRING = 3;
    private const MAJOR_ARRAY = 4;
    private const MAJOR_MAP = 5;
    private const MAJOR_TAG = 6;
    private const MAJOR_SIMPLE = 7;

    private const INFO_ONE_BYTE = 24;
    private const INFO_TWO_BYTES = 25;
    private const INFO_FOUR_BYTES = 26;
    private const INFO_EIGHT_BYTES = 27;
    private const INFO_INDEFINITE = 31;

    private const SIMPLE_FALSE = 20;
    private const SIMPLE_TRUE = 21;
    private const SIMPLE_NULL = 22;
    private const SIMPLE_UNDEFINED = 23;
    private const SIMPLE_HALF_FLOAT = 25;
    private const SIMPLE_FLOAT = 26;
    private const SIMPLE_DOUBLE = 27;

    private const TAG_EPOCH = 1;
    private const TAG_POS_BIGINT = 2;
    private const TAG_NEG_BIGINT = 3;
    private const TAG_DECIMAL = 4;

    private const FNV_OFFSET = 2166136261;
    private const FNV_PRIME = 16777619;

    private readonly string $payload;
    private readonly int $length;
    private int $idx = 0;

    public function __construct(string $source)
    {
        $this->payload = $source;
        $this->length = \strlen($source);
    }

    /**
     * Deserialize a top-level structure or union into member slots.
     *
     * @param array<int, mixed> $members Member slots to populate in place.
     *
     * @return void
     */
    public function deserialize(Schema $schema, array &$members): void
    {
        if ($this->length === 0) {
            return;
        }

        if ($schema->type === ShapeType::Union->value) {
            $members[0] = $this->readUnion($schema);
            return;
        }

        $this->readStruct($schema, $members);
    }

    /**
     * Read a typed structure into preallocated member slots.
     *
     * The schema-specific branches are intentionally specialized here to keep
     * member matching and typed hydration on the hot path.
     *
     * @param array<int, mixed> $members
     */
    private function readStruct(Schema $schema, array &$members): void
    {
        static $extKey = null;
        $extKey ??= CborSchemaExtensionProvider::key();
        /** @var CborSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);
        if (!$info) {
            return;
        }

        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $initial = \ord($this->payload[$this->idx++]);
        if (($initial >> 5) !== self::MAJOR_MAP) {
            return;
        }
        $infoByte = $initial & 0x1F;
        $indefinite = $infoByte === self::INFO_INDEFINITE;
        $remaining = $indefinite ? -1 : $this->readLengthFromInfo($infoByte);

        $orderedSchemas = $info->orderedMemberSchemas;
        $orderedIndexes = $info->orderedMemberIndexes;
        $orderedTypes = $info->orderedMemberTypes;
        $orderedNameBytes = $info->orderedMemberNameBytes;
        $orderedNameLengths = $info->orderedMemberNameLengths;
        $orderedNameHashes = $info->orderedMemberNameHashes;
        $orderedLookup1 = $info->orderedMemberLookup1;
        $orderedLookup2 = $info->orderedMemberLookup2;
        $orderedLookup3 = $info->orderedMemberLookup3;
        $orderedLookup4 = $info->orderedMemberLookup4;
        $orderedTargetClasses = $info->orderedMemberResolvedTargetClasses;
        $orderedTargetSchemas = $info->orderedMemberResolvedTargetSchemas;
        $orderedCount = \count($orderedSchemas);
        $payload = $this->payload;
        $payloadLength = $this->length;

        $hint = 0;

        while (true) {
            if ($indefinite) {
                if ($this->idx < $this->length
                    && \ord($this->payload[$this->idx]) === 0xFF
                ) {
                    $this->idx++;
                    break;
                }
            }
            if (!$indefinite && $remaining-- <= 0) {
                break;
            }

            $memberSlot = -1;
            if ($this->idx >= $payloadLength) {
                throw new \RuntimeException(
                    'Unexpected end of CBOR'
                    . ' payload.'
                );
            }
            $initial = \ord($payload[$this->idx++]);
            if (($initial >> 5) !== self::MAJOR_TEXTSTRING) {
                throw new \RuntimeException(
                'CBOR map keys must be text strings.'
                );
            }

            $infoByte = $initial & 0x1F;
            if ($infoByte === self::INFO_INDEFINITE) {
                $wireKey = '';
                while (true) {
                    if ($this->idx < $payloadLength
                        && \ord($payload[$this->idx]) === 0xFF
                    ) {
                        $this->idx++;
                        break;
                    }
                    $wireKey .= $this->readTextString();
                }
                if ($hint < $orderedCount
                    && $orderedNameBytes[$hint] === $wireKey
                ) {
                    $memberSlot = $hint;
                    $hint++;
                } else {
                    for ($i = 0; $i < $orderedCount; $i++) {
                        if ($orderedNameBytes[$i] === $wireKey) {
                            $memberSlot = $i;
                            break;
                        }
                    }
                }
            } else {
                $length = $this->readLengthFromInfo($infoByte);
                if ($length < 0 || $this->idx + $length > $payloadLength) {
                    throw new \RuntimeException(
                    'Malformed CBOR text string length.'
                    );
                }

                $start = $this->idx;
                $this->idx += $length;

                switch ($length) {
                    case 1:
                        $memberSlot =
                            $orderedLookup1[\ord($payload[$start])]
                            ?? -1;
                        break;

                    case 2:
                        $memberSlot = $orderedLookup2[
                            (\ord($payload[$start]) << 8)
                            | \ord($payload[$start + 1])
                        ] ?? -1;
                        break;

                    case 3:
                        $memberSlot = $orderedLookup3[
                                      (\ord($payload[$start]) << 16)
                                      | (\ord($payload[$start + 1]) << 8)
                                      | \ord($payload[$start + 2])
                        ] ?? -1;
                        break;

                    case 4:
                        $memberSlot = $orderedLookup4[
                                      (\ord($payload[$start]) << 24)
                                      | (\ord($payload[$start + 1]) << 16)
                                      | (\ord($payload[$start + 2]) << 8)
                                      | \ord($payload[$start + 3])
                        ] ?? -1;
                        break;
                }

                if ($memberSlot === $hint) {
                    $hint++;
                }

                if ($memberSlot < 0
                    && $hint < $orderedCount
                    && $orderedNameLengths[$hint] === $length
                    && \substr_compare(
                        $payload,
                        $orderedNameBytes[$hint],
                        $start,
                        $length
                    ) === 0
                ) {
                    $memberSlot = $hint;
                    $hint++;
                }

                if ($memberSlot < 0) {
                    $hash = self::FNV_OFFSET;
                    $end = $start + $length;
                    for ($j = $start; $j < $end; $j++) {
                        $hash ^= \ord($payload[$j]);
                        $hash = ($hash * self::FNV_PRIME) & 0xFFFFFFFF;
                    }
                    $scanLimit = $hint + 4 < $orderedCount
                        ? $hint + 4
                        : $orderedCount;
                    for ($i = $hint; $i < $scanLimit; $i++) {
                        if ($orderedNameHashes[$i] === $hash
                            && $orderedNameLengths[$i] === $length
                            && \substr_compare(
                                $payload,
                                $orderedNameBytes[$i],
                                $start,
                                $length
                            ) === 0
                        ) {
                            $memberSlot = $i;
                            $hint = $i + 1;
                            break;
                        }
                    }

                    if ($memberSlot < 0) {
                        for ($i = 0; $i < $orderedCount; $i++) {
                            if ($orderedNameHashes[$i] === $hash
                                && $orderedNameLengths[$i] === $length
                                && \substr_compare(
                                    $payload,
                                    $orderedNameBytes[$i],
                                    $start,
                                    $length
                                ) === 0
                            ) {
                                $memberSlot = $i;
                                break;
                            }
                        }
                    }
                }
            }

            if ($memberSlot < 0) {
                $this->skipValue();
                continue;
            }

            if ($this->idx < $payloadLength
                && (
                    ($byte = \ord($payload[$this->idx]))
                        === 0xF6
                    || $byte === 0xF7
                )
            ) {
                $this->idx++;
                continue;
            }

            $memberIndex = $orderedIndexes[$memberSlot];
            $memberType = $orderedTypes[$memberSlot];
            $memberSchema = $orderedSchemas[$memberSlot];
            $resolvedTargetClass = $orderedTargetClasses[$memberSlot];
            $resolvedTargetSchema = $orderedTargetSchemas[$memberSlot];

            $members[$memberIndex] = $this->readValue(
                $memberType,
                $memberSchema,
                $resolvedTargetClass,
                $resolvedTargetSchema
            );
        }
    }

    /**
     * Read a typed union value.
     *
     * The variant branches remain inlined here to keep member resolution and
     * payload hydration branch-local.
     */
    private function readUnion(Schema $schema): ?SerializableShapeInterface
    {
        static $extKey = null;
        $extKey ??= CborSchemaExtensionProvider::key();
        /** @var CborSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);
        if (!$info) {
            return null;
        }

        $unionSchema = $info->resolvedTargetSchema ?? $schema;
        $targetClass = $info->resolvedTargetClass ?? $unionSchema->targetClass;
        if (!$targetClass) {
            return null;
        }

        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $initial = \ord($this->payload[$this->idx++]);
        if (($initial >> 5) !== self::MAJOR_MAP) {
            return null;
        }
        $infoByte = $initial & 0x1F;
        $indefinite = $infoByte === self::INFO_INDEFINITE;
        $remaining = $indefinite ? -1 : $this->readLengthFromInfo($infoByte);

        $orderedSchemas = $info->orderedMemberSchemas;
        $orderedTypes = $info->orderedMemberTypes;
        $orderedNameBytes = $info->orderedMemberNameBytes;
        $orderedNameLengths = $info->orderedMemberNameLengths;
        $orderedNameHashes = $info->orderedMemberNameHashes;
        $orderedLookup1 = $info->orderedMemberLookup1;
        $orderedLookup2 = $info->orderedMemberLookup2;
        $orderedLookup3 = $info->orderedMemberLookup3;
        $orderedLookup4 = $info->orderedMemberLookup4;
        $orderedTargetClasses = $info->orderedMemberResolvedTargetClasses;
        $orderedTargetSchemas = $info->orderedMemberResolvedTargetSchemas;
        $orderedCount = \count($orderedSchemas);
        $payload = $this->payload;
        $payloadLength = $this->length;

        if (!$indefinite && ($remaining === 1 || $remaining === 2)) {
            for ($entry = 0; $entry < $remaining; $entry++) {
                $memberSlot = -1;
                $wireKey = '';

                if ($this->idx >= $payloadLength) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $initial = \ord($payload[$this->idx++]);
                if (($initial >> 5) !== self::MAJOR_TEXTSTRING) {
                    throw new \RuntimeException(
                    'CBOR map keys must be text strings.'
                    );
                }

                $infoByte = $initial & 0x1F;
                if ($infoByte === self::INFO_INDEFINITE) {
                    $wireKey = '';
                    while (true) {
                        if ($this->idx < $payloadLength
                            && \ord($payload[$this->idx]) === 0xFF
                        ) {
                            $this->idx++;
                            break;
                        }
                        $wireKey .= $this->readTextString();
                    }
                    for ($i = 0; $i < $orderedCount; $i++) {
                        if ($orderedNameBytes[$i] === $wireKey) {
                            $memberSlot = $i;
                            break;
                        }
                    }
                } else {
                    $length = $this->readLengthFromInfo($infoByte);
                    if ($length < 0 || $this->idx + $length > $payloadLength) {
                        throw new \RuntimeException(
                        'Malformed CBOR text string length.'
                        );
                    }

                    $start = $this->idx;
                    $this->idx += $length;

                    switch ($length) {
                        case 1:
                            $memberSlot =
                                $orderedLookup1[\ord($payload[$start])]
                                ?? -1;
                            break;

                        case 2:
                            $memberSlot = $orderedLookup2[
                                (\ord($payload[$start]) << 8)
                                | \ord($payload[$start + 1])
                            ] ?? -1;
                            break;

                        case 3:
                            $memberSlot = $orderedLookup3[
                                          (\ord($payload[$start]) << 16)
                                          | (\ord($payload[$start + 1]) << 8)
                                          | \ord($payload[$start + 2])
                            ] ?? -1;
                            break;

                        case 4:
                            $memberSlot = $orderedLookup4[
                                          (\ord($payload[$start]) << 24)
                                          | (\ord($payload[$start + 1]) << 16)
                                          | (\ord($payload[$start + 2]) << 8)
                                          | \ord($payload[$start + 3])
                            ] ?? -1;
                            break;
                    }

                    if ($length === 6
                        && \substr_compare($payload, '__type', $start, 6) === 0
                    ) {
                        $wireKey = '__type';
                    }

                    if ($wireKey === '' && $memberSlot < 0) {
                        $hash = self::FNV_OFFSET;
                        $end = $start + $length;
                        for ($j = $start; $j < $end; $j++) {
                            $hash ^= \ord($payload[$j]);
                            $hash = ($hash * self::FNV_PRIME) & 0xFFFFFFFF;
                        }
                        for ($i = 0; $i < $orderedCount; $i++) {
                            if ($orderedNameHashes[$i] === $hash
                                && $orderedNameLengths[$i] === $length
                                && \substr_compare(
                                    $payload,
                                    $orderedNameBytes[$i],
                                    $start,
                                    $length
                                ) === 0
                            ) {
                                $memberSlot = $i;
                                break;
                            }
                        }

                        if ($memberSlot < 0) {
                            $wireKey = \substr($payload, $start, $length);
                        }
                    }
                }

                if ($wireKey === '__type') {
                    $this->skipValue();
                    continue;
                }

                if ($this->idx < $payloadLength
                    && (
                        ($byte = \ord($payload[$this->idx]))
                            === 0xF6
                        || $byte === 0xF7
                    )
                ) {
                    $this->idx++;
                    continue;
                }

                if ($memberSlot < 0) {
                    return $targetClass::unknown(
                    $wireKey,
                    $this->readUntypedValue()
                    );
                }

                $memberName = $orderedNameBytes[$memberSlot];
                $memberType = $orderedTypes[$memberSlot];
                $memberSchema = $orderedSchemas[$memberSlot];
                $resolvedTargetClass = $orderedTargetClasses[$memberSlot];
                $resolvedTargetSchema = $orderedTargetSchemas[$memberSlot];

                return $targetClass::create(
                    $memberName,
                    $this->readValue(
                        $memberType,
                        $memberSchema,
                        $resolvedTargetClass,
                        $resolvedTargetSchema
                    )
                );
            }

            return null;
        }

        $hint = 0;

        while (true) {
            if ($indefinite) {
                if ($this->idx < $payloadLength
                    && \ord($payload[$this->idx]) === 0xFF
                ) {
                    $this->idx++;
                    break;
                }
            }
            if (!$indefinite && $remaining-- <= 0) {
                break;
            }

            $memberSlot = -1;
            $wireKey = '';
            if ($this->idx >= $payloadLength) {
                throw new \RuntimeException(
                    'Unexpected end of CBOR'
                    . ' payload.'
                );
            }
            $initial = \ord($payload[$this->idx++]);
            if (($initial >> 5) !== self::MAJOR_TEXTSTRING) {
                throw new \RuntimeException(
                'CBOR map keys must be text strings.'
                );
            }

            $infoByte = $initial & 0x1F;
            if ($infoByte === self::INFO_INDEFINITE) {
                $wireKey = '';
                while (true) {
                    if ($this->idx < $payloadLength
                        && \ord($payload[$this->idx]) === 0xFF
                    ) {
                        $this->idx++;
                        break;
                    }
                    $wireKey .= $this->readTextString();
                }
                if ($hint < $orderedCount
                    && $orderedNameBytes[$hint] === $wireKey
                ) {
                    $memberSlot = $hint;
                    $hint++;
                } else {
                    for ($i = 0; $i < $orderedCount; $i++) {
                        if ($orderedNameBytes[$i] === $wireKey) {
                            $memberSlot = $i;
                            break;
                        }
                    }
                }
            } else {
                $length = $this->readLengthFromInfo($infoByte);
                if ($length < 0 || $this->idx + $length > $payloadLength) {
                    throw new \RuntimeException(
                    'Malformed CBOR text string length.'
                    );
                }

                $start = $this->idx;
                $this->idx += $length;

                switch ($length) {
                    case 1:
                        $memberSlot =
                            $orderedLookup1[\ord($payload[$start])]
                            ?? -1;
                        break;

                    case 2:
                        $memberSlot = $orderedLookup2[
                            (\ord($payload[$start]) << 8)
                            | \ord($payload[$start + 1])
                        ] ?? -1;
                        break;

                    case 3:
                        $memberSlot = $orderedLookup3[
                                      (\ord($payload[$start]) << 16)
                                      | (\ord($payload[$start + 1]) << 8)
                                      | \ord($payload[$start + 2])
                        ] ?? -1;
                        break;

                    case 4:
                        $memberSlot = $orderedLookup4[
                                      (\ord($payload[$start]) << 24)
                                      | (\ord($payload[$start + 1]) << 16)
                                      | (\ord($payload[$start + 2]) << 8)
                                      | \ord($payload[$start + 3])
                        ] ?? -1;
                        break;
                }

                if ($length === 6
                    && \substr_compare($payload, '__type', $start, 6) === 0
                ) {
                    $wireKey = '__type';
                }

                if ($wireKey === '' && $memberSlot === $hint) {
                    $hint++;
                }

                if ($wireKey === ''
                    && $memberSlot < 0
                    && $hint < $orderedCount
                    && $orderedNameLengths[$hint] === $length
                    && \substr_compare(
                        $payload,
                        $orderedNameBytes[$hint],
                        $start,
                        $length
                    ) === 0
                ) {
                    $memberSlot = $hint;
                    $hint++;
                    $wireKey = $orderedNameBytes[$memberSlot];
                }

                if ($wireKey === '' && $memberSlot < 0) {
                    $hash = self::FNV_OFFSET;
                    $end = $start + $length;
                    for ($j = $start; $j < $end; $j++) {
                        $hash ^= \ord($payload[$j]);
                        $hash = ($hash * self::FNV_PRIME) & 0xFFFFFFFF;
                    }
                    $scanLimit = $hint + 4 < $orderedCount
                        ? $hint + 4
                        : $orderedCount;
                    for ($i = $hint; $i < $scanLimit; $i++) {
                        if ($orderedNameHashes[$i] === $hash
                            && $orderedNameLengths[$i] === $length
                            && \substr_compare(
                                $payload,
                                $orderedNameBytes[$i],
                                $start,
                                $length
                            ) === 0
                        ) {
                            $memberSlot = $i;
                            $hint = $i + 1;
                            $wireKey = $orderedNameBytes[$memberSlot];
                            break;
                        }
                    }

                    if ($memberSlot < 0 && $wireKey === '') {
                        for ($i = 0; $i < $orderedCount; $i++) {
                            if ($orderedNameHashes[$i] === $hash
                                && $orderedNameLengths[$i] === $length
                                && \substr_compare(
                                    $payload,
                                    $orderedNameBytes[$i],
                                    $start,
                                    $length
                                ) === 0
                            ) {
                                $memberSlot = $i;
                                $wireKey = $orderedNameBytes[$memberSlot];
                                break;
                            }
                        }
                    }

                    if ($memberSlot < 0 && $wireKey === '') {
                        $wireKey = \substr($payload, $start, $length);
                    }
                }
            }

            if ($wireKey === '__type') {
                $this->skipValue();
                continue;
            }

            if ($this->idx < $payloadLength
                && (
                    ($byte = \ord($payload[$this->idx]))
                        === 0xF6
                    || $byte === 0xF7
                )
            ) {
                $this->idx++;
                continue;
            }

            if ($memberSlot < 0) {
                return $targetClass::unknown(
                $wireKey,
                $this->readUntypedValue()
                );
            }

            $memberName = $orderedNameBytes[$memberSlot];
            $memberType = $orderedTypes[$memberSlot];
            $memberSchema = $orderedSchemas[$memberSlot];
            $resolvedTargetClass = $orderedTargetClasses[$memberSlot];
            $resolvedTargetSchema = $orderedTargetSchemas[$memberSlot];

            return $targetClass::create(
                $memberName,
                $this->readValue(
                    $memberType,
                    $memberSchema,
                    $resolvedTargetClass,
                    $resolvedTargetSchema
                )
            );
        }

        return null;
    }

    /**
     * Read a typed member value.
     */
    private function readValue(
        int $type,
        Schema $schema,
        ?string $resolvedTargetClass = null,
        ?Schema $resolvedTargetSchema = null
    ): mixed {
        static $extKey = null;
        $extKey ??= CborSchemaExtensionProvider::key();

        switch ($type) {
            case ShapeType::String_->value:
                return $this->readTextString();

            case ShapeType::Enum->value:
                $value = $this->readTextString();
                return $resolvedTargetClass !== null
                    ? $resolvedTargetClass::fromValue($value)
                    : $value;

            case ShapeType::Boolean->value:
                if ($this->idx >= $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $initial = \ord($this->payload[$this->idx++]);
                if ($initial === 0xF4) {
                    return false;
                }
                if ($initial === 0xF5) {
                    return true;
                }
                throw new \RuntimeException('Expected CBOR boolean.');

            case ShapeType::Integer->value:
            case ShapeType::Long->value:
            case ShapeType::Short->value:
            case ShapeType::Byte->value:
                return $this->readSignedInteger();

            case ShapeType::IntEnum->value:
                $value = $this->readSignedInteger();
                return $resolvedTargetClass !== null
                    ? $resolvedTargetClass::fromValue($value)
                    : $value;

            case ShapeType::Float_->value:
            case ShapeType::Double->value:
                return $this->readNumeric();

            case ShapeType::BigInteger->value:
                return $this->readBigInteger();

            case ShapeType::BigDecimal->value:
                return $this->readBigDecimal();

            case ShapeType::Blob->value:
                return $this->readByteString();

            case ShapeType::Timestamp->value:
                return $this->readTimestamp();

            case ShapeType::Structure->value:
                if (!$resolvedTargetClass) {
                    $this->skipValue();
                    return null;
                }
                $targetSchema = $resolvedTargetSchema ?? $schema;
                /** @var CborSchemaExtension|null $targetInfo */
                $targetInfo = $targetSchema->getExtension($extKey);
                $nestedMembers = $targetInfo?->membersTemplate ?? [];
                $this->readStruct($targetSchema, $nestedMembers);
                return $resolvedTargetClass::fromMembers($nestedMembers);

            case ShapeType::Union->value:
                return $this->readUnion($resolvedTargetSchema ?? $schema);

            case ShapeType::List_->value:
                return $this->readList($schema);

            case ShapeType::Map->value:
                return $this->readMap($schema);

            case ShapeType::Document->value:
                throw new \LogicException(
                    'rpcv2-cbor does not support'
                    . ' Smithy document shapes.'
                );

            default:
                return $this->readUntypedValue();
        }
    }

    /**
     * Read a typed list value.
     *
     * Sparse handling and container framing remain on the hot path while item
     * dispatch is delegated to readValue().
     */
    private function readList(Schema $schema): array
    {
        static $extKey = null;
        $extKey ??= CborSchemaExtensionProvider::key();
        /** @var CborSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);
        $itemSchema = $info?->listMemberSchema ?? $schema->listMember;
        if (!$itemSchema) {
            $this->skipValue();
            return [];
        }

        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $initial = \ord($this->payload[$this->idx++]);
        if (($initial >> 5) !== self::MAJOR_ARRAY) {
            return [];
        }
        $infoByte = $initial & 0x1F;
        $indefinite = $infoByte === self::INFO_INDEFINITE;
        $remaining = $indefinite ? -1 : $this->readLengthFromInfo($infoByte);

        $out = [];
        if ($remaining === 0) {
            return $out;
        }

        $itemType = $info?->listMemberType ?? $itemSchema->type;
        $resolvedTargetClass = $info?->listMemberResolvedTargetClass;
        $resolvedTargetSchema = $info?->listMemberResolvedTargetSchema;
        $payload = $this->payload;
        $payloadLength = $this->length;
        $sparse = $info?->sparse ?? false;

        while (true) {
            if ($indefinite) {
                if ($this->idx < $payloadLength
                    && \ord($payload[$this->idx]) === 0xFF
                ) {
                    $this->idx++;
                    return $out;
                }
            }
            if (!$indefinite && $remaining-- <= 0) {
                return $out;
            }

            if ($this->idx < $payloadLength
                && (
                    ($byte = \ord($payload[$this->idx]))
                        === 0xF6
                    || $byte === 0xF7
                )
            ) {
                $this->idx++;
                if ($sparse) {
                    $out[] = null;
                }
                continue;
            }

            $out[] = $this->readValue(
                $itemType,
                $itemSchema,
                $resolvedTargetClass,
                $resolvedTargetSchema
            );
        }
    }

    /**
     * Read a typed map value.
     *
     * Key parsing, sparse handling, and container framing remain on the hot
     * path while value dispatch is delegated to readValue().
     */
    private function readMap(Schema $schema): array
    {
        static $extKey = null;
        $extKey ??= CborSchemaExtensionProvider::key();
        /** @var CborSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);
        $valueSchema = $info?->mapValueSchema ?? $schema->mapValue;
        if (!$valueSchema) {
            $this->skipValue();
            return [];
        }

        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $initial = \ord($this->payload[$this->idx++]);
        if (($initial >> 5) !== self::MAJOR_MAP) {
            return [];
        }
        $infoByte = $initial & 0x1F;
        $indefinite = $infoByte === self::INFO_INDEFINITE;
        $remaining = $indefinite ? -1 : $this->readLengthFromInfo($infoByte);

        $out = [];
        if ($remaining === 0) {
            return $out;
        }

        $valueType = $info?->mapValueType ?? $valueSchema->type;
        $resolvedTargetClass = $info?->mapValueResolvedTargetClass;
        $resolvedTargetSchema = $info?->mapValueResolvedTargetSchema;
        $payload = $this->payload;
        $payloadLength = $this->length;
        $sparse = $info?->sparse ?? false;

        while (true) {
            if ($indefinite) {
                if ($this->idx < $payloadLength
                    && \ord($payload[$this->idx]) === 0xFF
                ) {
                    $this->idx++;
                    return $out;
                }
            }
            if (!$indefinite && $remaining-- <= 0) {
                return $out;
            }

            $key = $this->readTextString();
            if ($this->idx < $payloadLength
                && (
                    ($byte = \ord($payload[$this->idx]))
                        === 0xF6
                    || $byte === 0xF7
                )
            ) {
                $this->idx++;
                if ($sparse) {
                    $out[$key] = null;
                }
                continue;
            }

            $out[$key] = $this->readValue(
                $valueType,
                $valueSchema,
                $resolvedTargetClass,
                $resolvedTargetSchema
            );
        }
    }

    /**
     * Read a signed CBOR integer.
     */
    private function readSignedInteger(): int
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }

        $payload = $this->payload;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        $unsigned = $info;
        if ($info >= self::INFO_ONE_BYTE) {
            switch ($info) {
                case self::INFO_ONE_BYTE:
                    if ($this->idx >= $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $unsigned = \ord($payload[$this->idx++]);
                    break;

                case self::INFO_TWO_BYTES:
                    if ($this->idx + 2 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $unsigned = (\ord($payload[$idx]) << 8)
                        | \ord($payload[$idx + 1]);
                    $this->idx = $idx + 2;
                    break;

                case self::INFO_FOUR_BYTES:
                    if ($this->idx + 4 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $unsigned = (\ord($payload[$idx]) << 24)
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    $this->idx = $idx + 4;
                    break;

                case self::INFO_EIGHT_BYTES:
                    if ($this->idx + 8 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $high = (\ord($payload[$idx]) << 24)
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    if ($high > 0x7FFF_FFFF) {
                        throw new \RuntimeException(
                            'CBOR uint64 exceeds PHP'
                            . ' int range.'
                        );
                    }
                    $low = (
                        \ord($payload[$idx + 4]) << 24
                    )
                        | (
                            \ord($payload[$idx + 5]) << 16
                        )
                        | (
                            \ord($payload[$idx + 6]) << 8
                        )
                        | \ord($payload[$idx + 7]);
                    $unsigned = ($high << 32) | $low;
                    $this->idx = $idx + 8;
                    break;

                default:
                    throw new \RuntimeException(
                        'Unsupported CBOR additional'
                        . ' info.'
                    );
            }
        }

        return match ($major) {
            self::MAJOR_POSINT => $unsigned,
            self::MAJOR_NEGINT => $unsigned === PHP_INT_MAX
                ? PHP_INT_MIN
                : -1 - $unsigned,
            default => throw new \RuntimeException('Expected CBOR integer.'),
        };
    }

    /**
     * Read a numeric CBOR value.
     */
    private function readNumeric(): float|int
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }

        $payload = $this->payload;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        if ($major !== self::MAJOR_POSINT
            && $major !== self::MAJOR_NEGINT
        ) {
            if ($major === self::MAJOR_SIMPLE) {
                switch ($info) {
                    case self::SIMPLE_HALF_FLOAT:
                        return $this->readHalfFloat();

                    case self::SIMPLE_FLOAT:
                        if ($this->idx + 4 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $value = \unpack('G', $this->payload, $idx)[1];
                        $this->idx = $idx + 4;
                        return $value;

                    case self::SIMPLE_DOUBLE:
                        if ($this->idx + 8 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $value = \unpack('E', $this->payload, $idx)[1];
                        $this->idx = $idx + 8;
                        return $value;
                }

                throw new \RuntimeException('Expected CBOR float.');
            }

            throw new \RuntimeException('Expected CBOR numeric value.');
        }

        $unsigned = $info;
        if ($info >= self::INFO_ONE_BYTE) {
            switch ($info) {
                case self::INFO_ONE_BYTE:
                    if ($this->idx >= $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $unsigned = \ord($payload[$this->idx++]);
                    break;

                case self::INFO_TWO_BYTES:
                    if ($this->idx + 2 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $unsigned = (\ord($payload[$idx]) << 8)
                        | \ord($payload[$idx + 1]);
                    $this->idx = $idx + 2;
                    break;

                case self::INFO_FOUR_BYTES:
                    if ($this->idx + 4 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $unsigned = (\ord($payload[$idx]) << 24)
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    $this->idx = $idx + 4;
                    break;

                case self::INFO_EIGHT_BYTES:
                    if ($this->idx + 8 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $high = (\ord($payload[$idx]) << 24)
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    if ($high > 0x7FFF_FFFF) {
                        throw new \RuntimeException(
                            'CBOR uint64 exceeds PHP'
                            . ' int range.'
                        );
                    }
                    $low = (
                        \ord($payload[$idx + 4]) << 24
                    )
                        | (
                            \ord($payload[$idx + 5]) << 16
                        )
                        | (
                            \ord($payload[$idx + 6]) << 8
                        )
                        | \ord($payload[$idx + 7]);
                    $unsigned = ($high << 32) | $low;
                    $this->idx = $idx + 8;
                    break;

                default:
                    throw new \RuntimeException(
                        'Unsupported CBOR additional'
                        . ' info.'
                    );
            }
        }

        return $major === self::MAJOR_POSINT
            ? $unsigned
            : ($unsigned === PHP_INT_MAX ? PHP_INT_MIN : -1 - $unsigned);
    }

    /**
     * Read a Smithy BigInteger value.
     */
    private function readBigInteger(): BigInteger
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $initial = \ord($this->payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        if ($major === self::MAJOR_POSINT) {
            return new BigInteger((string) $this->readLengthFromInfo($info));
        }
        if ($major === self::MAJOR_NEGINT) {
            $unsigned = $this->readLengthFromInfo($info);
            return new BigInteger(
                (string) (
                    $unsigned === PHP_INT_MAX ? PHP_INT_MIN : -1 - $unsigned
                )
            );
        }
        if ($major !== self::MAJOR_TAG) {
            throw new \RuntimeException('Expected CBOR bigInteger value.');
        }

        switch ($info) {
            case self::TAG_POS_BIGINT:
                if ($this->idx >= $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $payload = $this->payload;
                $initial = \ord($payload[$this->idx++]);
                $major = $initial >> 5;
                $byteInfo = $initial & 0x1F;
                if ($major !== self::MAJOR_BYTESTRING) {
                    throw new \RuntimeException(
                    'Expected CBOR byte string.'
                    );
                }
                if ($byteInfo === self::INFO_INDEFINITE) {
                    $bytes = '';
                    while (true) {
                        if ($this->idx < $this->length
                            && \ord($this->payload[$this->idx]) === 0xFF
                        ) {
                            $this->idx++;
                            break;
                        }
                        $bytes .= $this->readByteString();
                    }
                } else {
                    $length = $byteInfo;
                    if ($byteInfo >= 24) {
                        switch ($byteInfo) {
                            case 24:
                                if ($this->idx >= $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $length = \ord(
                                    $payload[$this->idx++]
                                );
                                break;
                            case 25:
                                if ($this->idx + 2 > $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $length = (
                                    \ord($payload[$idx]) << 8
                                )
                                    | \ord($payload[$idx + 1]);
                                $this->idx = $idx + 2;
                                break;
                            case 26:
                                if ($this->idx + 4 > $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $length = (
                                    \ord($payload[$idx]) << 24
                                )
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                $this->idx = $idx + 4;
                                break;
                            case 27:
                                if ($this->idx + 8 > $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $high = (\ord($payload[$idx]) << 24)
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                if ($high > 0x7FFF_FFFF) {
                                    throw new \RuntimeException(
                                        'CBOR uint64 exceeds PHP'
                                        . ' int range.'
                                    );
                                }
                                $low = (
                                    \ord($payload[$idx + 4]) << 24
                                )
                                    | (
                                        \ord($payload[$idx + 5]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 6]) << 8
                                    )
                                    | \ord($payload[$idx + 7]);
                                $length = ($high << 32) | $low;
                                $this->idx = $idx + 8;
                                break;
                            default:
                                throw new \RuntimeException(
                                    'Unsupported CBOR additional'
                                    . ' info.'
                                );
                        }
                    }
                    if ($this->idx + $length > $this->length) {
                        throw new \RuntimeException(
                        'Malformed CBOR byte string length.'
                        );
                    }
                    $start = $this->idx;
                    $this->idx += $length;
                    $bytes = \substr($payload, $start, $length);
                }
                $result = '0';
                $length = \strlen($bytes);
                for ($i = 0; $i < $length; $i++) {
                    $result =
                        $this->multiplyUnsignedDecimalStringBySmall(
                    $result,
                    256
                    );
                    $result = $this->addSmallToUnsignedDecimalString(
                        $result,
                        \ord($bytes[$i])
                    );
                }
                return new BigInteger($result);

            case self::TAG_NEG_BIGINT:
                if ($this->idx >= $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $payload = $this->payload;
                $initial = \ord($payload[$this->idx++]);
                $major = $initial >> 5;
                $byteInfo = $initial & 0x1F;
                if ($major !== self::MAJOR_BYTESTRING) {
                    throw new \RuntimeException(
                    'Expected CBOR byte string.'
                    );
                }
                if ($byteInfo === self::INFO_INDEFINITE) {
                    $bytes = '';
                    while (true) {
                        if ($this->idx < $this->length
                            && \ord($this->payload[$this->idx]) === 0xFF
                        ) {
                            $this->idx++;
                            break;
                        }
                        $bytes .= $this->readByteString();
                    }
                } else {
                    $length = $byteInfo;
                    if ($byteInfo >= 24) {
                        switch ($byteInfo) {
                            case 24:
                                if ($this->idx >= $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $length = \ord(
                                    $payload[$this->idx++]
                                );
                                break;
                            case 25:
                                if ($this->idx + 2 > $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $length = (
                                    \ord($payload[$idx]) << 8
                                )
                                    | \ord($payload[$idx + 1]);
                                $this->idx = $idx + 2;
                                break;
                            case 26:
                                if ($this->idx + 4 > $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $length = (
                                    \ord($payload[$idx]) << 24
                                )
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                $this->idx = $idx + 4;
                                break;
                            case 27:
                                if ($this->idx + 8 > $this->length) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $high = (\ord($payload[$idx]) << 24)
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                if ($high > 0x7FFF_FFFF) {
                                    throw new \RuntimeException(
                                        'CBOR uint64 exceeds PHP'
                                        . ' int range.'
                                    );
                                }
                                $low = (
                                    \ord($payload[$idx + 4]) << 24
                                )
                                    | (
                                        \ord($payload[$idx + 5]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 6]) << 8
                                    )
                                    | \ord($payload[$idx + 7]);
                                $length = ($high << 32) | $low;
                                $this->idx = $idx + 8;
                                break;
                            default:
                                throw new \RuntimeException(
                                    'Unsupported CBOR additional'
                                    . ' info.'
                                );
                        }
                    }
                    if ($this->idx + $length > $this->length) {
                        throw new \RuntimeException(
                        'Malformed CBOR byte string length.'
                        );
                    }
                    $start = $this->idx;
                    $this->idx += $length;
                    $bytes = \substr($payload, $start, $length);
                }
                $result = '0';
                $length = \strlen($bytes);
                for ($i = 0; $i < $length; $i++) {
                    $result =
                        $this->multiplyUnsignedDecimalStringBySmall(
                    $result,
                    256
                    );
                    $result = $this->addSmallToUnsignedDecimalString(
                        $result,
                        \ord($bytes[$i])
                    );
                }
                return new BigInteger(
                '-' . $this->incrementUnsignedDecimalString($result)
                );
        }

        throw new \RuntimeException('Unsupported CBOR bigInteger tag.');
    }

    /**
     * Read a Smithy BigDecimal value.
     */
    private function readBigDecimal(): BigDecimal
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $payload = $this->payload;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;
        if ($major !== self::MAJOR_TAG || $info !== self::TAG_DECIMAL) {
            throw new \RuntimeException('Expected CBOR decimal-fraction tag.');
        }

        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;
        if ($major !== self::MAJOR_ARRAY) {
            $indefinite = false;
            $remaining = -1;
        } else {
            $indefinite = $info === self::INFO_INDEFINITE;
            $remaining = $indefinite
                ? -1
                : $this->readLengthFromInfo($info);
        }
        if ($indefinite || $remaining !== 2) {
            throw new \RuntimeException(
            'Malformed CBOR decimal-fraction payload.'
            );
        }

        $exponent = $this->readBigInteger()->value;
        $mantissa = $this->readBigInteger()->value;
        return new BigDecimal(
            $exponent === '0'
                ? $mantissa
                : $mantissa . 'e' . $exponent
        );
    }

    /**
     * Read a Smithy timestamp value.
     */
    private function readTimestamp(): float
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }

        $payload = $this->payload;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;
        if ($major !== self::MAJOR_TAG || $info !== self::TAG_EPOCH) {
            throw new \RuntimeException('Expected CBOR epoch timestamp tag.');
        }

        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }

        $next = \ord($payload[$this->idx++]);
        $major = $next >> 5;
        $info = $next & 0x1F;

        if ($major === self::MAJOR_POSINT || $major === self::MAJOR_NEGINT) {
            $unsigned = $info;
            if ($info >= 24) {
                switch ($info) {
                    case 24:
                        if ($this->idx >= $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $unsigned = \ord($payload[$this->idx++]);
                        break;

                    case 25:
                        if ($this->idx + 2 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $unsigned = (\ord($payload[$idx]) << 8)
                            | \ord($payload[$idx + 1]);
                        $this->idx = $idx + 2;
                        break;

                    case 26:
                        if ($this->idx + 4 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $unsigned = (\ord($payload[$idx]) << 24)
                            | (
                                \ord($payload[$idx + 1]) << 16
                            )
                            | (
                                \ord($payload[$idx + 2]) << 8
                            )
                            | \ord($payload[$idx + 3]);
                        $this->idx = $idx + 4;
                        break;

                    case 27:
                        if ($this->idx + 8 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $high = (\ord($payload[$idx]) << 24)
                            | (
                                \ord($payload[$idx + 1]) << 16
                            )
                            | (
                                \ord($payload[$idx + 2]) << 8
                            )
                            | \ord($payload[$idx + 3]);
                        if ($high > 0x7FFF_FFFF) {
                            throw new \RuntimeException(
                                'CBOR uint64 exceeds PHP'
                                . ' int range.'
                            );
                        }
                        $low = (
                            \ord($payload[$idx + 4]) << 24
                        )
                            | (
                                \ord($payload[$idx + 5]) << 16
                            )
                            | (
                                \ord($payload[$idx + 6]) << 8
                            )
                            | \ord($payload[$idx + 7]);
                        $unsigned = ($high << 32) | $low;
                        $this->idx = $idx + 8;
                        break;

                    default:
                        throw new \RuntimeException(
                            'Unsupported CBOR additional'
                            . ' info.'
                        );
                }
            }

            return $major === self::MAJOR_POSINT
                ? $unsigned
                : (
                    $unsigned === PHP_INT_MAX
                        ? (float) PHP_INT_MIN
                        : (float) (-1 - $unsigned)
                );
        }

        if ($major === self::MAJOR_SIMPLE) {
            switch ($info) {
                case 25:
                    return $this->readHalfFloat();

                case 26:
                    if ($this->idx + 4 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $value = \unpack('G', $this->payload, $idx)[1];
                    $this->idx = $idx + 4;
                    return (float) $value;

                case 27:
                    if ($this->idx + 8 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $value = \unpack('E', $this->payload, $idx)[1];
                    $this->idx = $idx + 8;
                    return $value;
            }

            throw new \RuntimeException(
            'Malformed CBOR epoch timestamp payload.'
            );
        }

        throw new \RuntimeException(
        'Malformed CBOR epoch timestamp payload.'
        );
    }

    /**
     * Read a CBOR text string.
     */
    private function readTextString(): string
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }

        $payload = $this->payload;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;
        if ($major !== self::MAJOR_TEXTSTRING) {
            throw new \RuntimeException(
            'Expected CBOR text string.'
            );
        }

        if ($info === self::INFO_INDEFINITE) {
            $chunks = '';
            while (true) {
                if ($this->idx < $this->length
                    && \ord($this->payload[$this->idx]) === 0xFF
                ) {
                    $this->idx++;
                    return $chunks;
                }
                $chunks .= $this->readTextString();
            }
        }

        $length = $info;
        if ($info >= 24) {
            switch ($info) {
                case 24:
                    if ($this->idx >= $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $length = \ord(
                        $payload[$this->idx++]
                    );
                    break;

                case 25:
                    if ($this->idx + 2 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $length = (
                        \ord($payload[$idx]) << 8
                    )
                        | \ord($payload[$idx + 1]);
                    $this->idx = $idx + 2;
                    break;

                case 26:
                    if ($this->idx + 4 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $length = (
                        \ord($payload[$idx]) << 24
                    )
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    $this->idx = $idx + 4;
                    break;

                case 27:
                    if ($this->idx + 8 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $high = (\ord($payload[$idx]) << 24)
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    if ($high > 0x7FFF_FFFF) {
                        throw new \RuntimeException(
                            'CBOR uint64 exceeds PHP'
                            . ' int range.'
                        );
                    }
                    $low = (
                        \ord($payload[$idx + 4]) << 24
                    )
                        | (
                            \ord($payload[$idx + 5]) << 16
                        )
                        | (
                            \ord($payload[$idx + 6]) << 8
                        )
                        | \ord($payload[$idx + 7]);
                    $length = ($high << 32) | $low;
                    $this->idx = $idx + 8;
                    break;

                default:
                    throw new \RuntimeException(
                        'Unsupported CBOR additional'
                        . ' info.'
                    );
            }
        }

        if ($this->idx + $length > $this->length) {
            throw new \RuntimeException(
            'Malformed CBOR text string length.'
            );
        }
        $start = $this->idx;
        $this->idx += $length;
        return \substr($payload, $start, $length);
    }

    /**
     * Read a CBOR byte string.
     */
    private function readByteString(): string
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }

        $payload = $this->payload;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;
        if ($major !== self::MAJOR_BYTESTRING) {
            throw new \RuntimeException(
            'Expected CBOR byte string.'
            );
        }

        if ($info === self::INFO_INDEFINITE) {
            $chunks = '';
            while (true) {
                if ($this->idx < $this->length
                    && \ord($this->payload[$this->idx]) === 0xFF
                ) {
                    $this->idx++;
                    return $chunks;
                }
                $chunks .= $this->readByteString();
            }
        }

        $length = $info;
        if ($info >= 24) {
            switch ($info) {
                case 24:
                    if ($this->idx >= $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $length = \ord(
                        $payload[$this->idx++]
                    );
                    break;

                case 25:
                    if ($this->idx + 2 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $length = (
                        \ord($payload[$idx]) << 8
                    )
                        | \ord($payload[$idx + 1]);
                    $this->idx = $idx + 2;
                    break;

                case 26:
                    if ($this->idx + 4 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $length = (
                        \ord($payload[$idx]) << 24
                    )
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    $this->idx = $idx + 4;
                    break;

                case 27:
                    if ($this->idx + 8 > $this->length) {
                        throw new \RuntimeException(
                            'Unexpected end of CBOR'
                            . ' payload.'
                        );
                    }
                    $idx = $this->idx;
                    $high = (\ord($payload[$idx]) << 24)
                        | (
                            \ord($payload[$idx + 1]) << 16
                        )
                        | (
                            \ord($payload[$idx + 2]) << 8
                        )
                        | \ord($payload[$idx + 3]);
                    if ($high > 0x7FFF_FFFF) {
                        throw new \RuntimeException(
                            'CBOR uint64 exceeds PHP'
                            . ' int range.'
                        );
                    }
                    $low = (
                        \ord($payload[$idx + 4]) << 24
                    )
                        | (
                            \ord($payload[$idx + 5]) << 16
                        )
                        | (
                            \ord($payload[$idx + 6]) << 8
                        )
                        | \ord($payload[$idx + 7]);
                    $length = ($high << 32) | $low;
                    $this->idx = $idx + 8;
                    break;

                default:
                    throw new \RuntimeException(
                        'Unsupported CBOR additional'
                        . ' info.'
                    );
            }
        }

        if ($this->idx + $length > $this->length) {
            throw new \RuntimeException(
            'Malformed CBOR byte string length.'
            );
        }
        $start = $this->idx;
        $this->idx += $length;
        return \substr($payload, $start, $length);
    }

    /**
     * Generic untyped reader for unknown union variants.
     */
    /**
     * Read an untyped CBOR value.
     */
    private function readUntypedValue(): mixed
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }

        $payload = $this->payload;
        $payloadLength = $this->length;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        switch ($major) {
            case self::MAJOR_POSINT:
                if ($info < self::INFO_ONE_BYTE) {
                    return $info;
                }
                switch ($info) {
                    case self::INFO_ONE_BYTE:
                        if ($this->idx >= $payloadLength) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        return \ord($payload[$this->idx++]);

                    case self::INFO_TWO_BYTES:
                        if ($this->idx + 2 > $payloadLength
                        ) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $this->idx = $idx + 2;
                        return (\ord($payload[$idx]) << 8)
                            | \ord($payload[$idx + 1]);

                    case self::INFO_FOUR_BYTES:
                        if ($this->idx + 4 > $payloadLength
                        ) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $this->idx = $idx + 4;
                        return (\ord($payload[$idx]) << 24)
                            | (
                                \ord($payload[$idx + 1]) << 16
                            )
                            | (
                                \ord($payload[$idx + 2]) << 8
                            )
                            | \ord($payload[$idx + 3]);

                    case self::INFO_EIGHT_BYTES:
                        if ($this->idx + 8 > $payloadLength
                        ) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $this->idx = $idx + 8;
                        $high = (\ord($payload[$idx]) << 24)
                            | (
                                \ord($payload[$idx + 1]) << 16
                            )
                            | (
                                \ord($payload[$idx + 2]) << 8
                            )
                            | \ord($payload[$idx + 3]);
                        if ($high > 0x7FFF_FFFF) {
                            throw new \RuntimeException(
                                'CBOR uint64 exceeds PHP'
                                . ' int range.'
                            );
                        }
                        $low = (
                            \ord($payload[$idx + 4]) << 24
                        )
                            | (
                                \ord($payload[$idx + 5]) << 16
                            )
                            | (
                                \ord($payload[$idx + 6]) << 8
                            )
                            | \ord($payload[$idx + 7]);
                        return ($high << 32) | $low;
                }
                throw new \RuntimeException(
                    'Unsupported CBOR additional'
                    . ' info.'
                );

            case self::MAJOR_NEGINT:
                $unsigned = $info;
                if ($info >= self::INFO_ONE_BYTE) {
                    switch ($info) {
                        case self::INFO_ONE_BYTE:
                            if ($this->idx >= $payloadLength) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $unsigned = \ord($payload[$this->idx++]);
                            break;

                        case self::INFO_TWO_BYTES:
                            if ($this->idx + 2 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $unsigned = (\ord($payload[$idx]) << 8)
                                | \ord($payload[$idx + 1]);
                            $this->idx = $idx + 2;
                            break;

                        case self::INFO_FOUR_BYTES:
                            if ($this->idx + 4 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $unsigned = (\ord($payload[$idx]) << 24)
                                | (
                                    \ord($payload[$idx + 1]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 2]) << 8
                                )
                                | \ord($payload[$idx + 3]);
                            $this->idx = $idx + 4;
                            break;

                        case self::INFO_EIGHT_BYTES:
                            if ($this->idx + 8 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $high = (\ord($payload[$idx]) << 24)
                                | (
                                    \ord($payload[$idx + 1]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 2]) << 8
                                )
                                | \ord($payload[$idx + 3]);
                            if ($high > 0x7FFF_FFFF) {
                                throw new \RuntimeException(
                                    'CBOR uint64 exceeds PHP'
                                    . ' int range.'
                                );
                            }
                            $low = (
                                \ord($payload[$idx + 4]) << 24
                            )
                                | (
                                    \ord($payload[$idx + 5]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 6]) << 8
                                )
                                | \ord($payload[$idx + 7]);
                            $unsigned = ($high << 32) | $low;
                            $this->idx = $idx + 8;
                            break;

                        default:
                            throw new \RuntimeException(
                                'Unsupported CBOR additional'
                                . ' info.'
                            );
                    }
                }
                return $unsigned === PHP_INT_MAX ? PHP_INT_MIN : -1 - $unsigned;

            case self::MAJOR_BYTESTRING:
                if ($info === self::INFO_INDEFINITE) {
                    $chunks = '';
                    while (true) {
                        if ($this->idx < $payloadLength
                            && \ord($payload[$this->idx]) === 0xFF
                        ) {
                            $this->idx++;
                            return $chunks;
                        }
                        $chunks .= $this->readByteString();
                    }
                }
                $length = $info;
                if ($info >= self::INFO_ONE_BYTE) {
                    switch ($info) {
                        case self::INFO_ONE_BYTE:
                            if ($this->idx >= $payloadLength) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $length = \ord(
                                $payload[$this->idx++]
                            );
                            break;

                        case self::INFO_TWO_BYTES:
                            if ($this->idx + 2 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $length = (
                                \ord($payload[$idx]) << 8
                            )
                                | \ord($payload[$idx + 1]);
                            $this->idx = $idx + 2;
                            break;

                        case self::INFO_FOUR_BYTES:
                            if ($this->idx + 4 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $length = (
                                \ord($payload[$idx]) << 24
                            )
                                | (
                                    \ord($payload[$idx + 1]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 2]) << 8
                                )
                                | \ord($payload[$idx + 3]);
                            $this->idx = $idx + 4;
                            break;

                        case self::INFO_EIGHT_BYTES:
                            if ($this->idx + 8 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $high = (\ord($payload[$idx]) << 24)
                                | (
                                    \ord($payload[$idx + 1]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 2]) << 8
                                )
                                | \ord($payload[$idx + 3]);
                            if ($high > 0x7FFF_FFFF) {
                                throw new \RuntimeException(
                                    'CBOR uint64 exceeds PHP'
                                    . ' int range.'
                                );
                            }
                            $low = (
                                \ord($payload[$idx + 4]) << 24
                            )
                                | (
                                    \ord($payload[$idx + 5]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 6]) << 8
                                )
                                | \ord($payload[$idx + 7]);
                            $length = ($high << 32) | $low;
                            $this->idx = $idx + 8;
                            break;

                        default:
                            throw new \RuntimeException(
                                'Unsupported CBOR additional'
                                . ' info.'
                            );
                    }
                }
                if ($this->idx + $length > $payloadLength) {
                    throw new \RuntimeException(
                    'Malformed CBOR byte string length.'
                    );
                }
                $start = $this->idx;
                $this->idx += $length;
                return \substr($payload, $start, $length);

            case self::MAJOR_TEXTSTRING:
                if ($info === self::INFO_INDEFINITE) {
                    $chunks = '';
                    while (true) {
                        if ($this->idx < $payloadLength
                            && \ord($payload[$this->idx]) === 0xFF
                        ) {
                            $this->idx++;
                            return $chunks;
                        }
                        $chunks .= $this->readTextString();
                    }
                }
                $length = $info;
                if ($info >= self::INFO_ONE_BYTE) {
                    switch ($info) {
                        case self::INFO_ONE_BYTE:
                            if ($this->idx >= $payloadLength) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $length = \ord(
                                $payload[$this->idx++]
                            );
                            break;

                        case self::INFO_TWO_BYTES:
                            if ($this->idx + 2 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $length = (
                                \ord($payload[$idx]) << 8
                            )
                                | \ord($payload[$idx + 1]);
                            $this->idx = $idx + 2;
                            break;

                        case self::INFO_FOUR_BYTES:
                            if ($this->idx + 4 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $length = (
                                \ord($payload[$idx]) << 24
                            )
                                | (
                                    \ord($payload[$idx + 1]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 2]) << 8
                                )
                                | \ord($payload[$idx + 3]);
                            $this->idx = $idx + 4;
                            break;

                        case self::INFO_EIGHT_BYTES:
                            if ($this->idx + 8 > $payloadLength
                            ) {
                                throw new \RuntimeException(
                                    'Unexpected end of CBOR'
                                    . ' payload.'
                                );
                            }
                            $idx = $this->idx;
                            $high = (\ord($payload[$idx]) << 24)
                                | (
                                    \ord($payload[$idx + 1]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 2]) << 8
                                )
                                | \ord($payload[$idx + 3]);
                            if ($high > 0x7FFF_FFFF) {
                                throw new \RuntimeException(
                                    'CBOR uint64 exceeds PHP'
                                    . ' int range.'
                                );
                            }
                            $low = (
                                \ord($payload[$idx + 4]) << 24
                            )
                                | (
                                    \ord($payload[$idx + 5]) << 16
                                )
                                | (
                                    \ord($payload[$idx + 6]) << 8
                                )
                                | \ord($payload[$idx + 7]);
                            $length = ($high << 32) | $low;
                            $this->idx = $idx + 8;
                            break;

                        default:
                            throw new \RuntimeException(
                                'Unsupported CBOR additional'
                                . ' info.'
                            );
                    }
                }
                if ($this->idx + $length > $payloadLength) {
                    throw new \RuntimeException(
                    'Malformed CBOR text string length.'
                    );
                }
                $start = $this->idx;
                $this->idx += $length;
                return \substr($payload, $start, $length);

            case self::MAJOR_ARRAY:
                $indefinite = $info === self::INFO_INDEFINITE;
                $remaining = -1;
                if (!$indefinite) {
                    $remaining = $info;
                    if ($info >= self::INFO_ONE_BYTE) {
                        switch ($info) {
                            case self::INFO_ONE_BYTE:
                                if ($this->idx >= $payloadLength) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $remaining = \ord($payload[$this->idx++]);
                                break;

                            case self::INFO_TWO_BYTES:
                                if ($this->idx + 2 > $payloadLength
                                ) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $remaining = (\ord($payload[$idx]) << 8)
                                    | \ord($payload[$idx + 1]);
                                $this->idx = $idx + 2;
                                break;

                            case self::INFO_FOUR_BYTES:
                                if ($this->idx + 4 > $payloadLength
                                ) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $remaining = (\ord($payload[$idx]) << 24)
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                $this->idx = $idx + 4;
                                break;

                            case self::INFO_EIGHT_BYTES:
                                if ($this->idx + 8 > $payloadLength
                                ) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $high = (\ord($payload[$idx]) << 24)
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                if ($high > 0x7FFF_FFFF) {
                                    throw new \RuntimeException(
                                        'CBOR uint64 exceeds PHP'
                                        . ' int range.'
                                    );
                                }
                                $low = (
                                    \ord($payload[$idx + 4]) << 24
                                )
                                    | (
                                        \ord($payload[$idx + 5]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 6]) << 8
                                    )
                                    | \ord($payload[$idx + 7]);
                                $remaining = ($high << 32) | $low;
                                $this->idx = $idx + 8;
                                break;

                            default:
                                throw new \RuntimeException(
                                    'Unsupported CBOR additional'
                                    . ' info.'
                                );
                        }
                    }
                }
                $out = [];
                while (true) {
                    if ($indefinite
                        && $this->idx < $payloadLength
                        && \ord($payload[$this->idx]) === 0xFF
                    ) {
                        $this->idx++;
                        return $out;
                    }
                    if (!$indefinite && $remaining-- <= 0) {
                        return $out;
                    }
                    $out[] = $this->readUntypedValue();
                }

            case self::MAJOR_MAP:
                $indefinite = $info === self::INFO_INDEFINITE;
                $remaining = -1;
                if (!$indefinite) {
                    $remaining = $info;
                    if ($info >= self::INFO_ONE_BYTE) {
                        switch ($info) {
                            case self::INFO_ONE_BYTE:
                                if ($this->idx >= $payloadLength) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $remaining = \ord($payload[$this->idx++]);
                                break;

                            case self::INFO_TWO_BYTES:
                                if ($this->idx + 2 > $payloadLength
                                ) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $remaining = (\ord($payload[$idx]) << 8)
                                    | \ord($payload[$idx + 1]);
                                $this->idx = $idx + 2;
                                break;

                            case self::INFO_FOUR_BYTES:
                                if ($this->idx + 4 > $payloadLength
                                ) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $remaining = (\ord($payload[$idx]) << 24)
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                $this->idx = $idx + 4;
                                break;

                            case self::INFO_EIGHT_BYTES:
                                if ($this->idx + 8 > $payloadLength
                                ) {
                                    throw new \RuntimeException(
                                        'Unexpected end of CBOR'
                                        . ' payload.'
                                    );
                                }
                                $idx = $this->idx;
                                $high = (\ord($payload[$idx]) << 24)
                                    | (
                                        \ord($payload[$idx + 1]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 2]) << 8
                                    )
                                    | \ord($payload[$idx + 3]);
                                if ($high > 0x7FFF_FFFF) {
                                    throw new \RuntimeException(
                                        'CBOR uint64 exceeds PHP'
                                        . ' int range.'
                                    );
                                }
                                $low = (
                                    \ord($payload[$idx + 4]) << 24
                                )
                                    | (
                                        \ord($payload[$idx + 5]) << 16
                                    )
                                    | (
                                        \ord($payload[$idx + 6]) << 8
                                    )
                                    | \ord($payload[$idx + 7]);
                                $remaining = ($high << 32) | $low;
                                $this->idx = $idx + 8;
                                break;

                            default:
                                throw new \RuntimeException(
                                    'Unsupported CBOR additional'
                                    . ' info.'
                                );
                        }
                    }
                }
                $out = [];
                while (true) {
                    if ($indefinite
                        && $this->idx < $payloadLength
                        && \ord($payload[$this->idx]) === 0xFF
                    ) {
                        $this->idx++;
                        return $out;
                    }
                    if (!$indefinite && $remaining-- <= 0) {
                        return $out;
                    }
                    $key = $this->readUntypedValue();
                    $out[(string) $key] = $this->readUntypedValue();
                }

            case self::MAJOR_TAG:
                switch ($info) {
                    case self::TAG_EPOCH:
                        if ($this->idx >= $payloadLength) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }

                        $next = \ord($payload[$this->idx++]);
                        $timestampMajor = $next >> 5;
                        $timestampInfo = $next & 0x1F;

                        if ($timestampMajor !== self::MAJOR_POSINT
                            && $timestampMajor !== self::MAJOR_NEGINT
                        ) {
                            if ($timestampMajor === self::MAJOR_SIMPLE) {
                                switch ($timestampInfo) {
                                    case self::SIMPLE_HALF_FLOAT:
                                        return $this->readHalfFloat();
                                    case self::SIMPLE_FLOAT:
                                        if ($this->idx + 4 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $value = \unpack(
                                            'G',
                                            $payload,
                                            $idx
                                        )[1];
                                        $this->idx = $idx + 4;
                                        return (float) $value;
                                    case self::SIMPLE_DOUBLE:
                                        if ($this->idx + 8 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $value = \unpack(
                                            'E',
                                            $payload,
                                            $idx
                                        )[1];
                                        $this->idx = $idx + 8;
                                        return $value;
                                }
                            }
                            throw new \RuntimeException(
                            'Malformed CBOR epoch timestamp payload.'
                            );
                        }

                        $unsigned = $timestampInfo;
                        if ($timestampInfo >= self::INFO_ONE_BYTE) {
                            switch ($timestampInfo) {
                                case self::INFO_ONE_BYTE:
                                    if ($this->idx >= $payloadLength) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $unsigned = \ord($payload[$this->idx++]);
                                    break;

                                case self::INFO_TWO_BYTES:
                                    if ($this->idx + 2 > $payloadLength
                                    ) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $idx = $this->idx;
                                    $unsigned = (\ord($payload[$idx]) << 8)
                                        | \ord($payload[$idx + 1]);
                                    $this->idx = $idx + 2;
                                    break;

                                case self::INFO_FOUR_BYTES:
                                    if ($this->idx + 4 > $payloadLength
                                    ) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $idx = $this->idx;
                                    $unsigned = (\ord($payload[$idx]) << 24)
                                        | (
                                            \ord($payload[$idx + 1]) << 16
                                        )
                                        | (
                                            \ord($payload[$idx + 2]) << 8
                                        )
                                        | \ord($payload[$idx + 3]);
                                    $this->idx = $idx + 4;
                                    break;

                                case self::INFO_EIGHT_BYTES:
                                    if ($this->idx + 8 > $payloadLength
                                    ) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $idx = $this->idx;
                                    $high = (\ord($payload[$idx]) << 24)
                                        | (
                                            \ord($payload[$idx + 1]) << 16
                                        )
                                        | (
                                            \ord($payload[$idx + 2]) << 8
                                        )
                                        | \ord($payload[$idx + 3]);
                                    if ($high > 0x7FFF_FFFF) {
                                        throw new \RuntimeException(
                                            'CBOR uint64 exceeds PHP'
                                            . ' int range.'
                                        );
                                    }
                                    $low = (
                                        \ord($payload[$idx + 4]) << 24
                                    )
                                        | (
                                            \ord($payload[$idx + 5]) << 16
                                        )
                                        | (
                                            \ord($payload[$idx + 6]) << 8
                                        )
                                        | \ord($payload[$idx + 7]);
                                    $unsigned = ($high << 32) | $low;
                                    $this->idx = $idx + 8;
                                    break;

                                default:
                                    throw new \RuntimeException(
                                        'Unsupported CBOR additional'
                                        . ' info.'
                                    );
                            }
                        }

                        if ($timestampMajor === self::MAJOR_POSINT) {
                            return (float) $unsigned;
                        }
                        return $unsigned === PHP_INT_MAX
                            ? (float) PHP_INT_MIN
                            : (float) (-1 - $unsigned);

                    case self::TAG_POS_BIGINT:
                        if ($this->idx >= $payloadLength) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $initial = \ord($payload[$this->idx++]);
                        $major = $initial >> 5;
                        $byteInfo = $initial & 0x1F;
                        if ($major !== self::MAJOR_BYTESTRING) {
                            throw new \RuntimeException(
                            'Expected CBOR byte string.'
                            );
                        }
                        if ($byteInfo === self::INFO_INDEFINITE) {
                            $bytes = '';
                            while (true) {
                                if ($this->idx < $payloadLength
                                    && \ord($payload[$this->idx]) === 0xFF
                                ) {
                                    $this->idx++;
                                    break;
                                }
                                $bytes .= $this->readByteString();
                            }
                        } else {
                            $length = $byteInfo;
                            if ($byteInfo >= self::INFO_ONE_BYTE) {
                                switch ($byteInfo) {
                                    case self::INFO_ONE_BYTE:
                                        if ($this->idx >= $payloadLength) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $length = \ord(
                                            $payload[$this->idx++]
                                        );
                                        break;
                                    case self::INFO_TWO_BYTES:
                                        if ($this->idx + 2 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $length = (
                                            \ord($payload[$idx]) << 8
                                        )
                                            | \ord($payload[$idx + 1]);
                                        $this->idx = $idx + 2;
                                        break;
                                    case self::INFO_FOUR_BYTES:
                                        if ($this->idx + 4 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $length = (
                                            \ord($payload[$idx]) << 24
                                        )
                                            | (
                                                \ord($payload[$idx + 1]) << 16
                                            )
                                            | (
                                                \ord($payload[$idx + 2]) << 8
                                            )
                                            | \ord($payload[$idx + 3]);
                                        $this->idx = $idx + 4;
                                        break;
                                    case self::INFO_EIGHT_BYTES:
                                        if ($this->idx + 8 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $high = (\ord($payload[$idx]) << 24)
                                            | (
                                                \ord($payload[$idx + 1]) << 16
                                            )
                                            | (
                                                \ord($payload[$idx + 2]) << 8
                                            )
                                            | \ord($payload[$idx + 3]);
                                        if ($high > 0x7FFF_FFFF) {
                                            throw new \RuntimeException(
                                                'CBOR uint64 exceeds PHP'
                                                . ' int range.'
                                            );
                                        }
                                        $low = (
                                            \ord($payload[$idx + 4]) << 24
                                        )
                                            | (
                                                \ord($payload[$idx + 5]) << 16
                                            )
                                            | (
                                                \ord($payload[$idx + 6]) << 8
                                            )
                                            | \ord($payload[$idx + 7]);
                                        $length = ($high << 32) | $low;
                                        $this->idx = $idx + 8;
                                        break;
                                    default:
                                        throw new \RuntimeException(
                                            'Unsupported CBOR additional'
                                            . ' info.'
                                        );
                                }
                            }
                            if ($this->idx + $length > $payloadLength) {
                                throw new \RuntimeException(
                                'Malformed CBOR byte string length.'
                                );
                            }
                            $start = $this->idx;
                            $this->idx += $length;
                            $bytes = \substr($payload, $start, $length);
                        }
                        $result = '0';
                        $length = \strlen($bytes);
                        for ($i = 0; $i < $length; $i++) {
                            $result =
                                $this->multiplyUnsignedDecimalStringBySmall(
                            $result,
                            256
                            );
                            $result = $this->addSmallToUnsignedDecimalString(
                                $result,
                                \ord($bytes[$i])
                            );
                        }
                        return new BigInteger($result);

                    case self::TAG_NEG_BIGINT:
                        if ($this->idx >= $payloadLength) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $initial = \ord($payload[$this->idx++]);
                        $major = $initial >> 5;
                        $byteInfo = $initial & 0x1F;
                        if ($major !== self::MAJOR_BYTESTRING) {
                            throw new \RuntimeException(
                            'Expected CBOR byte string.'
                            );
                        }
                        if ($byteInfo === self::INFO_INDEFINITE) {
                            $bytes = '';
                            while (true) {
                                if ($this->idx < $payloadLength
                                    && \ord($payload[$this->idx]) === 0xFF
                                ) {
                                    $this->idx++;
                                    break;
                                }
                                $bytes .= $this->readByteString();
                            }
                        } else {
                            $length = $byteInfo;
                            if ($byteInfo >= self::INFO_ONE_BYTE) {
                                switch ($byteInfo) {
                                    case self::INFO_ONE_BYTE:
                                        if ($this->idx >= $payloadLength) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $length = \ord(
                                            $payload[$this->idx++]
                                        );
                                        break;
                                    case self::INFO_TWO_BYTES:
                                        if ($this->idx + 2 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $length = (
                                            \ord($payload[$idx]) << 8
                                        )
                                            | \ord($payload[$idx + 1]);
                                        $this->idx = $idx + 2;
                                        break;
                                    case self::INFO_FOUR_BYTES:
                                        if ($this->idx + 4 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $length = (
                                            \ord($payload[$idx]) << 24
                                        )
                                            | (
                                                \ord($payload[$idx + 1]) << 16
                                            )
                                            | (
                                                \ord($payload[$idx + 2]) << 8
                                            )
                                            | \ord($payload[$idx + 3]);
                                        $this->idx = $idx + 4;
                                        break;
                                    case self::INFO_EIGHT_BYTES:
                                        if ($this->idx + 8 > $payloadLength
                                        ) {
                                            throw new \RuntimeException(
                                                'Unexpected end of CBOR'
                                                . ' payload.'
                                            );
                                        }
                                        $idx = $this->idx;
                                        $high = (\ord($payload[$idx]) << 24)
                                            | (
                                                \ord($payload[$idx + 1]) << 16
                                            )
                                            | (
                                                \ord($payload[$idx + 2]) << 8
                                            )
                                            | \ord($payload[$idx + 3]);
                                        if ($high > 0x7FFF_FFFF) {
                                            throw new \RuntimeException(
                                                'CBOR uint64 exceeds PHP'
                                                . ' int range.'
                                            );
                                        }
                                        $low = (
                                            \ord($payload[$idx + 4]) << 24
                                        )
                                            | (
                                                \ord($payload[$idx + 5]) << 16
                                            )
                                            | (
                                                \ord($payload[$idx + 6]) << 8
                                            )
                                            | \ord($payload[$idx + 7]);
                                        $length = ($high << 32) | $low;
                                        $this->idx = $idx + 8;
                                        break;
                                    default:
                                        throw new \RuntimeException(
                                            'Unsupported CBOR additional'
                                            . ' info.'
                                        );
                                }
                            }
                            if ($this->idx + $length > $payloadLength) {
                                throw new \RuntimeException(
                                'Malformed CBOR byte string length.'
                                );
                            }
                            $start = $this->idx;
                            $this->idx += $length;
                            $bytes = \substr($payload, $start, $length);
                        }
                        $result = '0';
                        $length = \strlen($bytes);
                        for ($i = 0; $i < $length; $i++) {
                            $result =
                                $this->multiplyUnsignedDecimalStringBySmall(
                            $result,
                            256
                            );
                            $result = $this->addSmallToUnsignedDecimalString(
                                $result,
                                \ord($bytes[$i])
                            );
                        }
                        return new BigInteger(
                        '-' . $this->incrementUnsignedDecimalString($result)
                        );

                    case self::TAG_DECIMAL:
                        if ($this->idx >= $payloadLength) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $next = \ord($payload[$this->idx++]);
                        if (($next >> 5) !== self::MAJOR_ARRAY) {
                            throw new \RuntimeException(
                            'Malformed CBOR decimal-fraction payload.'
                            );
                        }
                        $nextInfo = $next & 0x1F;
                        if ($nextInfo === self::INFO_INDEFINITE) {
                            throw new \RuntimeException(
                            'Malformed CBOR decimal-fraction payload.'
                            );
                        }
                        $remaining = $nextInfo;
                        if ($nextInfo >= self::INFO_ONE_BYTE) {
                            switch ($nextInfo) {
                                case self::INFO_ONE_BYTE:
                                    if ($this->idx >= $payloadLength) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $remaining = \ord($payload[$this->idx++]);
                                    break;

                                case self::INFO_TWO_BYTES:
                                    if ($this->idx + 2 > $payloadLength
                                    ) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $idx = $this->idx;
                                    $remaining = (\ord($payload[$idx]) << 8)
                                        | \ord($payload[$idx + 1]);
                                    $this->idx = $idx + 2;
                                    break;

                                case self::INFO_FOUR_BYTES:
                                    if ($this->idx + 4 > $payloadLength
                                    ) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $idx = $this->idx;
                                    $remaining = (\ord($payload[$idx]) << 24)
                                        | (
                                            \ord($payload[$idx + 1]) << 16
                                        )
                                        | (
                                            \ord($payload[$idx + 2]) << 8
                                        )
                                        | \ord($payload[$idx + 3]);
                                    $this->idx = $idx + 4;
                                    break;

                                case self::INFO_EIGHT_BYTES:
                                    if ($this->idx + 8 > $payloadLength
                                    ) {
                                        throw new \RuntimeException(
                                            'Unexpected end of CBOR'
                                            . ' payload.'
                                        );
                                    }
                                    $idx = $this->idx;
                                    $high = (\ord($payload[$idx]) << 24)
                                        | (
                                            \ord($payload[$idx + 1]) << 16
                                        )
                                        | (
                                            \ord($payload[$idx + 2]) << 8
                                        )
                                        | \ord($payload[$idx + 3]);
                                    if ($high > 0x7FFF_FFFF) {
                                        throw new \RuntimeException(
                                            'CBOR uint64 exceeds PHP'
                                            . ' int range.'
                                        );
                                    }
                                    $low = (
                                        \ord($payload[$idx + 4]) << 24
                                    )
                                        | (
                                            \ord($payload[$idx + 5]) << 16
                                        )
                                        | (
                                            \ord($payload[$idx + 6]) << 8
                                        )
                                        | \ord($payload[$idx + 7]);
                                    $remaining = ($high << 32) | $low;
                                    $this->idx = $idx + 8;
                                    break;

                                default:
                                    throw new \RuntimeException(
                                        'Unsupported CBOR additional'
                                        . ' info.'
                                    );
                            }
                        }
                        if ($remaining !== 2) {
                            throw new \RuntimeException(
                            'Malformed CBOR decimal-fraction payload.'
                            );
                        }
                        $exponent = $this->readBigInteger()->value;
                        $mantissa = $this->readBigInteger()->value;
                        return new BigDecimal(
                            $exponent === '0'
                                ? $mantissa
                                : $mantissa . 'e' . $exponent
                        );
                }
                return $this->readUntypedValue();

            case self::MAJOR_SIMPLE:
                switch ($info) {
                    case self::SIMPLE_FALSE:
                        return false;

                    case self::SIMPLE_TRUE:
                        return true;

                    case self::SIMPLE_NULL:
                    case self::SIMPLE_UNDEFINED:
                        return null;

                    case self::SIMPLE_HALF_FLOAT:
                        return $this->readHalfFloat();

                    case self::SIMPLE_FLOAT:
                        if ($this->idx + 4 > $payloadLength
                        ) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $value = \unpack(
                            'G',
                            $payload,
                            $idx
                        )[1];
                        $this->idx = $idx + 4;
                        return $value;

                    case self::SIMPLE_DOUBLE:
                        if ($this->idx + 8 > $payloadLength
                        ) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $idx = $this->idx;
                        $value = \unpack(
                            'E',
                            $payload,
                            $idx
                        )[1];
                        $this->idx = $idx + 8;
                        return $value;
                }
                throw new \RuntimeException('Unsupported CBOR simple value.');
        }

        throw new \RuntimeException('Unsupported CBOR major type.');
    }

    /**
     * Skip a CBOR value without decoding it.
     *
     * @return void
     */
    private function skipValue(): void
    {
        if ($this->idx >= $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $payload = $this->payload;
        $initial = \ord($payload[$this->idx++]);
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        switch ($major) {
            case self::MAJOR_POSINT:
            case self::MAJOR_NEGINT:
                $this->skipLengthValue($info);
                return;

            case self::MAJOR_BYTESTRING:
            case self::MAJOR_TEXTSTRING:
                if ($info === self::INFO_INDEFINITE) {
                    while (!(
                        $this->idx < $this->length
                        && \ord($payload[$this->idx]) === 0xFF
                    )) {
                        $this->skipValue();
                    }
                    $this->idx++;
                    return;
                }
                $bytes = $this->readLengthFromInfo($info);
                if ($this->idx + $bytes > $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $this->idx += $bytes;
                return;

            case self::MAJOR_ARRAY:
                $indefinite = $info === self::INFO_INDEFINITE;
                $remaining = $indefinite
                    ? -1
                    : $this->readLengthFromInfo($info);
                while (true) {
                    if ($indefinite
                        && $this->idx < $this->length
                        && \ord($payload[$this->idx]) === 0xFF
                    ) {
                        $this->idx++;
                        return;
                    }
                    if (!$indefinite && $remaining-- <= 0) {
                        return;
                    }
                    $this->skipValue();
                }

            case self::MAJOR_MAP:
                $indefinite = $info === self::INFO_INDEFINITE;
                $remaining = $indefinite
                    ? -1
                    : $this->readLengthFromInfo($info);
                while (true) {
                    if ($indefinite
                        && $this->idx < $this->length
                        && \ord($payload[$this->idx]) === 0xFF
                    ) {
                        $this->idx++;
                        return;
                    }
                    if (!$indefinite && $remaining-- <= 0) {
                        return;
                    }
                    $this->skipValue();
                    $this->skipValue();
                }

            case self::MAJOR_TAG:
                $this->skipValue();
                return;

            case self::MAJOR_SIMPLE:
                switch ($info) {
                    case self::SIMPLE_HALF_FLOAT:
                        if ($this->idx + 2 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $this->idx += 2;
                        break;

                    case self::SIMPLE_FLOAT:
                        if ($this->idx + 4 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $this->idx += 4;
                        break;

                    case self::SIMPLE_DOUBLE:
                        if ($this->idx + 8 > $this->length) {
                            throw new \RuntimeException(
                                'Unexpected end of CBOR'
                                . ' payload.'
                            );
                        }
                        $this->idx += 8;
                        break;
                }
                return;
        }

        throw new \RuntimeException('Unsupported CBOR value while skipping.');
    }

    /**
     * Read a CBOR half-precision float.
     */
    private function readHalfFloat(): float
    {
        if ($this->idx + 2 > $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $bits = (\ord($this->payload[$this->idx]) << 8)
            | \ord($this->payload[$this->idx + 1]);
        $this->idx += 2;
        $sign = ($bits >> 15) & 0x01;
        $exp = ($bits >> 10) & 0x1F;
        $mant = $bits & 0x3FF;

        return match ($exp) {
            0 => $mant === 0
                ? ($sign ? -0.0 : 0.0)
                : ($sign ? -1 : 1) * (2 ** -14) * ($mant / 1024),
            31 => $mant === 0 ? ($sign ? -INF : INF) : NAN,
            default => (float) (
                ($sign ? -1 : 1)
                * (2 ** ($exp - 15))
                * (1 + $mant / 1024)
            ),
        };
    }

    /**
     * Read a CBOR length from an additional-info value.
     */
    private function readLengthFromInfo(int $info): int
    {
        if ($info < self::INFO_ONE_BYTE) {
            return $info;
        }

        switch ($info) {
            case self::INFO_ONE_BYTE:
                if ($this->idx >= $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                return \ord($this->payload[$this->idx++]);

            case self::INFO_TWO_BYTES:
                if ($this->idx + 2 > $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $value = (\ord($this->payload[$this->idx]) << 8)
                    | \ord($this->payload[$this->idx + 1]);
                $this->idx += 2;
                return $value;

            case self::INFO_FOUR_BYTES:
                if ($this->idx + 4 > $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $idx = $this->idx;
                $this->idx = $idx + 4;
                return (\ord($this->payload[$idx]) << 24)
                    | (\ord($this->payload[$idx + 1]) << 16)
                    | (\ord($this->payload[$idx + 2]) << 8)
                    | \ord($this->payload[$idx + 3]);

            case self::INFO_EIGHT_BYTES:
                if ($this->idx + 8 > $this->length) {
                    throw new \RuntimeException(
                        'Unexpected end of CBOR'
                        . ' payload.'
                    );
                }
                $idx = $this->idx;
                $this->idx = $idx + 8;
                $high = (\ord($this->payload[$idx]) << 24)
                    | (\ord($this->payload[$idx + 1]) << 16)
                    | (\ord($this->payload[$idx + 2]) << 8)
                    | \ord($this->payload[$idx + 3]);
                if ($high > 0x7FFF_FFFF) {
                    throw new \RuntimeException(
                        'CBOR uint64 exceeds PHP'
                        . ' int range.'
                    );
                }
                $low = (\ord($this->payload[$idx + 4]) << 24)
                    | (\ord($this->payload[$idx + 5]) << 16)
                    | (\ord($this->payload[$idx + 6]) << 8)
                    | \ord($this->payload[$idx + 7]);
                return ($high << 32) | $low;
        }

        throw new \RuntimeException(
            'Unsupported CBOR additional'
            . ' info.'
        );
    }

    /**
     * Skip a fixed-width length payload.
     *
     * @return void
     */
    private function skipLengthValue(int $info): void
    {
        if ($info < self::INFO_ONE_BYTE) {
            return;
        }

        $bytes = match ($info) {
            self::INFO_ONE_BYTE => 1,
            self::INFO_TWO_BYTES => 2,
            self::INFO_FOUR_BYTES => 4,
            self::INFO_EIGHT_BYTES => 8,
            default => throw new \RuntimeException(
                'Unsupported CBOR integer additional info.'
            ),
        };

        if ($this->idx + $bytes > $this->length) {
            throw new \RuntimeException(
                'Unexpected end of CBOR'
                . ' payload.'
            );
        }
        $this->idx += $bytes;
    }

    /**
     * Add one to an unsigned decimal string.
     */
    private function incrementUnsignedDecimalString(string $value): string
    {
        $carry = 1;
        $result = '';
        for ($i = \strlen($value) - 1; $i >= 0; $i--) {
            $digit = (\ord($value[$i]) - 48) + $carry;
            if ($digit >= 10) {
                $digit -= 10;
                $carry = 1;
            } else {
                $carry = 0;
            }
            $result = \chr(48 + $digit) . $result;
        }
        if ($carry !== 0) {
            $result = '1' . $result;
        }
        return $result;
    }

    /**
     * Multiply an unsigned decimal string by a small integer.
     */
    private function multiplyUnsignedDecimalStringBySmall(string $value, int $factor): string
    {
        if ($value === '0') {
            return '0';
        }
        $carry = 0;
        $result = '';
        for ($i = \strlen($value) - 1; $i >= 0; $i--) {
            $product = ((\ord($value[$i]) - 48) * $factor) + $carry;
            $result = \chr(48 + ($product % 10)) . $result;
            $carry = \intdiv($product, 10);
        }
        while ($carry !== 0) {
            $result = \chr(48 + ($carry % 10)) . $result;
            $carry = \intdiv($carry, 10);
        }
        return $result;
    }

    /**
     * Add a small integer to an unsigned decimal string.
     */
    private function addSmallToUnsignedDecimalString(string $value, int $addend): string
    {
        $carry = $addend;
        $result = '';
        for ($i = \strlen($value) - 1; $i >= 0; $i--) {
            $sum = (\ord($value[$i]) - 48) + ($carry % 10);
            $carry = \intdiv($carry, 10);
            if ($sum >= 10) {
                $sum -= 10;
                $carry++;
            }
            $result = \chr(48 + $sum) . $result;
        }
        while ($carry !== 0) {
            $result = \chr(48 + ($carry % 10)) . $result;
            $carry = \intdiv($carry, 10);
        }
        return \ltrim($result, '0') ?: '0';
    }
}
