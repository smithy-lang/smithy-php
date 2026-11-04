<?php

declare(strict_types=1);

namespace Smithy\Core\Serde;

use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\DocumentInterface;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\Document\UnionDocument;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;
use Smithy\Core\Shape\ShapeType;

/**
 * Create generated shapes or schema-backed documents from document values.
 *
 * @internal
 */
final class ShapeFactory
{
    /**
     * Create the type described by a registry entry.
     *
     * @param class-string<SerializableShapeInterface>|null $targetClass
     */
    public static function create(
        Schema $schema,
        ?string $targetClass,
        DocumentInterface $value
    ): SerializableShapeInterface
    {
        $resolved = $schema->resolveTarget();
        $targetClass = $schema->targetClass
            ?? $resolved->targetClass
            ?? $targetClass;

        return match ($resolved->type) {
            ShapeType::Structure->value => self::createStructure(
                $resolved,
                $targetClass,
                $value
            ),
            ShapeType::Union->value => self::createUnion(
                $resolved,
                $targetClass,
                $value
            ),
            ShapeType::Enum->value => self::createEnum(
                $resolved,
                $targetClass,
                $value->asString()
            ),
            ShapeType::IntEnum->value => self::createEnum(
                $resolved,
                $targetClass,
                $value->asInteger()
            ),
            ShapeType::Document->value => $value,
            default => self::createDocument(
                $resolved,
                self::valueFor($resolved, $value)
            )
        };
    }

    /**
     * @param class-string<SerializableShapeInterface>|null $targetClass
     */
    private static function createStructure(
        Schema $schema,
        ?string $targetClass,
        DocumentInterface $value
    ): SerializableShapeInterface
    {
        $members = $schema->getMemberCount() === 0
            ? []
            : \array_fill(0, $schema->getMemberCount(), null);

        foreach ($schema->getMembersByIndex() as $index => $member) {
            $memberValue = $value->getMember((string) $member->memberName);
            if ($memberValue !== null) {
                $members[$index] = self::valueFor($member, $memberValue);
            }
        }

        if ($targetClass !== null) {
            return $targetClass::fromMembers($members);
        }

        return StructDocument::fromMembers($schema, $members);
    }

    /**
     * @param class-string<SerializableShapeInterface>|null $targetClass
     */
    private static function createUnion(
        Schema $schema,
        ?string $targetClass,
        DocumentInterface $value
    ): SerializableShapeInterface
    {
        foreach ($value->asMap() as $tag => $memberValue) {
            if ($tag === '__type') {
                continue;
            }

            $member = $schema->getMember($tag);
            $hydrated = $memberValue === null
                ? null
                : ($member === null
                    ? self::untypedValue($memberValue)
                    : self::valueFor($member, $memberValue));

            if ($targetClass !== null) {
                return $targetClass::create($tag, $hydrated);
            }

            return UnionDocument::create($schema, $tag, $hydrated);
        }

        throw new InvalidShapeValueException(
            "Cannot create union {$schema->id} from an empty document"
        );
    }

    /**
     * @param class-string<SerializableShapeInterface>|null $targetClass
     */
    private static function createEnum(
        Schema $schema,
        ?string $targetClass,
        string|int $value
    ): SerializableShapeInterface
    {
        if ($targetClass !== null) {
            return $targetClass::fromValue($value);
        }

        return self::createDocument($schema, $value);
    }

    private static function valueFor(
        Schema $schema,
        DocumentInterface $value
    ): mixed
    {
        $resolved = $schema->resolveTarget();
        $targetClass = $schema->targetClass ?? $resolved->targetClass;

        return match ($resolved->type) {
            ShapeType::Boolean->value => $value->asBoolean(),
            ShapeType::Byte->value => $value->asByte(),
            ShapeType::Short->value => $value->asShort(),
            ShapeType::Integer->value => $value->asInteger(),
            ShapeType::Long->value => $value->asLong(),
            ShapeType::Float_->value => $value->asFloat(),
            ShapeType::Double->value => $value->asDouble(),
            ShapeType::BigInteger->value => $value->asBigInteger(),
            ShapeType::BigDecimal->value => $value->asBigDecimal(),
            ShapeType::String_->value => $value->asString(),
            ShapeType::Blob->value => $value->asBlob(),
            ShapeType::Timestamp->value => $value->asTimestamp(),
            ShapeType::Enum->value => $targetClass !== null
                ? $targetClass::fromValue($value->asString())
                : $value->asString(),
            ShapeType::IntEnum->value => $targetClass !== null
                ? $targetClass::fromValue($value->asInteger())
                : $value->asInteger(),
            ShapeType::List_->value => self::listValue($resolved, $value),
            ShapeType::Map->value => self::mapValue($resolved, $value),
            ShapeType::Structure->value,
            ShapeType::Union->value => self::create(
                $resolved,
                $targetClass,
                $value
            ),
            ShapeType::Document->value => $value,
            default => throw new InvalidShapeValueException(
                'Cannot create a value for unsupported shape type '
                    . ShapeType::from($resolved->type)->name
            )
        };
    }

    /**
     * @return list<mixed>
     */
    private static function listValue(
        Schema $schema,
        DocumentInterface $value
    ): array
    {
        $member = $schema->getListMember();
        $result = [];

        foreach ($value->asList() as $item) {
            $result[] = $item === null
                ? null
                : ($member === null
                    ? self::untypedValue($item)
                    : self::valueFor($member, $item));
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function mapValue(
        Schema $schema,
        DocumentInterface $value
    ): array
    {
        $mapValue = $schema->getMapValue();
        $result = [];

        foreach ($value->asMap() as $key => $entry) {
            $result[$key] = $entry === null
                ? null
                : ($mapValue === null
                    ? self::untypedValue($entry)
                    : self::valueFor($mapValue, $entry));
        }

        return $result;
    }

    private static function untypedValue(DocumentInterface $value): mixed
    {
        return match ($value->type()) {
            ShapeType::Boolean => $value->asBoolean(),
            ShapeType::Byte => $value->asByte(),
            ShapeType::Short => $value->asShort(),
            ShapeType::Integer => $value->asInteger(),
            ShapeType::Long => $value->asLong(),
            ShapeType::Float_ => $value->asFloat(),
            ShapeType::Double => $value->asDouble(),
            ShapeType::BigInteger => $value->asBigInteger(),
            ShapeType::BigDecimal => $value->asBigDecimal(),
            ShapeType::String_,
            ShapeType::Enum => $value->asString(),
            ShapeType::Blob => $value->asBlob(),
            ShapeType::Timestamp => $value->asTimestamp(),
            ShapeType::IntEnum => $value->asInteger(),
            ShapeType::List_ => \array_map(
                static fn (?DocumentInterface $item): mixed => $item === null
                    ? null
                    : self::untypedValue($item),
                $value->asList()
            ),
            ShapeType::Map,
            ShapeType::Structure,
            ShapeType::Union => \array_map(
                static fn (?DocumentInterface $entry): mixed => $entry === null
                    ? null
                    : self::untypedValue($entry),
                $value->asMap()
            ),
            ShapeType::Document => $value,
            default => throw new InvalidShapeValueException(
                'Cannot extract unsupported document type '
                    . $value->type()->name
            )
        };
    }

    private static function createDocument(
        Schema $schema,
        mixed $value
    ): AbstractDocument
    {
        $document = AbstractDocument::fromSchemaValue($schema, $value);
        if ($document === null) {
            throw new InvalidShapeValueException(
                "Cannot create {$schema->id} from null"
            );
        }

        return $document;
    }
}
