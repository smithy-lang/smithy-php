<?php

declare(strict_types=1);

namespace Smithy\FormUrlEncoded;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\BigDecimal;
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\Document\DocumentInterface;
use Smithy\Core\Shape\UnionInterface;

/**
 * Serialize awsQuery-family request bodies.
 *
 * Behavior is driven by {@see FormUrlEncodedCodecSettings}.
 *
 * @internal Instantiated only by {@see FormUrlEncodedCodec}.
 */
final class FormUrlEncodedSerializer implements ShapeSerializerInterface
{
    private readonly FormUrlEncodedCodecSettings $settings;

    public function __construct(
        ?FormUrlEncodedCodecSettings $settings = null
    ) {
        $this->settings = $settings ?? new FormUrlEncodedCodecSettings();
    }

    /**
     * The accumulated URL-encoded body. Each `serializeXxx` scalar
     * append emits one `key=value&` fragment; {@see flush()} trims the
     * final trailing `&` before returning.
     */
    private string $buffer = '';

    /**
     * Prefix carried by every scalar emit under the current struct /
     * list / map / union frame. Always ends with `'.'` (or is the empty
     * string at the outermost frame), so appending the wire name is a
     * bare concat with no separator branch.
     */
    private string $prefix = '';

    /**
     * Wire-name lookup for the current struct / union frame, or `null`
     * at the outermost level before any `beginStruct` runs. Sourced from
     * {@see FormUrlEncodedSchemaExtension::$memberWireNames}.
     *
     * @var array<int, string>|null
     */
    private ?array $wireNames = null;

    /**
     * Precomputed timestamp-format map for the current struct frame:
     * `memberIndex → TimestampFormat`. Sparse. Only timestamp members
     * with an explicit `@timestampFormat` trait appear.
     *
     * @var array<int, TimestampFormat>
     */
    private array $tsFormats = [];

    /**
     * Saved parent frames for nested struct / union writes. Each entry
     * captures the state {@see beginStruct} rewrote so {@see endStruct}
     * can restore it exactly.
     *
     * @var list<array{0: string, 1: array<int,string>|null, 2: array<int,TimestampFormat>}>
     */
    private array $frameStack = [];

    // -----------------------------------------------------------------
    // Scalar write paths
    // -----------------------------------------------------------------
    //
    // Each of these looks up the current wire name in one array offset,
    // concatenates the prefix + wire name + '=' + urlencoded value +
    // '&', and appends to the buffer in a single `.=`. The hot path for
    // a struct with N scalar members is N × (1 array read, 1 C-string
    // encode, 1 string concat).
    //
    // `$this->wireNames` is guaranteed non-null here because scalar
    // methods only run inside a beginStruct → endStruct pair emitted
    // by codegen. The `null` state is reserved for the outermost
    // "before any struct opens" case, which no scalar path reaches.

    /**
     * Serialize a boolean member.
     *
     * @return void
     */
    public function serializeBoolean(Schema $schema, bool $value, int $idx): void
    {
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . ($value ? 'true' : 'false')
            . '&';
    }


    /**
     * Serialize an integer member.
     *
     * @return void
     */
    public function serializeInteger(Schema $schema, int $value, int $idx): void
    {
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . (string) $value
            . '&';
    }


    /**
     * Serialize a float member.
     *
     * @return void
     */
    public function serializeFloat(Schema $schema, float $value, int $idx): void
    {
        // Match Smithy's canonical non-numeric float spellings.
        $s = match ($str = (string) $value) {
            'NAN'  => 'NaN',
            'INF'  => 'Infinity',
            '-INF' => '-Infinity',
            default => $str,
        };
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . \rawurlencode($s)
            . '&';
    }


    /**
     * Serialize a big-integer member.
     *
     * @return void
     */
    public function serializeBigInteger(
        Schema $schema,
        BigInteger $value,
        int $idx
    ): void
    {
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . \rawurlencode($value->value)
            . '&';
    }


    /**
     * Serialize a big-decimal member.
     *
     * @return void
     */
    public function serializeBigDecimal(
        Schema $schema,
        BigDecimal $value,
        int $idx
    ): void
    {
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . \rawurlencode($value->value)
            . '&';
    }


