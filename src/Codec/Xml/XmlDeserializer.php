<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Codec\Xml;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableShapeInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Serde\ShapeDeserializerInterface;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\BigDecimal;
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\Document\UnionDocument;

/**
 * Deserialize XML into Smithy shapes with a C-extension tree parser.
 *
 * Parses the document into an in-memory tree once, then traverses that
 * tree with schema-guided member dispatch.
 *
 * @internal Instantiated only by {@see XmlCodec}. External callers should
 *           depend on {@see ShapeDeserializerInterface}. The concrete parser
 *           choice is a private detail of the codec.
 */
final class XmlDeserializer implements ShapeDeserializerInterface
{
    // Amortize the namespace-scope check over larger maps.
    private const NATIVE_MAP_MIN_ENTRIES = 32;

    private readonly XmlCodecSettings $settings;
    private readonly bool $honorTimestampFormatTrait;
    private readonly bool $hasNamespacePrefixes;

    /**
     * Initialize namespace handling from the codec's prefix check.
     *
     * Inspect the parsed tree when no prefix flag is supplied.
     */
    public function __construct(
        private \SimpleXMLElement $element,
        ?XmlCodecSettings $settings = null,
        ?bool $hasNamespacePrefixes = null
    )
    {
        $this->settings = $settings ?? new XmlCodecSettings();
        $this->honorTimestampFormatTrait =
            $this->settings->honorTimestampFormatTrait;
        $this->hasNamespacePrefixes = $hasNamespacePrefixes ?? \array_diff_key(
            $element->getNamespaces(true),
            ['' => true]
        ) !== [];
    }

    /**
     * Deserialize an XML payload into members.
     *
     * @param array<int, mixed> $members
     *
     * @return void
     */
    public function deserialize(Schema $schema, array &$members): void
    {
        if ($schema->type === ShapeType::Union->value) {
            // httpPayload union bodies use the current root as the union payload.
            $members[0] = $this->readUnionFromElement($this->element, $schema);

            return;
        }

        $this->readStructFromElement($this->element, $schema, $members);
    }

