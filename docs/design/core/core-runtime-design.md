# Core Runtime Design

**Status:** Current as of September 30, 2026

**Package:** `smithy/smithy-php` (`Smithy\Core\`)

## Scope

The Core package defines the model-independent values and contracts shared by
code generation, dynamic clients, codecs, protocols, and the client runtime.
It does not own wire formats, HTTP behavior, authentication, or transport.

## Components

| Area | Responsibility |
|---|---|
| `Model` | Read and query a Smithy JSON AST at runtime. |
| `Serde\Schema` | Represent shape metadata, members, targets, and traits. |
| `Serde\SchemaConverter` | Convert modeled shapes into a lazy runtime schema graph. |
| `Serde\TypeRegistry` | Resolve and cache schemas by modeled and canonical shape ID. |
| `Serde\SchemaExtensionKey` | Attach provider-owned computed metadata to a schema. |
| `Serde\ShapeSerializerInterface` | Typed value-to-wire dispatch contract. |
| `Serde\ShapeDeserializerInterface` | Schema-directed wire-to-value contract. |
| `Shape` | Runtime interfaces for structures, unions, enums, operations, and services. |
| `Shape\Document` | Schema-backed values for runtime-loaded shapes. |

## Schema Graph

`Schema` represents both root shapes and members. A root schema stores its
canonical shape ID and `ShapeType`. A member schema additionally stores its
modeled name, stable integer index, effective and direct traits, and a target
reference.

Structures and unions index members by both name and assigned integer index.
Collection schemas retain item or key/value schemas. Recursive references use
`SchemaReference` so graph construction does not require eager completion of
the target.

Dense integer trait keys serve known serde hot paths. Runtime-loaded schemas
also retain shape-ID keyed trait views so extensions and provider packages can
inspect open traits without adding every trait to `Schema`.

The schema graph must remain independent of generated data-object classes.
Generated schemas can name a target class at a serialization boundary, but
schema traversal and dynamic values depend only on Core contracts.

## Runtime Model Conversion

`SmithyModel` performs structural JSON-AST validation and supplies normalized
queries for services, operations, resources, members, traits, auth, errors,
and canonical `originalShapeId` values. It assumes the AST has already passed
full Smithy semantic validation.

`SchemaConverter` converts shapes lazily. It materializes dense and open trait
views through `SchemaTraitRegistry`, preserves canonical shape IDs, assigns
stable member indexes, prepares operation metadata needed by the runtime, and
uses references for recursive targets.

`TypeRegistry` is client-scoped. It resolves modeled or canonical IDs on
demand and reuses one `Schema` object for aliases of the same converted shape.

## Documents

Documents represent runtime-loaded Smithy values without generated classes.
Scalar document classes preserve Smithy type distinctions. `ListDocument` and
`MapDocument` preserve the collection kind even when empty.

`StructDocument` stores raw positional members and creates child documents
lazily when inspected. It implements `SerializableStructInterface`, so the
same protocol and codec paths accept generated structures and dynamic
documents.

`UnionDocument` preserves exactly one selected member, its modeled tag, and
its value. Schema-aware coercion must validate the selected member against the
current schema rather than trusting member indexes from another schema graph.

Documents distinguish a value schema from the schema used at the parent
serialization boundary. This allows a canonical structure or union value to
retain member-level traits when nested.

## Shape Contracts

Generated and dynamic values meet at a small set of interfaces:

- `SerializableShapeInterface` dispatches one value to a serializer
- `SerializableStructInterface` exposes a structure schema and positional
  member access
- `UnionInterface` exposes one union tag, member index, and value
- `EnumInterface` exposes a modeled backing value
- `OperationInterface` exposes operation schemas, auth, errors, streaming
  members, factories, and runtime identity
- `ServiceInterface` exposes service identity and operation lookup

These contracts keep codecs and protocols free of reflection and generated
class-specific branches.

## Schema Extensions

Schema extensions move repeated routing, lookup, and format work out of hot
request paths. A `SchemaExtensionKey` owns a unique integer slot and a provider.
The schema computes the value on first access and caches non-null results.
Providers that return `null` run again on the next access.

Providers own the extension value type and can derive it from schema traits,
targets, and members. Protocol and codec packages therefore add specialized
metadata without expanding the base `Schema` API.

See [Schema Extensions](schema-extensions.md) for provider and caching
invariants.

## Type Mapping

Generated PHP values and runtime documents follow one shared Smithy-to-PHP
mapping. Primitive shapes use native scalar values where PHP can preserve
their domain. `BigInteger`, `BigDecimal`, timestamps, blobs, enums, unions,
documents, and streaming values use dedicated contracts where a native scalar
would lose semantics.

See [Shape to PHP Type Mapping](../codegen/smithy-php-shape-to-php-type-mapping.md)
for the complete mapping.

## Invariants

- Member indexes are assigned by the schema rather than inferred from array
  iteration. Generated schemas preserve historical assignments when available.
- Generated and runtime-loaded schemas expose equivalent effective metadata.
- Canonical shape IDs survive model transforms through `originalShapeId`.
- Open trait support belongs in registries and extensions, not vendor-specific
  branches in Core.
- Document coercion validates against the destination schema graph.
- Codecs and protocols consume Core interfaces rather than concrete generated
  classes.

## Validation

Core tests cover schema construction and filtering, recursive references,
trait materialization, model queries, canonical IDs, document coercion,
structure and union behavior, numeric wrappers, and generated/runtime contract
parity.

Run:

```shell
vendor/bin/phpunit tests/Core
```

## Related Designs

- [Shape to PHP Type Mapping](../codegen/smithy-php-shape-to-php-type-mapping.md)
- [Schema Extensions](schema-extensions.md)
- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Code Generation](../codegen/smithy-php-code-generation.md)
- [Dynamic Client Model and Schema](../client/dynamic/model-and-schema.md)
