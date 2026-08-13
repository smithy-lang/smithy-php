<?php

declare(strict_types=1);

namespace Smithy\Cbor;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SchemaExtensionKey;
use Smithy\Core\Serde\SchemaExtensionProviderInterface;
use Smithy\Core\Serde\ShapeType;

/**
 * Build cached CBOR metadata for schema extension lookups.
 */
final class CborSchemaExtensionProvider
    implements SchemaExtensionProviderInterface
{
    /**
     * 32-bit FNV-1a constants used for raw member-name byte pre-hashes.
     */
    private const FNV_OFFSET = 2166136261;
    private const FNV_PRIME = 16777619;

    public static ?SchemaExtensionKey $KEY = null;

    /**
     * Return the shared extension key.
     */
    public static function key(): SchemaExtensionKey
    {
        return self::$KEY ??= new SchemaExtensionKey(new self());
    }

    /**
     * Build cached CBOR metadata for a schema.
     */
    public function provide(Schema $schema): ?CborSchemaExtension
    {
        $memberNameBytes = $schema->isMember() && $schema->memberName !== null
            ? self::encodeMemberName($schema->memberName)
            : null;

        if ($schema->type === ShapeType::List_->value) {
            $listMember = $schema->listMember;
            $resolvedTargetClass = null;
            $resolvedTargetSchema = null;
            $listMemberDecodeKind = null;
            if ($listMember !== null) {
                $listMemberDecodeKind = self::decodeKindForType(
                    $listMember->type
                );
                switch ($listMember->type) {
                    case ShapeType::Enum->value:
                    case ShapeType::IntEnum->value:
                        $resolvedTargetClass = $listMember->targetClass
                            ?? $listMember->resolveTarget()->targetClass;
                        break;

                    case ShapeType::Structure->value:
                    case ShapeType::Union->value:
                        if ($listMember->getMemberCount() === 0
                            && $listMember->targetClass !== null
                        ) {
                            $resolvedTargetSchema =
                                $listMember->resolveTarget();
                            $resolvedTargetClass =
                                $resolvedTargetSchema->targetClass;
                        } else {
                            $resolvedTargetSchema = $listMember;
                            $resolvedTargetClass = $listMember->targetClass;
                        }
                        break;
                }
            }

            return new CborSchemaExtension(
                memberNameBytes: $memberNameBytes,
                listMemberType: $listMember?->type,
                listMemberDecodeKind: $listMemberDecodeKind,
                listMemberSchema: $listMember,
                listMemberResolvedTargetClass: $resolvedTargetClass,
                listMemberResolvedTargetSchema: $resolvedTargetSchema,
                sparse: $schema->hasTrait(Schema::T_SPARSE)
            );
        }

        if ($schema->type === ShapeType::Map->value) {
            $mapValue = $schema->mapValue;
            $resolvedTargetClass = null;
            $resolvedTargetSchema = null;
            $mapValueDecodeKind = null;
            if ($mapValue !== null) {
                $mapValueDecodeKind = self::decodeKindForType($mapValue->type);
                switch ($mapValue->type) {
                    case ShapeType::Enum->value:
                    case ShapeType::IntEnum->value:
                        $resolvedTargetClass = $mapValue->targetClass
                            ?? $mapValue->resolveTarget()->targetClass;
                        break;

                    case ShapeType::Structure->value:
                    case ShapeType::Union->value:
                        if ($mapValue->getMemberCount() === 0
                            && $mapValue->targetClass !== null
                        ) {
                            $resolvedTargetSchema = $mapValue->resolveTarget();
                            $resolvedTargetClass =
                                $resolvedTargetSchema->targetClass;
                        } else {
                            $resolvedTargetSchema = $mapValue;
                            $resolvedTargetClass = $mapValue->targetClass;
                        }
                        break;
                }
            }

            return new CborSchemaExtension(
                memberNameBytes: $memberNameBytes,
                sparse: $schema->hasTrait(Schema::T_SPARSE),
                mapKeySchema: $schema->mapKey,
                mapValueSchema: $mapValue,
                mapValueType: $mapValue?->type,
                mapValueDecodeKind: $mapValueDecodeKind,
                mapValueResolvedTargetClass: $resolvedTargetClass,
                mapValueResolvedTargetSchema: $resolvedTargetSchema
            );
        }

        if ($schema->type === ShapeType::Enum->value
            || $schema->type === ShapeType::IntEnum->value
        ) {
            return new CborSchemaExtension(
                memberNameBytes: $memberNameBytes,
                resolvedTargetClass: $schema->targetClass
                    ?? $schema->resolveTarget()?->targetClass
            );
        }

        if ($schema->type !== ShapeType::Structure->value
            && $schema->type !== ShapeType::Union->value
        ) {
            return $memberNameBytes === null
                ? null
                : new CborSchemaExtension(memberNameBytes: $memberNameBytes);
        }

        $orderedMemberSchemas = [];
        $orderedMemberIndexes = [];
        $orderedMemberTypes = [];
        $orderedMemberDecodeKinds = [];
        $orderedMemberNameBytes = [];
        $orderedMemberNameLengths = [];
        $orderedMemberNameHashes = [];
        $orderedMemberLookup1 = [];
        $orderedMemberLookup2 = [];
        $orderedMemberLookup3 = [];
        $orderedMemberLookup4 = [];
        $orderedMemberResolvedTargetClasses = [];
        $orderedMemberResolvedTargetSchemas = [];
        $fieldNameTable = [];
        $membersByIndex = $schema->getMembersByIndex();
        if ($membersByIndex !== []) {
            \ksort($membersByIndex);
        }

        foreach ($membersByIndex as $member) {
            $rawNameBytes = (string) $member->memberName;
            $encodedNameBytes = self::encodeMemberName($rawNameBytes);
            $fieldNameTable[$member->memberIndex] = $encodedNameBytes;
            $orderedMemberSchemas[] = $member;
            $orderedMemberIndexes[] = $member->memberIndex ?? 0;
            $orderedMemberTypes[] = $member->type;
            $orderedMemberDecodeKinds[] = self::decodeKindForType(
                $member->type
            );
            $orderedMemberNameBytes[] = $rawNameBytes;
            $nameLength = \strlen($rawNameBytes);
            $orderedMemberNameLengths[] = $nameLength;
            $orderedMemberNameHashes[] = self::hash($rawNameBytes);

            if ($nameLength >= 1 && $nameLength <= 4) {
                $packed = self::packShortAsciiKey($rawNameBytes, $nameLength);
                if ($packed !== null) {
                    $slot = \count($orderedMemberSchemas) - 1;
                    switch ($nameLength) {
                        case 1:
                            $orderedMemberLookup1[$packed] = $slot;
                            break;

                        case 2:
                            $orderedMemberLookup2[$packed] = $slot;
                            break;

                        case 3:
                            $orderedMemberLookup3[$packed] = $slot;
                            break;

                        case 4:
                            $orderedMemberLookup4[$packed] = $slot;
                            break;
                    }
                }
            }

            $resolvedTargetClass = null;
            $resolvedTargetSchema = null;
            switch ($member->type) {
                case ShapeType::Enum->value:
                case ShapeType::IntEnum->value:
                    $resolvedTargetClass = $member->targetClass
                        ?? $member->resolveTarget()->targetClass;
                    break;

                case ShapeType::Structure->value:
                case ShapeType::Union->value:
                    if ($member->getMemberCount() === 0
                        && $member->targetClass !== null
                    ) {
                        $resolvedTargetSchema = $member->resolveTarget();
                        $resolvedTargetClass =
                            $resolvedTargetSchema->targetClass;
                    } else {
                        $resolvedTargetSchema = $member;
                        $resolvedTargetClass = $member->targetClass;
                    }
                    break;
            }

            $orderedMemberResolvedTargetClasses[] = $resolvedTargetClass;
            $orderedMemberResolvedTargetSchemas[] = $resolvedTargetSchema;
        }

        $memberCount = $schema->getMemberCount();
        $membersTemplate = $memberCount > 0
            ? \array_fill(0, $memberCount, null)
            : [];

        if ($schema->getMemberCount() === 0 && $schema->targetClass !== null) {
            $resolvedTargetSchema = $schema->resolveTarget();
            $resolvedTargetClass = $resolvedTargetSchema->targetClass;
        } else {
            $resolvedTargetSchema = $schema;
            $resolvedTargetClass = $schema->targetClass;
        }

        return new CborSchemaExtension(
            memberNameBytes: $memberNameBytes,
            orderedMemberSchemas: $orderedMemberSchemas,
            orderedMemberIndexes: $orderedMemberIndexes,
            orderedMemberTypes: $orderedMemberTypes,
            orderedMemberDecodeKinds: $orderedMemberDecodeKinds,
            orderedMemberNameBytes: $orderedMemberNameBytes,
            orderedMemberNameLengths: $orderedMemberNameLengths,
            orderedMemberNameHashes: $orderedMemberNameHashes,
            orderedMemberLookup1: $orderedMemberLookup1,
            orderedMemberLookup2: $orderedMemberLookup2,
            orderedMemberLookup3: $orderedMemberLookup3,
            orderedMemberLookup4: $orderedMemberLookup4,
            orderedMemberResolvedTargetClasses:
                $orderedMemberResolvedTargetClasses,
            orderedMemberResolvedTargetSchemas:
                $orderedMemberResolvedTargetSchemas,
            fieldNameTable: $fieldNameTable,
            memberCount: $memberCount,
            membersTemplate: $membersTemplate,
            resolvedTargetClass: $resolvedTargetClass,
            resolvedTargetSchema: $resolvedTargetSchema
        );
    }

    /**
     * Map a ShapeType to a decode kind.
     */
    private static function decodeKindForType(int $type): int
    {
        return match ($type) {
            ShapeType::Boolean->value => 1,
            ShapeType::Byte->value,
            ShapeType::Short->value,
            ShapeType::Integer->value,
            ShapeType::Long->value => 2,
            ShapeType::Float_->value, ShapeType::Double->value => 3,
            ShapeType::BigInteger->value => 4,
            ShapeType::BigDecimal->value => 5,
            ShapeType::String_->value => 6,
            ShapeType::Blob->value => 7,
            ShapeType::Timestamp->value => 8,
            ShapeType::List_->value => 9,
            ShapeType::Map->value => 10,
            ShapeType::Structure->value => 11,
            ShapeType::Union->value => 12,
            ShapeType::Enum->value => 13,
            ShapeType::IntEnum->value => 14,
            ShapeType::Document->value => 15,
            default => 0,
        };
    }

    /**
     * Encode a member name as a CBOR text string.
     *
     * Returns the text-string header followed by the raw UTF-8 bytes.
     */
    public static function encodeMemberName(string $memberName): string
    {
        $length = \strlen($memberName);

        if ($length < 24) {
            return \chr(0x60 | $length) . $memberName;
        }

        if ($length < 0x100) {
            return "\x78" . \chr($length) . $memberName;
        }

        if ($length < 0x10000) {
            return "\x79" . \pack('n', $length) . $memberName;
        }

        return "\x7A" . \pack('N', $length) . $memberName;
    }

    /**
     * Hash member-name bytes with FNV-1a.
     */
    private static function hash(string $memberNameBytes): int
    {
        $hash = self::FNV_OFFSET;
        $length = \strlen($memberNameBytes);
        for ($i = 0; $i < $length; $i++) {
            $hash ^= \ord($memberNameBytes[$i]);
            $hash = ($hash * self::FNV_PRIME) & 0xFFFFFFFF;
        }

        return $hash;
    }

    /**
     * Pack a short ASCII member name into an integer lookup key.
     */
    private static function packShortAsciiKey(string $memberNameBytes, int $length): ?int
    {
        $packed = 0;
        for ($i = 0; $i < $length; $i++) {
            $byte = \ord($memberNameBytes[$i]);
            if ($byte > 0x7F) {
                return null;
            }
            $packed = ($packed << 8) | $byte;
        }

        return $packed;
    }
}