    /**
     * Read structure members from an element.
     *
     * @param array<int, mixed> $out
     *
     * @return void
     */
    private function readStructFromElement(
        \SimpleXMLElement $element,
        Schema $schema,
        array &$out
    ): void
    {
        // Cache the XML extension key once per method.
        static $extKey = null;
        $extKey ??= XmlSchemaExtensionProvider::key();

        /** @var XmlSchemaExtension $info */
        $info = $schema->getExtension($extKey);

        // Attribute decode metadata is prepacked on the extension.
        if ($info->hasAttributes) {
            $attrs = $element->attributes();
            if ($this->hasNamespacePrefixes) {
                $attrs = [];
                foreach ($element->xpath('./@*') ?: [] as $attribute) {
                    $attrs[$attribute->getName()] = $attribute;
                }
            }

            if ($attrs !== null) {
                foreach ($info->attributeDeserMembers as $entry) {
                    $attrName = $entry[0];
                    $attrValue = $attrs[$attrName] ?? null;
                    if ($attrValue !== null) {
                        $value = (string) $attrValue;
                        $idx = $entry[1];
                        $memberSchema = $info->attributeMembers[$attrName]
                            ?? null;
                        switch ($entry[2]) {
                            case ShapeType::Boolean->value:
                                $out[$idx] = $value === 'true'
                                    || $value === '1';
                                break;
                            case ShapeType::Integer->value:
                            case ShapeType::Long->value:
                            case ShapeType::Short->value:
                            case ShapeType::Byte->value:
                                $out[$idx] = (int) $value;
                                break;
                            case ShapeType::IntEnum->value:
                                $intValue = (int) $value;
                                $targetClass = $memberSchema?->targetClass;
                                $out[$idx] = $targetClass !== null
                                    ? $targetClass::fromValue($intValue)
                                    : $intValue;
                                break;
                            case ShapeType::Float_->value:
                            case ShapeType::Double->value:
                                $out[$idx] = (float) $value;
                                break;
                            case ShapeType::Timestamp->value:
                                $out[$idx] = (
                                    ($this->honorTimestampFormatTrait
                                        ? $entry[3] ?? null
                                        : null
                                    ) ?? $this->settings->defaultTimestampFormat
                                )->parse($value);
                                break;
                            case ShapeType::Blob->value:
                                $out[$idx] = \base64_decode($value, true) ?: '';
                                break;
                            case ShapeType::Enum->value:
                                $targetClass = $memberSchema?->targetClass;
                                $out[$idx] = $targetClass !== null
                                    ? $targetClass::fromValue($value)
                                    : $value;
                                break;
                            default:
                                $out[$idx] = $value;
                                break;
                        }
                    }
                }
            }
        }

        $ordered = $info->orderedChildMembers;
        $orderedCount = $info->childMemberCount;
        $hint = 0;
        $children = $this->hasNamespacePrefixes
            ? $this->namedChildren($element)
            : $element->children();
        foreach ($children as $childName => $child) {
            if ($hint < $orderedCount && $ordered[$hint][0] === $childName) {
                $entry = $ordered[$hint][1];
                $hint++;
            } else {
                $entry = $info->childMembers[$childName] ?? null;
                if ($entry === null) {
                    continue;
                }
                $scanLimit = $hint + 4 < $orderedCount
                    ? $hint + 4
                    : $orderedCount;
                for ($i = $hint; $i < $scanLimit; $i++) {
                    if ($ordered[$i][0] === $childName) {
                        $hint = $i + 1;
                        break;
                    }
                }
            }
            switch ($entry[0]) {
                case 0:
                    $idx = $entry[1];
                    switch ($entry[3]) {
                        case ShapeType::String_->value:
                            $out[$idx] = (string) $child;
                            break;
                        case ShapeType::Enum->value:
                            $value = (string) $child;
                            $targetClass = $entry[2]->targetClass;
                            $out[$idx] = $targetClass !== null
                                ? $targetClass::fromValue($value)
                                : $value;
                            break;
                        case ShapeType::Integer->value:
                        case ShapeType::Long->value:
                        case ShapeType::Short->value:
                        case ShapeType::Byte->value:
                            $out[$idx] = (int) (string) $child;
                            break;
                        case ShapeType::IntEnum->value:
                            $value = (int) (string) $child;
                            $targetClass = $entry[2]->targetClass;
                            $out[$idx] = $targetClass !== null
                                ? $targetClass::fromValue($value)
                                : $value;
                            break;
                        case ShapeType::Boolean->value:
                            $s = (string) $child;
                            $out[$idx] = $s === 'true' || $s === '1';
                            break;
                        case ShapeType::Float_->value:
                        case ShapeType::Double->value:
                            $s = (string) $child;
                            $out[$idx] = match ($s) {
                                'NaN' => NAN,
                                'Infinity' => INF,
                                '-Infinity' => -INF,
                                default => (float) $s
                            };
                            break;
                        case ShapeType::Blob->value:
                            $out[$idx] = \base64_decode(
                                (string) $child,
                                true
                            ) ?: '';
                            break;
                        case ShapeType::Timestamp->value:
                            $out[$idx] = (
                                ($this->honorTimestampFormatTrait
                                    ? $entry[4] ?? null
                                    : null
                                ) ?? $this->settings->defaultTimestampFormat
                            )->parse((string) $child);
                            break;
                        case ShapeType::Structure->value:
                            $out[$idx] = $this->readNestedStructFromElement(
                                $child,
                                $entry[2]
                            );
                            break;
                        case ShapeType::Union->value:
                            $out[$idx] = $this->readUnionFromElement(
                                $child,
                                $entry[2]
                            );
                            break;
                        case ShapeType::List_->value:
                            $out[$idx] = $this->readListFromElement(
                                $child,
                                $entry[2]
                            );
                            break;
                        case ShapeType::Map->value:
                            $out[$idx] = $this->readMapFromElement(
                                $child,
                                $entry[2]
                            );
                            break;
                        default:
                            $out[$idx] = (string) $child;
                            break;
                    }
                    break;
                case 2:
                    $out[$entry[1]][] = $this->readValueFromElement(
                        $child,
                        $entry[3]
                    );
                    break;
                case 3:
                    if ($this->hasNamespacePrefixes) {
                        $fields = $this->indexedChildren($child);
                        $keyElement = $fields[$entry[3]] ?? null;
                        if ($keyElement !== null) {
                            $key = (string) $keyElement;
                            $valueElement = $fields[$entry[4]] ?? null;
                            $out[$entry[1]][$key] = $valueElement !== null
                                ? $this->readValueFromElement(
                                    $valueElement,
                                    $entry[5]
                                )
                                : null;
                        }
                        break;
                    }
                    $key = isset($child->{$entry[3]})
                        ? (string) $child->{$entry[3]}
                        : null;
                    if ($key !== null) {
                        $out[$entry[1]][$key] = isset($child->{$entry[4]})
                            ? $this->readValueFromElement(
                                $child->{$entry[4]},
                                $entry[5]
                            )
                            : null;
                    }
                    break;
            }
        }
    }

