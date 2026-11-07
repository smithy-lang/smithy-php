# Smithy-PHP Shape to PHP Type Mapping

**Status:** Current as of September 30, 2026

**Package:** `smithy/smithy-php` (`Smithy\Codegen\`,
`Smithy\Core\Shape\`, and `Smithy\Core\Serde\`)

## Overview

This document defines how the smithy-php code generator maps Smithy shapes to
PHP types. The mappings account for PHP's type system, runtime cost, and
forward-compatibility requirements.

`PhpSymbolProvider` owns PHP type hints and class names.
`StructureGenerator`, `EnumGenerator`, and `UnionGenerator` own the generated
value classes. Core supplies the shared runtime values and serde contracts
used by generated and dynamic clients.

Service and operation shapes are metadata rather than member value types.
`ServiceGenerator` emits a `<Service>Service` metadata class, and
`OperationGenerator` emits an `<Operation>Operation` metadata class. Resource
shapes do not produce standalone classes. Their operations are included in
the enclosing service closure. Mixins contribute members to their consumers
but do not produce standalone classes. When an operation references
`smithy.api#Unit`, codegen emits the shared empty `Unit` structure.

## Value Type Mapping

| Smithy shape | PHP representation | Object-backed? |
|---|---|---|
| string | `string` | No |
| boolean | `bool` | No |
| byte, short, integer, long | `int` | No |
| float, double | `float` | No |
| bigInteger | `Smithy\Core\Shape\BigInteger` | Yes |
| bigDecimal | `Smithy\Core\Shape\BigDecimal` | Yes |
| blob | `string` | No |
| blob member with `@httpPayload` | `string\|StreamInterface` | No |
| timestamp | `float` | No |
| document | `DocumentInterface` | Yes |
| list | `array` | No |
| map | `array` | No |
| enum | Generated class | Yes |
| intEnum | Generated class | Yes |
| structure | Generated class | Yes |
| union | Generated abstract class and variants | Yes |

## Scalar Types

### String

Smithy strings map to PHP's native `string`. PHP strings preserve arbitrary
bytes, so no generated wrapper is required.

### Boolean

Smithy booleans map to PHP's native `bool`.

Members with a renderable non-null default use `bool`. Other members use
`?bool` and default to `null`, including members marked `@required`.

### Numbers (byte, short, integer, long, float, double)

Smithy's six numeric types collapse into `int` and `float`. PHP has no distinct
byte, short, or long types, so all four integer widths use `int`. Smithy float
and double shapes use `float`.

### Blob

Ordinary blobs use PHP strings as byte sequences. Blob members carrying
`@httpPayload` accept `string|StreamInterface`, allowing request and response
payloads to stream without changing the type of ordinary blob members.

Raw PHP resources are not accepted. Resource ownership is ambiguous, and the
runtime cannot safely decide when to close a caller-owned handle.

### Timestamp

Timestamps expose epoch seconds as `float`. The selected wire format does not
change the generated PHP type.

`ArrayShapeSerializer::fromArray()` accepts numeric epoch values,
`DateTimeInterface`, and parseable timestamp strings. Date-time objects and
strings are converted to integral epoch seconds before constructor invocation.
The generated float parameter accepts those whole-second values.

### Document

Documents preserve Smithy types that share a PHP representation, including
strings and blobs or lists and maps. Each codec chooses the wire encoding.

Generated document members use
`Smithy\Core\Shape\Document\DocumentInterface`. The runtime implementations
extend `AbstractDocument`, which supplies default extraction behavior and
factory methods.

One concrete subclass per Smithy data-model type lives under `Smithy\Core\Shape\Document\`:

| Subclass                                        | Holds                                              | `as*()`                                    |
| ----------------------------------------------- | -------------------------------------------------- | ------------------------------------------ |
| `StringDocument`                                | `string`                                           | `asString`                                 |
| `BooleanDocument`                               | `bool`                                             | `asBoolean`                                |
| `IntegerDocument`                               | `int`                                              | `asInteger`, `asLong`, `asShort`, `asByte` |
| `LongDocument`, `ShortDocument`, `ByteDocument` | `int` (Short and Byte range-check at construction) | inherit from `IntegerDocument`             |
| `FloatDocument`, `DoubleDocument`               | `float`                                            | `asFloat`, `asDouble`                      |
| `BigIntegerDocument`                            | `BigInteger` wrapper                               | `asBigInteger`                             |
| `BigDecimalDocument`                            | `BigDecimal` wrapper                               | `asBigDecimal`                             |
| `BlobDocument`                                  | raw `string` bytes                                 | `asBlob`                                   |
| `TimestampDocument`                             | `float` epoch seconds                              | `asTimestamp`                              |
| `ListDocument`                                  | `list<AbstractDocument\|null>`                     | `asList`                                   |
| `MapDocument`                                   | `array<string, AbstractDocument\|null>`            | `asMap`, `getMember(string)`               |
| `StructDocument`                                | schema plus raw positional member values           | dispatches `serializeStruct`               |
| `UnionDocument`                                 | schema plus one tagged raw value                   | dispatches `serializeUnion`                |

Coercion methods on `AbstractDocument` throw
`IncompatibleDocumentTypeException` by default. Each subclass overrides the
calls supported by its data-model type. `IntegerDocument::asShort()` and
`asByte()` enforce their ranges. `ShortDocument` and `ByteDocument` enforce the
same ranges at construction.

An absent or explicit null child is represented by `null`. There is no
`NullDocument`. Collection and aggregate accessors therefore return nullable
child documents.

Static factories build documents from PHP runtime values:

```php
$doc = AbstractDocument::fromString('hello');
$doc = AbstractDocument::fromTimestamp(1_700_000_000);
$doc = AbstractDocument::fromValue([
    'name' => 'Scully',
    'tags' => ['x', 'y'],
    'photo' => new BlobValue($pngBytes),
    'born' => new TimestampValue(946684800)
]);
$name = $doc->getMember('name')->asString();
$tags = $doc->getMember('tags')->asList();
```

`fromValue(mixed)` walks `\stdClass`, list arrays, and map arrays, picking
subclasses by PHP runtime type. The `BlobValue` and `TimestampValue` marker
wrappers exist for this generic conversion only, where PHP's runtime type is
too narrow to disambiguate. A blob and a string are both `string` to PHP. The
typed factories (`fromBlob`, `fromTimestamp`) need no markers.

### BigInteger and BigDecimal

Smithy's `bigInteger` and `bigDecimal` represent arbitrary-precision values.
PHP has no native equivalent, so the runtime provides string-backed
`BigInteger` and `BigDecimal` wrappers.

`BigInteger` accepts an optional minus sign followed by digits, including
leading zeros. `BigDecimal` follows the JSON number grammar and rejects leading
zeros in the integer part.

XML writes the validated string as text. JSON writes it as an unquoted number.
CBOR writes large integers with tag 2 or 3 and writes decimals with decimal
fraction tag 4. A decimal's exponent or mantissa may itself use a bignum tag
when it exceeds the signed 64-bit range.

## Collection Types

### List

Lists map to sequential PHP arrays. PHPDoc records the element type for static
analysis, such as `list<Dimension>|null`.

The `@sparse` trait permits null elements. PHP arrays support null values, and
the schema retains the trait for protocol behavior.

### Map

Maps use PHP arrays with string keys. PHPDoc records the key and value types.
The runtime does not enforce map constraint traits before transmission.

The `@sparse` trait permits null map values.

## Named Types

### Structure

Structures are the primary generated type. Each becomes a `final class`.
Ordinary modeled members use public readonly constructor properties. Mutable
pagination token and page-size members use private writable storage exposed
through typed copy methods, and modeled error members that collide with the
exception hierarchy use private storage. Every generated structure implements
`SerializableStructInterface`. The interface exposes `getSchema()`,
`serialize()`, `serializeMembers()`, `getMemberValue()`, and
`withMemberValue()`. Generated classes also expose the static `schema()`
accessor used by registries and array conversion.

Non-defaulted members accept `null` at the generated client boundary.
Defaulted members use their modeled PHP literal. Constraint validation remains
the service's responsibility.

Fresh generation orders `@required` members first, defaulted members second,
and optional members last. The assigned `memberIndex` follows that generated
order. Regeneration preserves historical slots and appends new members within
the appropriate partition.

`FromMembersTrait` provides the internal `fromMembers()` construction path.
The deserializer fills a positional member array, one slot per
`memberIndex`, and passes the pre-hydrated values to the constructor.

```php
final class CopyObjectRequest implements SerializableStructInterface
{
    use FromMembersTrait;

    public function __construct(
        public readonly ?string $bucket = null,
        public readonly ?string $copySource = null,
        public readonly ?string $key = null,
        public readonly ?string $aCL = null
    ) {}
}
```

#### Array Conversion

`Smithy\Core\Shape\Utils\ArrayShapeSerializer` converts between generated
shape objects and the V3-compatible associative-array representation.
Generated structures can opt into shape-local `fromArray()` and `toArray()`
methods through `ArraySerializableTrait`. The trait delegates both methods to
`ArrayShapeSerializer`.

##### `fromArray()`

```php
$req = ArrayShapeSerializer::fromArray(
    CopyObjectRequest::class,
    [
        'Bucket' => 'my-target-bucket',
        'CopySource' => 'my-source-bucket/path/to/object',
        'Key' => 'destination/key',
        'MetadataDirective' => 'COPY'
    ]
);
```

When array serialization is enabled for the structure's category, the
equivalent call is:

```php
$req = CopyObjectRequest::fromArray([
    'Bucket' => 'my-target-bucket',
    'CopySource' => 'my-source-bucket/path/to/object',
    'Key' => 'destination/key',
    'MetadataDirective' => 'COPY'
]);
```

Input keys are modeled `memberName` values. The utility converts each key to
its camelCase property name with `lcfirst($memberName)` when building the
named constructor arguments.

Missing keys are not passed to the constructor. The parameter's PHP default
applies. An explicit `null` for an optional member is preserved.

The utility is V3-compatible at the input boundary. It coerces accepted array
values into the generated constructor types. The coercion rules track
[V3's `Validator`](https://github.com/aws/aws-sdk-php/blob/master/src/Api/Validator.php):

| Member type | Accepted input | Coercion |
|---|---|---|
| `string` | string, integer, float, stringable object | String cast for non-string scalars |
| Integer family | integer, float, numeric string | Integer cast when numeric |
| `blob` | string or stringable object | String cast for stringable objects |
| `document` | scalar, array, `stdClass`, `AbstractDocument` | `AbstractDocument::fromValue()` |
| `bigInteger`, `bigDecimal` | decimal string or matching wrapper | Wrapper construction |
| `timestamp` | numeric epoch, string, `DateTimeInterface` | Strings and date-time objects become epoch seconds |
| `float`, `double` | numeric or named non-finite string | Float conversion |
| `enum`, `intEnum` | wire scalar or generated enum | `fromValue()` for wire scalars |
| Structure or union | associative array | Recursive structure hydration |
| List or map | array | Element-wise hydration |

##### `toArray()`

`ArrayShapeSerializer::toArray()` produces the V3-equivalent associative
array. Scalars pass through, structures and unions recurse, enums and
big-numerics unwrap to their `value`, and typed list and map values convert
element by element.

```php
$resp = $client->headObject(...);
$plain = ArrayShapeSerializer::toArray($resp);
```

Structures that use `ArraySerializableTrait` can call `$resp->toArray()`
instead.

##### `ArrayAccess` for output shapes

Output shapes can opt into PHP's `ArrayAccess` through
`ReadonlyArrayAccessTrait`. This permits `$resp['ContentLength']` reads. Write
and unset operations throw.

### Error Structure

Structures with the `@error` trait generate as exception classes extending the
generated per-service exception. That service exception extends
`Smithy\Core\Exception\ApiException` by default, or the parent selected by
`baseServiceException`. Modeled errors carry an `ErrorFault` indicating
client or server origin. The generated schema retains `@retryable` metadata
for retry-policy integrations. The base exception hierarchy does not expose a
separate retryable flag. Errors implement `SerializableStructInterface` and
use the same `FromMembersTrait` hydration path as other structures.

```php
final class AllTypesErrorException extends ExampleException implements
    SerializableStructInterface
{
    // ...
}
```

### Enum

Generated enum classes accept unknown values for forward compatibility. Known
values use cached singletons. Unknown values create uncached instances so newer
service values can round-trip without growing a process-wide cache.

PHP native enums cannot preserve unknown values. `from()` throws and
`tryFrom()` discards the value. Generated classes therefore expose
`fromValue()` and named factories.

```php
final class NestedEnum implements EnumInterface
{
    private static array $known = [];

    public readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function a(): self
    {
        return self::$known['A'] ??= new self('A');
    }

    public static function b(): self
    {
        return self::$known['B'] ??= new self('B');
    }

    public static function fromValue(string $value): self
    {
        return match ($value) {
            'A' => self::$known['A'] ??= new self('A'),
            'B' => self::$known['B'] ??= new self('B'),
            default => new self($value)
        };
    }

    public static function cases(): array
    {
        return [self::a(), self::b()];
    }
}
```

### IntEnum

Integer enums use the same pattern with `int` values.

```php
final class NestedIntEnum implements EnumInterface
{
    private static array $known = [];

    public readonly int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function a(): self
    {
        return self::$known[1] ??= new self(1);
    }

    public static function b(): self
    {
        return self::$known[2] ??= new self(2);
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            1 => self::$known[1] ??= new self(1),
            2 => self::$known[2] ??= new self(2),
            default => new self($value)
        };
    }

    public static function cases(): array
    {
        return [self::a(), self::b()];
    }
}
```

### Union

Unions use an abstract readonly base class with one concrete class per variant.
Each variant stores a typed value. An `Unknown` variant preserves unrecognized
variants from newer service versions.

`tag()` returns the variant name. `value()` uses a covariant return type in each
known variant. `create()` dispatches a tag and value to the matching class.

```php
abstract readonly class NestedUnion implements UnionInterface
{
    public static function a(string $value): self
    {
        return new NestedUnionA($value);
    }

    public static function b(int $value): self
    {
        return new NestedUnionB($value);
    }

    public static function unknown(string $tag, mixed $value): self
    {
        return new NestedUnionUnknown($tag, $value);
    }
}
```

## Recursive Shapes

Generated member schemas use `targetClass` for class-backed targets such as
structures, unions, and enums. For recursive structures and unions, that class
reference also forms the lazy resolution boundary: the member schema avoids
embedding the target members, and consumers call `resolveTarget()` when they
need the complete target schema.

```php
structure TreeNode {
    value: String
    left: TreeNode   // recursive
    right: TreeNode  // recursive
}
```

Generates a normal class with nullable self-referencing properties:

```php
final class TreeNode implements SerializableStructInterface
{
    public function __construct(
        public readonly ?string $value = null,
        public readonly ?TreeNode $left = null,
        public readonly ?TreeNode $right = null
    ) {}
}
```

## Member Optionality

Members fall into three buckets that map to constructor parameter shape:

- Required members without defaults are nullable and default to null.
- Members with renderable non-null defaults are non-nullable and use the
  modeled literal.
- Optional members are nullable and default to null.

Fresh generation places these groups in the listed order. The Smithy
`NullableIndex` determines member presence semantics. Codegen reduces that
information to the generated constructor groups.

### `@default` Members

Members with `@default(value)` emit as non-nullable parameters with a literal
PHP default:

```php
public function __construct(
    public readonly ?string $bucket = null,             // @required
    public readonly int $maxKeys = 1000,                // @default(1000)
    public readonly string $delimiter = '/',            // @default("/")
    public readonly bool $listV2 = true,                // @default(true)
    public readonly array $tags = [],                   // @default([])
    public readonly ?string $continuationToken = null   // optional
) {}
```

`@default(null)` clears an inherited default. Codegen places the member in the
optional group.

`ArrayShapeSerializer::fromArray()` and `FromMembersTrait::fromMembers()` use
the constructor's literal default when input omits the member. Generated
objects do not retain a separate marker that distinguishes an omitted member
from the same explicitly supplied default value. Serializers therefore receive
the materialized value.

Enum and intEnum members use explicit properties because PHP constructor
defaults must be constant expressions. The constructor accepts the generated
enum or its backing value and normalizes it once.

```php
public readonly Status $status;
public readonly Priority $priority;

public function __construct(
    Status|string $status = 'ACTIVE',
    Priority|int $priority = 1
)
{
    $this->status = $status instanceof Status
        ? $status
        : Status::fromValue($status);
    $this->priority = $priority instanceof Priority
        ? $priority
        : Priority::fromValue($priority);
}
```

## Constraint Traits

Constraint traits such as length, pattern, range, and uniqueItems do not change
generated types. Codegen does not emit client-side validation for these
traits. The service remains authoritative.

## Performance Considerations

The mapping favors native PHP scalars and arrays when they preserve the Smithy
value domain. Dedicated objects are reserved for values that need additional
semantics, including arbitrary-precision numbers, documents, enums, unions,
and structures.

Known enum values are cached as singletons, while unknown values remain
uncached to preserve forward compatibility without growing process-wide state.
Generated serializers and positional member indexes avoid reflection in
request and response hot paths.

Machine-specific benchmark results are not part of this design contract.
Performance changes should be evaluated with a reproducible, checked-in
benchmark before changing a public type mapping.

## Related Designs

- [Code Generation](smithy-php-code-generation.md)
- [Core Runtime](../core/core-runtime-design.md)
- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Serialization Codecs](../codec/serialization-codecs.md)
