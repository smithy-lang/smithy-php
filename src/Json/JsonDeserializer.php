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
    private readonly JsonCodecSettings $settings;
    private readonly bool $honorTimestampFormatTrait;
    /**
     * Decoded wire body.
     */
    private mixed $parsed;

    public function __construct(
        string $source,
        ?JsonCodecSettings $settings = null
    )
    {
        $this->settings = $settings ?? new JsonCodecSettings();
        $this->honorTimestampFormatTrait =
            $this->settings->honorTimestampFormatTrait;
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
            $members[0] = $this->readUnion($schema, $this->parsed);
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
        $ordered = $this->settings->honorJsonName
            ? $info->orderedMembers
            : $info->rawOrderedMembers;
        $memberDecodeByWireName = $this->settings->honorJsonName
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
            : ($this->settings->honorJsonName
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
                $fmt = ($this->honorTimestampFormatTrait
                    ? $context
                    : null
                ) ?? $this->settings->defaultTimestampFormat;
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
        $itemType = $info?->listMemberType ?? $itemSchema->type;
        $context = match ($itemType) {
            ShapeType::Timestamp->value => $info?->listMemberTimestampFormat,
            ShapeType::Enum->value,
            ShapeType::IntEnum->value => $info?->listMemberResolvedTargetClass,
            default => $itemSchema,
        };

        $out = [];
        foreach ($value as $item) {
            $out[] = $item === null
                ? null
                : $this->readValue($itemType, $item, $context);
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
        $context = match ($valueType) {
            ShapeType::Timestamp->value => $info?->mapValueTimestampFormat,
            ShapeType::Enum->value,
            ShapeType::IntEnum->value => $info?->mapValueResolvedTargetClass,
            default => $valueSchema,
        };

        $out = [];
        foreach ($value as $key => $item) {
            $out[$key] = $item === null
                ? null
                : $this->readValue($valueType, $item, $context);
        }

        return $out;
    }
}