    /**
     * Read a list value from an element.
     *
     * @return list<mixed>
     */
    private function readListFromElement(
        \SimpleXMLElement $element,
        Schema $schema,
        ?XmlSchemaExtension $info = null
    ): array
    {
        // Cache the XML extension key once per method.
        static $extKey = null;
        $extKey ??= XmlSchemaExtensionProvider::key();

        /** @var XmlSchemaExtension $info */
        $info ??= $schema->getExtension($extKey);

        $listMember = $info->listMemberSchema;
        $memberName = $info->listMemberName ?? 'member';
        $memberType = $info->listMemberType ?? ShapeType::String_->value;
        $result = [];
        $children = $this->hasNamespacePrefixes
            ? $this->children($element, $memberName)
            : $element->{$memberName};

        switch ($memberType) {
            case ShapeType::String_->value:
                foreach ($children as $child) {
                    $result[] = (string) $child;
                }
                break;
            case ShapeType::Enum->value:
                $class = $listMember->targetClass;
                foreach ($children as $child) {
                    $value = (string) $child;
                    $result[] = $class !== null
                        ? $class::fromValue($value)
                        : $value;
                }
                break;
            case ShapeType::Integer->value:
            case ShapeType::Long->value:
            case ShapeType::Short->value:
            case ShapeType::Byte->value:
                foreach ($children as $child) {
                    $result[] = (int) (string) $child;
                }
                break;
            case ShapeType::IntEnum->value:
                $class = $listMember->targetClass;
                foreach ($children as $child) {
                    $value = (int) (string) $child;
                    $result[] = $class !== null
                        ? $class::fromValue($value)
                        : $value;
                }
                break;
            case ShapeType::BigInteger->value:
                foreach ($children as $child) {
                    $result[] = new BigInteger((string) $child);
                }
                break;
            case ShapeType::BigDecimal->value:
                foreach ($children as $child) {
                    $result[] = new BigDecimal((string) $child);
                }
                break;
            case ShapeType::Boolean->value:
                foreach ($children as $child) {
                    $result[] = ($s = (string) $child) === 'true' || $s === '1';
                }
                break;
            case ShapeType::Float_->value:
            case ShapeType::Double->value:
                foreach ($children as $child) {
                    $s = (string) $child;
                    $result[] = match ($s) {
                        'NaN' => NAN,
                        'Infinity' => INF,
                        '-Infinity' => -INF,
                        default => (float) $s
                    };
                }
                break;
            case ShapeType::Timestamp->value:
                $fmt = ($this->honorTimestampFormatTrait
                    ? $info->listMemberTimestampFormat
                    : null
                ) ?? $this->settings->defaultTimestampFormat;
                foreach ($children as $child) {
                    $result[] = $fmt->parse((string) $child);
                }
                break;
            case ShapeType::Structure->value:
                $memberInfo = $info->listMemberExtension;
                foreach ($children as $child) {
                    $result[] = $this->readNestedStructFromElement(
                        $child,
                        $listMember,
                        $memberInfo
                    );
                }
                break;
            case ShapeType::Union->value:
                $memberInfo = $info->listMemberExtension;
                foreach ($children as $child) {
                    $result[] = $this->readUnionFromElement(
                        $child,
                        $listMember,
                        $memberInfo
                    );
                }
                break;
            default:
                foreach ($children as $child) {
                    $result[] = $this->readValueFromElement(
                        $child,
                        $listMember
                    );
                }
        }

        return $result;
    }

