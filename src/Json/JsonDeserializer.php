<?php

declare(strict_types=1);

namespace Smithy\Json;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableShapeInterface;
use Smithy\Core\Serde\ShapeDeserializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\BigDecimal;
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\Document\AbstractDocument;

/**
 * Deserialize JSON into Smithy shapes.
 */
final class JsonDeserializer implements ShapeDeserializerInterface
{
    /**
     * Decoded wire body.
     */
    private mixed $parsed;

    /**
     * Honor `smithy.api#jsonName` when decoding member names.
     */
    private readonly bool $honorJsonName;

    public function __construct(string $source, bool $honorJsonName = true)
    {
        $this->honorJsonName = $honorJsonName;
        if ($source === '') {
            $this->parsed = null;
            return;
        }
        $decoded = \json_decode($source, true, 512, \JSON_BIGINT_AS_STRING);
        if ($decoded === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'JSON deserialization failed: ' . \json_last_error_msg()
            );
        }
        $this->parsed = $decoded;
    }

    /**
     * Deserialize the parsed wire body into positional schema members.
     *
     * @param array<int, mixed> $members
     *
     * @return void
     */
    public function deserialize(Schema $schema, array &$members): void
    {
        if ($this->parsed === null) {
            return;
        }
        // @httpPayload unions treat the whole document as the union value.
        if ($schema->type === ShapeType::Union->value) {
            if (\is_array($this->parsed) && $this->parsed !== []) {
                $member = new Schema(
                    id: $schema->id,
                    type: ShapeType::Union,
                    members: [],
                    targetClass: $schema->targetClass
                );
                $members[0] = $this->readUnion($member, $this->parsed);
            }
            return;
        }

        if (!\is_array($this->parsed)) {
            // Only JSON objects can populate a struct.
            return;
        }
        $this->readStruct($schema, $this->parsed, $members);
    }

    /**
     * Walk a JSON object into the positional `$members` array for `$schema`.
     *
     * @param array<string, mixed> $object Decoded JSON object.
     * @param array<int, mixed>    $members Positional output, modified in place.
     */
    private function readStruct(Schema $schema, array $object, array &$members): void
    {
        // Cache the extension key in the hot path.
        static $extKey = null;
        $extKey ??= JsonSchemaExtensionProvider::key();
        /** @var JsonSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);
        if ($info === null) {
            return;
        }

        // Pick the wire-name tables once per struct.
        $ordered = $this->honorJsonName
            ? $info->orderedMembers
            : $info->rawOrderedMembers;
        $memberDecodeByWireName = $this->honorJsonName
            ? $info->memberDecodeByWireName
            : $info->rawMemberDecodeByWireName;
        // Favor in-order keys, then fall back to the hash lookup.
        $orderedCount = $info->orderedMemberCount;
        $hint = 0;
        foreach ($object as $key => $value) {
            // Fast path for in-order keys.
            if ($hint < $orderedCount && $ordered[$hint][0] === $key) {
                $entry = $ordered[$hint][1];
                $hint++;
            } else {
                // Ignore unknown wire keys.
                $entry = $memberDecodeByWireName[$key] ?? null;
                if ($entry === null) {
                    continue;
                }
                // Nudge the hint forward after an out-of-order match.
                $scanLimit = $hint + 4 < $orderedCount
                    ? $hint + 4
                    : $orderedCount;
                for ($i = $hint; $i < $scanLimit; $i++) {
                    if ($ordered[$i][0] === $key) {
                        $hint = $i + 1;
                        break;
                    }
                }
            }

            $idx = $entry[0];
            if ($value === null) {
                $members[$idx] = null;
                continue;
            }

            $members[$idx] = $this->readValue(
                $entry[1],
                $value,
                $entry[2] ?? null
            );
        }
    }
    /**
     * Hydrate a nested struct.
     *
     * Uses the prebuilt member template when available. Resolve the
     * target only for stub schemas.
     */
    private function readNestedStruct(
        Schema $member,
        mixed $value
    ): ?SerializableShapeInterface
    {
        if (!\is_array($value)) {
            return null;
        }
        static $extKey = null;
        $extKey ??= JsonSchemaExtensionProvider::key();

        // Resolve the target only for stub schemas.
        /** @var JsonSchemaExtension|null $info */
        $info = $member->getExtension($extKey);
        if ($info === null || $info->memberCount === 0) {
            $targetSchema = $member->resolveTarget();
            $info = $targetSchema->getExtension($extKey);
        } else {
            $targetSchema = $member;
        }

        // Fall back to a fresh member array if no extension is present.
        if ($info !== null) {
            $members = $info->membersTemplate;
        } else {
            $count = $targetSchema->getMemberCount();
            $members = $count > 0 ? \array_fill(0, $count, null) : [];
        }
        $this->readStruct($targetSchema, $value, $members);
        return $targetSchema->targetClass
            ? ($targetSchema->targetClass)::fromMembers($members)
            : null;
    }



    /**
     * Hydrate a union wire value.
     */
    private function readUnion(Schema $member, mixed $value): ?SerializableShapeInterface
    {
        if (!\is_array($value) || $value === []) {
            return null;
        }
        // Memoize target union extensions by schema id.
        static $extKey = null;
        static $targetInfoById = [];
        $extKey ??= JsonSchemaExtensionProvider::key();
        /** @var JsonSchemaExtension|null $memberInfo */
        $memberInfo = $member->getExtension($extKey);
        if ($memberInfo === null) {
            // Synthetic schemas may not have precomputed extensions.
            $unionSchema = $member->resolveTarget();
            $targetClass = $unionSchema->targetClass;
            if ($targetClass === null) {
                return null;
            }
            $info = $unionSchema->getExtension($extKey);
        } else {
            $targetClass = $memberInfo->resolvedTargetClass;
            if ($targetClass === null) {
                return null;
            }
            $unionSchema = $memberInfo->resolvedTargetSchema ?? $member;
            // Canonical member schemas already carry the union tables.
            if ($unionSchema === $member) {
                $info = $memberInfo;
            } else {
                $tid = $unionSchema->id;
                $info = $targetInfoById[$tid]
                    ??= $unionSchema->getExtension($extKey);
            }
        }

        // Pick the union wire-name table once.
        $unionDecodeByWireName = $info === null
            ? []
            : ($this->honorJsonName
                ? $info->unionDecodeByWireName
                : $info->rawUnionDecodeByWireName);

        foreach ($value as $wireKey => $wireValue) {
            // Ignore restJson1's synthetic discriminator.
            if ($wireKey === '__type') {
                continue;
            }
            // Null union entries are absent variants.
            if ($wireValue === null) {
                continue;
            }
            $entry = $unionDecodeByWireName[$wireKey] ?? null;

            if ($entry === null) {
                return $targetClass::unknown((string) $wireKey, $wireValue);
            }

            return $targetClass::create(
                $entry[0],
                $this->readValue(
                    $entry[1],
                    $wireValue,
                    $entry[2] ?? null
                )
            );
        }
        return null;
    }

    /**
     * Read one wire value using a ShapeType-directed dispatch.
     */
    private function readValue(int $type, mixed $value, mixed $context = null): mixed
    {
        switch ($type) {
            case ShapeType::String_->value:
                return (string) $value;

            case ShapeType::Enum->value:
                return $context !== null
                    ? $context::fromValue((string) $value)
                    : (string) $value;

            case ShapeType::Boolean->value:
                return (bool) $value;

            case ShapeType::Integer->value:
            case ShapeType::Long->value:
            case ShapeType::Short->value:
            case ShapeType::Byte->value:
                return (int) $value;

            case ShapeType::IntEnum->value:
                $intValue = (int) $value;
                return $context !== null
                    ? $context::fromValue($intValue)
                    : $intValue;

            case ShapeType::Float_->value:
            case ShapeType::Double->value:
                return match ($value) {
                    'NaN' => \NAN,
                    'Infinity' => \INF,
                    '-Infinity' => -\INF,
                    default => (float) $value,
                };

            case ShapeType::BigInteger->value:
                return new BigInteger((string) $value);

            case ShapeType::BigDecimal->value:
                return new BigDecimal((string) $value);

            case ShapeType::Blob->value:
                return \base64_decode((string) $value, true) ?: '';

            case ShapeType::Timestamp->value:
                $fmt = $context ?? TimestampFormat::EpochSeconds;
                return $fmt === TimestampFormat::EpochSeconds
                    ? ((\is_int($value) || \is_float($value))
                        ? (float) $value
                        : (float) (string) $value)
                    : $fmt->parse((string) $value);

            case ShapeType::Structure->value:
                return $this->readNestedStruct($context, $value);

            case ShapeType::Union->value:
                return $this->readUnion($context, $value);

            case ShapeType::List_->value:
                return $this->readList($context, $value);

            case ShapeType::Map->value:
                return $this->readMap($context, $value);

            case ShapeType::Document->value:
                return AbstractDocument::fromValue($value);

            default:
                return $value;
        }
    }


    /**
     * Deserialize a JSON list.
     *
     * Uses extension metadata for the member type and timestamp format.
     */
    private function readList(Schema $member, mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }
        $itemSchema = $member->listMember;
        if ($itemSchema === null) {
            // Synthetic schemas without a member schema pass through.
            return $value;
        }

        static $extKey = null;
        $extKey ??= JsonSchemaExtensionProvider::key();
        /** @var JsonSchemaExtension|null $info */
        $info = $member->getExtension($extKey);
        // Synthetic schemas may not have extension metadata.
        $memberType = $info?->listMemberType ?? $itemSchema->type;

        $out = [];
        switch ($memberType) {
            case 10:  // ShapeType::String_
                foreach ($value as $item) {
                    $out[] = $item === null ? null : (string) $item;
                }
                break;

            case 4:  // ShapeType::Integer
            case 5:  // ShapeType::Long
            case 3: // ShapeType::Short
            case 2: // ShapeType::Byte
                foreach ($value as $item) {
                    $out[] = $item === null ? null : (int) $item;
                }
                break;

            case 1: // ShapeType::Boolean
                foreach ($value as $item) {
                    $out[] = $item === null ? null : (bool) $item;
                }
                break;

            case 6: // ShapeType::Float_
            case 7: // ShapeType::Double
                foreach ($value as $item) {
                    if ($item === null) {
                        $out[] = null;
                        continue;
                    }
                    $out[] = match ($item) {
                            'NaN' => \NAN,
                            'Infinity' => \INF,
                            '-Infinity' => -\INF,
                            default => (float) $item,
                        };
                }
                break;

            case 12: // ShapeType::Timestamp
                // Timestamp lists reuse the precomputed format.
                $fmt = $info?->listMemberTimestampFormat
                    ?? TimestampFormat::EpochSeconds;
                if ($fmt === TimestampFormat::EpochSeconds) {
                    foreach ($value as $item) {
                        if ($item === null) {
                            $out[] = null;
                            continue;
                        }
                        $out[] = \is_int($item) || \is_float($item)
                            ? (float) $item
                            : (float) (string) $item;
                    }
                } else {
                    foreach ($value as $item) {
                        $out[] = $item === null
                            ? null
                            : $fmt->parse((string) $item);
                    }
                }
                break;

            case 11: // ShapeType::Blob
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : (\base64_decode((string) $item, true) ?: '');
                }
                break;

            case 17: // ShapeType::Enum
                $target = $info?->listMemberResolvedTargetClass;
                if ($target === null) {
                    foreach ($value as $item) {
                        $out[] = $item === null ? null : (string) $item;
                    }
                } else {
                    foreach ($value as $item) {
                        $out[] = $item === null
                            ? null
                            : $target::fromValue((string) $item);
                    }
                }
                break;

            case 18: // ShapeType::IntEnum
                $target = $info?->listMemberResolvedTargetClass;
                if ($target === null) {
                    foreach ($value as $item) {
                        $out[] = $item === null ? null : (int) $item;
                    }
                } else {
                    foreach ($value as $item) {
                        $out[] = $item === null
                            ? null
                            : $target::fromValue((int) $item);
                    }
                }
                break;

            case 8: // ShapeType::BigInteger
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : new BigInteger((string) $item);
                }
                break;

            case 9: // ShapeType::BigDecimal
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : new BigDecimal((string) $item);
                }
                break;

            case 15: // ShapeType::Structure
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : $this->readNestedStruct($itemSchema, $item);
                }
                break;

            case 16: // ShapeType::Union
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : $this->readUnion($itemSchema, $item);
                }
                break;

            case 13: // ShapeType::List_
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : $this->readList($itemSchema, $item);
                }
                break;

            case 14: // ShapeType::Map
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : $this->readMap($itemSchema, $item);
                }
                break;

            case 19: // ShapeType::Document
                foreach ($value as $item) {
                    $out[] = $item === null
                        ? null
                        : AbstractDocument::fromValue($item);
                }
                break;

            default:
                return $value;
        }
        return $out;
    }


    private function readMap(Schema $member, mixed $value): array
    {
        // `json_decode(..., true)` always returns arrays for objects.
        if (!\is_array($value)) {
            return [];
        }

        $valueSchema = $member->mapValue;
        if ($valueSchema === null) {
            return $value;
        }

        static $extKey = null;
        $extKey ??= JsonSchemaExtensionProvider::key();
        /** @var JsonSchemaExtension|null $info */
        $info = $member->getExtension($extKey);
        $valueType = $info?->mapValueType ?? $valueSchema->type;

        $out = [];
        switch ($valueType) {
            case 10: // ShapeType::String_
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null ? null : (string) $v;
                }
                break;

            case 4: // ShapeType::Integer
            case 5: // ShapeType::Long
            case 3: // ShapeType::Short
            case 2: // ShapeType::Byte
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null ? null : (int) $v;
                }
                break;

            case 1: // ShapeType::Boolean
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null ? null : (bool) $v;
                }
                break;

            case 6: // ShapeType::Float_
            case 7: // ShapeType::Double
                foreach ($value as $key => $v) {
                    if ($v === null) {
                        $out[$key] = null;
                        continue;
                    }
                    $out[$key] = match ($v) {
                        'NaN' => \NAN,
                        'Infinity' => \INF,
                        '-Infinity' => -\INF,
                        default => (float) $v,
                    };
                }
                break;

            case 12: // ShapeType::Timestamp
                $fmt = $info?->mapValueTimestampFormat
                    ?? TimestampFormat::EpochSeconds;
                if ($fmt === TimestampFormat::EpochSeconds) {
                    foreach ($value as $key => $v) {
                        if ($v === null) {
                            $out[$key] = null;
                            continue;
                        }
                        $out[$key] = \is_int($v) || \is_float($v)
                            ? (float) $v
                            : (float) (string) $v;
                    }
                } else {
                    foreach ($value as $key => $v) {
                        $out[$key] = $v === null
                            ? null
                            : $fmt->parse((string) $v);
                    }
                }
                break;

            case 11: // ShapeType::Blob
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : (\base64_decode((string) $v, true) ?: '');
                }
                break;

            case 17: // ShapeType::Enum
                $target = $info?->mapValueResolvedTargetClass;
                if ($target === null) {
                    foreach ($value as $key => $v) {
                        $out[$key] = $v === null ? null : (string) $v;
                    }
                } else {
                    foreach ($value as $key => $v) {
                        $out[$key] = $v === null
                            ? null
                            : $target::fromValue((string) $v);
                    }
                }
                break;

            case 18: // ShapeType::IntEnum
                $target = $info?->mapValueResolvedTargetClass;
                if ($target === null) {
                    foreach ($value as $key => $v) {
                        $out[$key] = $v === null ? null : (int) $v;
                    }
                } else {
                    foreach ($value as $key => $v) {
                        $out[$key] = $v === null
                            ? null
                            : $target::fromValue((int) $v);
                    }
                }
                break;

            case 8: // ShapeType::BigInteger
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : new BigInteger((string) $v);
                }
                break;

            case 9: // ShapeType::BigDecimal
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : new BigDecimal((string) $v);
                }
                break;

            case 15: // ShapeType::Structure
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : $this->readNestedStruct($valueSchema, $v);
                }
                break;

            case 16: // ShapeType::Union
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : $this->readUnion($valueSchema, $v);
                }
                break;

            case 13: // ShapeType::List_
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : $this->readList($valueSchema, $v);
                }
                break;

            case 14: // ShapeType::Map
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : $this->readMap($valueSchema, $v);
                }
                break;

            case 19: // ShapeType::Document
                foreach ($value as $key => $v) {
                    $out[$key] = $v === null
                        ? null
                        : AbstractDocument::fromValue($v);
                }
                break;

            default:
                return $value;
        }

        return $out;
    }
}