    /**
     * Serialize a string member.
     *
     * @return void
     */
    public function serializeString(Schema $schema, string $value, int $idx): void
    {
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . \rawurlencode($value)
            . '&';
    }


    /**
     * Serialize a blob member.
     *
     * @return void
     */
    public function serializeBlob(Schema $schema, string $value, int $idx): void
    {
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . \rawurlencode(\base64_encode($value))
            . '&';
    }


    /**
     * Serialize a timestamp member.
     *
     * @return void
     */
    public function serializeTimestamp(
        Schema $schema,
        float $value,
        int $idx,
        ?TimestampFormat $format = null
    ): void
    {
        $fmt = $this->tsFormats[$idx]
            ?? $format
            ?? $this->settings->defaultTimestampFormat;
        $this->buffer .= $this->prefix
            . $this->wireNames[$idx]
            . '='
            . \rawurlencode($fmt->format($value))
            . '&';
    }


    // -----------------------------------------------------------------
    // Aggregate write paths (list / map)
    // -----------------------------------------------------------------

    /**
     * Serialize a list member.
     *
     * @return void
     */
    public function serializeList(Schema $schema, array $list, int $idx): void
    {
        if ($list === []) {
            if ($this->settings->alwaysFlattenLists) {
                return;
            }

            $containerName = $this->wireNames[$idx] ?? '';
            $this->buffer .= $this->prefix . $containerName . '=&';
            return;
        }

        static $extKey = null;
        $extKey ??= FormUrlEncodedSchemaExtensionProvider::key();
        /** @var FormUrlEncodedSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);

        $containerName = $this->wireNames[$idx] ?? '';
        $precomputedItemPrefix = $this->settings->useAlternateWireNames
            ? $info?->ec2MemberListPrefix
            : $info?->memberListPrefix;
        if ($precomputedItemPrefix !== null) {
            $itemPrefix = $this->prefix . $precomputedItemPrefix;
        } else {
            $flattened = $this->settings->alwaysFlattenLists
                || ($info?->flattened ?? false);
            if ($flattened) {
                $itemPrefix = $this->prefix . $containerName . '.';
            } else {
                $memberName = $info?->listMemberName ?? 'member';
                $itemPrefix = $this->prefix . $containerName . '.'
                    . $memberName . '.';
            }
        }

        $memberSchema = $info?->listMemberSchema ?? $schema->getListMember();
        // Resolve member type as its int discriminant once per call.
        $mt = $info?->listMemberType
            ?? $memberSchema?->type
            ?? ShapeType::Document->value;

        $savedPrefix = $this->prefix;
        $savedWireNames = $this->wireNames;
        $savedTsFormats = $this->tsFormats;

        switch ($mt) {
            case ShapeType::String_->value:
            case ShapeType::Enum->value:
            case ShapeType::Integer->value:
            case ShapeType::Long->value:
            case ShapeType::Short->value:
            case ShapeType::Byte->value:
            case ShapeType::IntEnum->value:
            case ShapeType::Boolean->value:
            case ShapeType::Float_->value:
            case ShapeType::Double->value:
            case ShapeType::Blob->value:
            case ShapeType::Timestamp->value:
            case ShapeType::BigInteger->value:
            case ShapeType::BigDecimal->value:
                $fmt = $mt === ShapeType::Timestamp->value
                    ? ($info?->listMemberTimestampFormat
                        ?? $this->settings->defaultTimestampFormat)
                    : null;
                $counter = 1;
                foreach ($list as $item) {
                    if ($item === null) {
                        $counter++;
                        continue;
                    }
                    $this->serializeValue(
                        $mt,
                        $item,
                        $itemPrefix . $counter,
                        $fmt
                    );
                    $counter++;
                }
                break;

            case ShapeType::Structure->value:
            case ShapeType::Union->value:
                $savedPrefix = $this->prefix;
                $counter = 1;
                foreach ($list as $item) {
                    if ($item === null) {
                        $counter++;
                        continue;
                    }
                    $this->wireNames = [0 => (string) $counter];
                    $this->prefix = $itemPrefix;
                    if ($mt === ShapeType::Structure->value) {
                        $this->serializeStruct($memberSchema, $item, 0);
                    } else {
                        $this->serializeUnion($memberSchema, $item, 0);
                    }
                    $counter++;
                }
                break;

            case ShapeType::List_->value:
                $counter = 1;
                foreach ($list as $item) {
                    if ($item === null) {
                        $counter++;
                        continue;
                    }
                    $this->wireNames = [0 => (string) $counter];
                    $this->prefix = $itemPrefix;
                    $this->serializeList($memberSchema, $item, 0);
                    $counter++;
                }
                break;

            case ShapeType::Map->value:
                $counter = 1;
                foreach ($list as $item) {
                    if ($item === null) {
                        $counter++;
                        continue;
                    }
                    $this->wireNames = [0 => (string) $counter];
                    $this->prefix = $itemPrefix;
                    $this->serializeMap($memberSchema, $item, 0);
                    $counter++;
                }
                break;
        }

        $this->prefix = $savedPrefix;
        $this->wireNames = $savedWireNames;
        $this->tsFormats = $savedTsFormats;
    }


    /**
     * Serialize a map member.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function serializeMap(Schema $schema, array $map, int $idx): void
    {
        if (!$this->settings->supportsMaps) {
            throw new \RuntimeException(
                'This form-urlencoded mode does not support map '
                . 'serialization (member: '
                . ($schema->memberName ?? $schema->id) . ')'
            );
        }

        if ($map === []) {
            return;
        }

        static $extKey = null;
        $extKey ??= FormUrlEncodedSchemaExtensionProvider::key();
        /** @var FormUrlEncodedSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);

        $containerName = $this->wireNames[$idx] ?? '';
        $flattened = $info?->flattened ?? false;
        $keyName = $info?->mapKeyName ?? 'key';
        $valueName = $info?->mapValueName ?? 'value';
        $keyAssignment = $info?->mapKeyAssignment ?? ($keyName . '=');

        $precomputedEntryPrefix = $info?->memberMapEntryPrefix;
        if ($precomputedEntryPrefix !== null) {
            $baseEntryPrefix = $this->prefix . $precomputedEntryPrefix;
        } else {
            $baseEntryPrefix = $flattened
                ? $this->prefix . $containerName . '.'
                : $this->prefix . $containerName . '.entry.';
        }

        $valueSchema = $info?->mapValueSchema ?? $schema->getMapValue();
        $valueType = $valueSchema?->type ?? ShapeType::String_->value;
        $valueTsFmt = $info?->mapValueTimestampFormat;

        $savedPrefix = $this->prefix;
        $savedWireNames = $this->wireNames;
        $savedTsFormats = $this->tsFormats;

        $counter = 1;
        foreach ($map as $k => $v) {
            $entryPrefix = $baseEntryPrefix . $counter . '.';
            $keyString = (string) $k;

            $this->buffer .= $entryPrefix
                . $keyAssignment
                . \rawurlencode($keyString)
                . '&';

            if ($v === null) {
                $counter++;
                continue;
            }

            switch ($valueType) {
                case ShapeType::String_->value:
                case ShapeType::Enum->value:
                case ShapeType::Integer->value:
                case ShapeType::Long->value:
                case ShapeType::Short->value:
                case ShapeType::Byte->value:
                case ShapeType::IntEnum->value:
                case ShapeType::Boolean->value:
                case ShapeType::Float_->value:
                case ShapeType::Double->value:
                case ShapeType::Timestamp->value:
                case ShapeType::Blob->value:
                case ShapeType::BigInteger->value:
                case ShapeType::BigDecimal->value:
                    $this->serializeValue(
                        $valueType,
                        $v,
                        $entryPrefix . $valueName,
                        $valueTsFmt
                    );
                    break;
                case ShapeType::Structure->value:
                case ShapeType::Union->value:
                    $this->prefix = $entryPrefix;
                    $this->wireNames = [0 => $valueName];
                    if ($valueType === ShapeType::Structure->value) {
                        $this->serializeStruct($valueSchema, $v, 0);
                    } else {
                        $this->serializeUnion($valueSchema, $v, 0);
                    }
                    break;
                case ShapeType::List_->value:
                    $this->wireNames = [0 => $valueName];
                    $this->prefix = $entryPrefix;
                    $this->serializeList($valueSchema, $v, 0);
                    break;
                case ShapeType::Map->value:
                    $this->wireNames = [0 => $valueName];
                    $this->prefix = $entryPrefix;
                    $this->serializeMap($valueSchema, $v, 0);
                    break;
            }

            $counter++;
        }

        $this->prefix = $savedPrefix;
        $this->wireNames = $savedWireNames;
        $this->tsFormats = $savedTsFormats;
    }


    // -----------------------------------------------------------------
    // Struct / union entry points
    // -----------------------------------------------------------------

    /**
     * Serialize a structure member.
     *
     * @return void
     */
    public function serializeStruct(
        Schema $schema,
        SerializableStructInterface $struct,
        int $idx
    ): void
    {
        $this->beginStruct($struct::schema(), $idx);
        $struct->serializeMembers($this);
        $this->endStruct();
    }

    /**
     * Initialize the top-level request frame.
     *
     * @return void
     */
    public function beginRootStruct(Schema $schema): void
    {
        static $extKey = null;
        $extKey ??= FormUrlEncodedSchemaExtensionProvider::key();
        /** @var FormUrlEncodedSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);

        $this->prefix = '';
        $this->wireNames = $this->settings->useAlternateWireNames
            ? ($info?->ec2MemberWireNames ?? [])
            : ($info?->memberWireNames ?? []);
        $this->tsFormats = $info?->tsFormats ?? [];
    }


    /**
     * Push a structure frame.
     *
     * @return void
     */
    public function beginStruct(Schema $schema, int $idx): void
    {
        static $extKey = null;
        $extKey ??= FormUrlEncodedSchemaExtensionProvider::key();
        /** @var FormUrlEncodedSchemaExtension|null $info */
        $info = $schema->getExtension($extKey);

        $this->frameStack[] = [
            $this->prefix,
            $this->wireNames,
            $this->tsFormats
        ];

        if ($this->wireNames !== null) {
            $this->prefix .= $this->wireNames[$idx] . '.';
        }

        $this->wireNames = $this->settings->useAlternateWireNames
            ? ($info?->ec2MemberWireNames ?? [])
            : ($info?->memberWireNames ?? []);
        $this->tsFormats = $info?->tsFormats ?? [];
    }


    /**
     * Pop a structure frame.
     *
     * @return void
     */
    public function endStruct(): void
    {
        [$this->prefix, $this->wireNames, $this->tsFormats]
            = \array_pop($this->frameStack);
    }


    /**
     * Serialize a union member.
     *
     * @return void
     */
    public function serializeUnion(Schema $schema, mixed $union, int $idx): void
    {
        /** @var UnionInterface $union */
        $tag = $union->tag();
        $value = $union->value();
        if ($value === null) {
            return;
        }

        static $extKey = null;
        $extKey ??= FormUrlEncodedSchemaExtensionProvider::key();
        /** @var FormUrlEncodedSchemaExtension|null $info */
        $info = $union::schema()->getExtension($extKey);
        $variantSchema = $info?->unionMembersByName[$tag] ?? null;
        if ($variantSchema === null) {
            return;
        }

        $savedPrefix = $this->prefix;
        if ($this->wireNames !== null) {
            $this->prefix .= $this->wireNames[$idx] . '.';
        }
        $savedWireNames = $this->wireNames;
        $savedTsFormats = $this->tsFormats;
        $variantIdx = $variantSchema->memberIndex;
        $variantWire = $this->settings->useAlternateWireNames
            ? (
                ($info?->ec2UnionMemberWireNames[$tag] ?? null)
                ?? $variantSchema->memberName
            )
            : (
                ($info?->unionMemberWireNames[$tag] ?? null)
                ?? $variantSchema->memberName
            );
        $this->wireNames = [$variantIdx => $variantWire];
        $this->tsFormats = [];
        $variantTsFmt = $info->unionMemberTsFormats[$tag] ?? null;
        if ($variantTsFmt !== null) {
            $this->tsFormats[$variantIdx] = $variantTsFmt;
        }

        $key = $this->prefix . $variantWire;
        switch ($variantSchema->type) {
            case ShapeType::String_->value:
            case ShapeType::Enum->value:
            case ShapeType::Integer->value:
            case ShapeType::Long->value:
            case ShapeType::Short->value:
            case ShapeType::Byte->value:
            case ShapeType::IntEnum->value:
            case ShapeType::Float_->value:
            case ShapeType::Double->value:
            case ShapeType::Boolean->value:
            case ShapeType::Timestamp->value:
            case ShapeType::Blob->value:
            case ShapeType::BigInteger->value:
            case ShapeType::BigDecimal->value:
                $this->serializeValue(
                    $variantSchema->type,
                    $value,
                    $key,
                    $variantTsFmt
                );
                break;
            case ShapeType::Structure->value:
                $this->serializeStruct($variantSchema, $value, $variantIdx);
                break;
            case ShapeType::Union->value:
                $this->serializeUnion($variantSchema, $value, $variantIdx);
                break;
            case ShapeType::List_->value:
                $this->serializeList($variantSchema, $value, $variantIdx);
                break;
            case ShapeType::Map->value:
                $this->serializeMap($variantSchema, $value, $variantIdx);
                break;
        }

        $this->prefix = $savedPrefix;
        $this->wireNames = $savedWireNames;
        $this->tsFormats = $savedTsFormats;
    }


    /**
     * Reject document serialization.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function serializeDocument(
        Schema $schema,
        ?DocumentInterface $value,
        int $idx
    ): void {
        throw new \RuntimeException(
            'This form-urlencoded mode does not support document '
            . 'types (member: '
            . ($schema->memberName ?? $schema->id) . ')'
        );
    }


    /**
     * Reset serializer state.
     *
     * @return void
     *
     * @internal Called by {@see FormUrlEncodedCodec} only.
     */
    public function reset(): void
    {
        $this->buffer = '';
        $this->prefix = '';
        $this->wireNames = null;
        $this->tsFormats = [];
        $this->frameStack = [];
    }


    /**
     * Return the accumulated body and reset the buffer.
     */
    public function flush(): string
    {
        $out = $this->buffer !== ''
            ? \substr($this->buffer, 0, -1)
            : '';
        $this->reset();

        return $out;
    }

    /**
     * Serialize one scalar-like value at the given wire key.
     */
    private function serializeValue(
        int $type,
        mixed $value,
        string $key,
        ?TimestampFormat $timestampFormat = null
    ): void
    {
        switch ($type) {
            case ShapeType::String_->value:
            case ShapeType::Enum->value:
                $this->buffer .= $key
                    . '='
                    . \rawurlencode((string) $value)
                    . '&';
                return;
            case ShapeType::Integer->value:
            case ShapeType::Long->value:
            case ShapeType::Short->value:
            case ShapeType::Byte->value:
            case ShapeType::IntEnum->value:
                $this->buffer .= $key . '=' . (int) $value . '&';
                return;
            case ShapeType::Boolean->value:
                $this->buffer .= $key . '='
                    . ($value ? 'true' : 'false')
                    . '&';
                return;
            case ShapeType::Float_->value:
            case ShapeType::Double->value:
                $number = match ($str = (string) (float) $value) {
                    'NAN'  => 'NaN',
                    'INF'  => 'Infinity',
                    '-INF' => '-Infinity',
                    default => $str,
                };
                $this->buffer .= $key . '=' . \rawurlencode($number) . '&';
                return;
            case ShapeType::Timestamp->value:
                $fmt = $timestampFormat
                    ?? $this->settings->defaultTimestampFormat;
                $this->buffer .= $key . '='
                    . \rawurlencode($fmt->format((float) $value))
                    . '&';
                return;
            case ShapeType::Blob->value:
                $this->buffer .= $key . '='
                    . \rawurlencode(\base64_encode((string) $value))
                    . '&';
                return;
            case ShapeType::BigInteger->value:
            case ShapeType::BigDecimal->value:
                $this->buffer .= $key . '='
                    . \rawurlencode($value->value)
                    . '&';
                return;
        }

        throw new \LogicException(
            'serializeValue does not support ShapeType ' . $type
        );
    }

}