    /**
     * Read a map value from an element.
     *
     * @return array<string, mixed>
     */
    private function readMapFromElement(
        \SimpleXMLElement $element,
        Schema $schema,
        ?XmlSchemaExtension $info = null
    ): array
    {
        // Cache the XML extension key once per method.
        static $extKey = null;
        $extKey ??= XmlSchemaExtensionProvider::key();

        /** @var XmlSchemaExtension $info */
        $info ??= $schema->getExtension($extKey);

        $mapValue = $info->mapValueSchema;
        $keyName = $info->mapKeyName;
        $valueName = $info->mapValueName;
        $entryName = $info->mapEntryName;
        $result = [];
        if ($this->hasNamespacePrefixes) {
            $entries = $this->children($element, $entryName);
            $namespace = \count($entries) >= self::NATIVE_MAP_MIN_ENTRIES
                ? $this->uniformMapNamespace($element)
                : null;
            if ($namespace === null) {
                return $this->readPrefixedMapEntries($entries, $info);
            }
            $entries = $element->children($namespace)->{$entryName};
        } else {
            $entries = $element->{$entryName};
        }

        if ($mapValue !== null
            && $mapValue->type === ShapeType::Timestamp->value
        ) {
            $fmt = ($this->honorTimestampFormatTrait
                ? $info->mapValueTimestampFormat
                : null
            ) ?? $this->settings->defaultTimestampFormat;
            foreach ($entries as $entry) {
                $key = isset($entry->{$keyName})
                    ? (string) $entry->{$keyName}
                    : null;
                if ($key === null) {
                    continue;
                }
                $result[$key] = isset($entry->{$valueName})
                    ? $fmt->parse((string) $entry->{$valueName})
                    : null;
            }

            return $result;
        }

        foreach ($entries as $entry) {
            $key = isset($entry->{$keyName})
                ? (string) $entry->{$keyName}
                : null;
            if ($key !== null) {
                $result[$key] = isset($entry->{$valueName})
                    ? $this->readValueFromElement(
                        $entry->{$valueName},
                        $mapValue
                    )
                    : null;
            }
        }

        return $result;
    }

    /** Read a union value from an element. */
    private function readUnionFromElement(
        \SimpleXMLElement $element,
        Schema $schema,
        ?XmlSchemaExtension $info = null
    ): ?SerializableShapeInterface
    {
        // Cache the XML extension key once per method.
        static $extKey = null;
        $extKey ??= XmlSchemaExtensionProvider::key();

        /** @var XmlSchemaExtension|null $info */
        $info ??= $schema->getExtension($extKey);
        if ($info === null || $info->memberCount === 0) {
            $iterSchema = $schema->resolveTarget();
            $info = $iterSchema->getExtension($extKey);
        } else {
            $iterSchema = $schema;
        }
        $targetClass = $iterSchema->targetClass;
        $membersByXmlName = $info->unionMembersByXmlName;

        $children = $this->hasNamespacePrefixes
            ? $this->namedChildren($element)
            : $element->children();
        foreach ($children as $childName => $child) {
            $entry = $membersByXmlName[$childName] ?? null;
            if ($entry === null) {
                $value = (string) $child;

                return $targetClass !== null
                    ? $targetClass::unknown($childName, $value)
                    : UnionDocument::unknown(
                        $iterSchema,
                        $childName,
                        $value
                    );
            }

            $memberSchema = $entry[1];
            $value = $this->readValueFromElement($child, $memberSchema);

            if ($targetClass !== null) {
                return $info->hasIndexedUnionFactory
                    ? $targetClass::createByIndex($entry[0], $value)
                    : $targetClass::create(
                        $memberSchema->memberName,
                        $value
                    );
            }

            return UnionDocument::create(
                $iterSchema,
                $memberSchema->memberName,
                $value
            );
        }

        return null;
    }

    /**
     * Preserve document order and repeated local names across prefixes.
     *
     * @return \Generator<string, \SimpleXMLElement>
     */
    private function namedChildren(\SimpleXMLElement $element): \Generator
    {
        foreach ($element->xpath('./*') ?: [] as $child) {
            yield $child->getName() => $child;
        }
    }

    /**
     * Select prefixed children by local name.
     *
     * @return list<\SimpleXMLElement>
     */
    private function children(
        \SimpleXMLElement $element,
        string $name
    ): array
    {
        $matches = [];
        foreach ($element->xpath('./*') ?: [] as $child) {
            if ($child->getName() === $name) {
                $matches[] = $child;
            }
        }

        return $matches;
    }

    /**
     * Index the first child for each local name with one XPath traversal.
     *
     * @return array<string, \SimpleXMLElement>
     */
    private function indexedChildren(\SimpleXMLElement $element): array
    {
        $fields = [];
        foreach ($element->xpath('./*') ?: [] as $child) {
            $fields[$child->getName()] ??= $child;
        }

        return $fields;
    }

    /**
     * Use a native namespace view only when every map element shares its URI.
     */
    private function uniformMapNamespace(\SimpleXMLElement $element): ?string
    {
        foreach ($element->getNamespaces() as $namespace) {
            $element->registerXPathNamespace('_smithy_map', $namespace);
            if ($element->xpath('self::_smithy_map:*') !== []
                && $element->xpath(
                    '(.//*[not(self::_smithy_map:*)])[1]'
                ) === []
            ) {
                return $namespace;
            }
        }

        return null;
    }

    /**
     * Decode map keys and values from one traversal per prefixed entry.
     *
     * @param list<\SimpleXMLElement> $entries
     *
     * @return array<string, mixed>
     */
    private function readPrefixedMapEntries(
        array $entries,
        XmlSchemaExtension $info
    ): array
    {
        $mapValue = $info->mapValueSchema;
        $format = $mapValue?->type === ShapeType::Timestamp->value
            ? ($this->honorTimestampFormatTrait
                ? $info->mapValueTimestampFormat
                : null
            ) ?? $this->settings->defaultTimestampFormat
            : null;
        $result = [];
        foreach ($entries as $entry) {
            $fields = $this->indexedChildren($entry);
            $keyElement = $fields[$info->mapKeyName] ?? null;
            if ($keyElement === null) {
                continue;
            }
            $key = (string) $keyElement;
            $valueElement = $fields[$info->mapValueName] ?? null;
            $result[$key] = $valueElement !== null
                ? ($format !== null
                    ? $format->parse((string) $valueElement)
                    : $this->readValueFromElement($valueElement, $mapValue)
                )
                : null;
        }

        return $result;
    }

    /** Read a scalar or nested value from an element. */
    private function readValueFromElement(\SimpleXMLElement $element, ?Schema $schema): mixed
    {
        // Cache the XML extension key once per method.
        static $extKey = null;
        $extKey ??= XmlSchemaExtensionProvider::key();

        if ($schema === null) {
            return (string) $element;
        }

        switch ($schema->type) {
            case ShapeType::String_->value:
                return (string) $element;
            case ShapeType::Enum->value:
                $value = (string) $element;
                return $schema->targetClass !== null
                    ? ($schema->targetClass)::fromValue($value)
                    : $value;
            case ShapeType::Integer->value:
            case ShapeType::Long->value:
            case ShapeType::Short->value:
            case ShapeType::Byte->value:
                return (int) (string) $element;
            case ShapeType::IntEnum->value:
                $value = (int) (string) $element;
                return $schema->targetClass !== null
                    ? ($schema->targetClass)::fromValue($value)
                    : $value;
            case ShapeType::BigInteger->value:
                return new BigInteger((string) $element);
            case ShapeType::BigDecimal->value:
                return new BigDecimal((string) $element);
            case ShapeType::Boolean->value:
                $s = (string) $element;
                return $s === 'true' || $s === '1';
            case ShapeType::Float_->value:
            case ShapeType::Double->value:
                $s = (string) $element;
                return match ($s) {
                    'NaN' => NAN,
                    'Infinity' => INF,
                    '-Infinity' => -INF,
                    default => (float) $s
                };
            case ShapeType::Blob->value:
                return \base64_decode((string) $element, true) ?: '';
            case ShapeType::Timestamp->value:
                return (
                    ($this->honorTimestampFormatTrait
                        ? $schema->getExtension($extKey)?->timestampFormat
                        : null
                    ) ?? $this->settings->defaultTimestampFormat
                )->parse((string) $element);
            case ShapeType::Structure->value:
                return $this->readNestedStructFromElement($element, $schema);
            case ShapeType::Union->value:
                return $this->readUnionFromElement($element, $schema);
            case ShapeType::List_->value:
                return $this->readListFromElement($element, $schema);
            case ShapeType::Map->value:
                return $this->readMapFromElement($element, $schema);
            default:
                return (string) $element;
        }
    }

    /** Read a nested structure value from an element. */
    private function readNestedStructFromElement(
        \SimpleXMLElement $element,
        Schema $schema,
        ?XmlSchemaExtension $info = null
    ): ?SerializableShapeInterface
    {
        // Cache the XML extension key once per method.
        static $extKey = null;
        $extKey ??= XmlSchemaExtensionProvider::key();

        // Recursive stub members resolve through the target schema here.
        /** @var XmlSchemaExtension|null $info */
        $info ??= $schema->getExtension($extKey);
        if ($info === null || $info->memberCount === 0) {
            $resolved = $schema->resolveTarget();
            $info = $resolved->getExtension($extKey);
        } else {
            $resolved = $schema;
        }

        if ($info !== null) {
            $members = $info->membersTemplate;
        } else {
            $count = $resolved->getMemberCount();
            $members = $count > 0 ? \array_fill(0, $count, null) : [];
        }
        $this->readStructFromElement($element, $resolved, $members);

        if ($resolved->targetClass !== null) {
            return ($resolved->targetClass)::fromMembers($members);
        }

        return StructDocument::fromMembers($resolved, $members);
    }
}
