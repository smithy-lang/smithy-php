# Smithy-PHP: Code Generation

**Status:** Current as of September 30, 2026.

**Package:** `smithy/smithy-php` (`Smithy\Codegen\`)

## Scope

This document describes the architecture and generated contracts of the
smithy-php code generator. The namespace README is the user-facing guide for
installation, command-line usage, and the PHP API. This document is for
contributors who need to understand where generation decisions belong, how
generated artifacts interact with the runtime, and which compatibility
constraints must be preserved.

The generator consumes a Smithy JSON AST. It is not a replacement for the
Smithy reference validator: callers are expected to provide a valid model
produced by a Smithy build. `SmithyModel` performs basic loading checks and the
generator validates requirements specific to emitted PHP, including service
count, pagination metadata, and waiter definitions.

## Overview

The generator emits the static PHP representation of one service closure:

- structure, enum, union, and `smithy.api#Unit` shape classes
- operation metadata singletons
- service metadata
- a typed service client and service-specific client configuration
- a centralized `Schemas` registry and a per-service `TypeRegistry`
- a service exception base when configured
- endpoint bytecode and an optional generated endpoint cache-key builder

Generated shape classes implement the runtime serde contracts described in
[Serialization and Deserialization](../serde/serialization-and-deserialization.md). They do
not contain protocol-specific JSON, XML, CBOR, or HTTP behavior. Protocols
interpret generated schemas and operation metadata at runtime.

The generated and dynamic paths intentionally share those runtime contracts.
Generated code precomputes model decisions for the static hot path. The dynamic
client builds equivalent schemas and operation metadata from a model at runtime.

See [Shape to PHP Type Mapping](smithy-php-shape-to-php-type-mapping.md) for
the generated value, constructor, and forward-compatibility contracts.

## Tenets

1. **Static decisions belong at codegen time.** Member order, member indexes,
   PHP symbols, operation metadata, plugin requirements, and endpoint bytecode
   are derived once and emitted as PHP.

2. **Generated shapes are protocol-independent.** Shapes expose schemas and
   typed serde methods. Codecs and protocols determine wire representation.

3. **Generated code accelerates runtime contracts rather than replacing
   them.** A protocol can work with generated shapes or runtime documents
   through the same schema and operation interfaces.

4. **Output is deterministic for the same inputs.** A fixed JSON AST,
   `CodegenSettings`, and prior generated output produce stable source.

5. **Regeneration must preserve positional contracts by default.** Existing
   constructor slots and schema member indexes are treated as generated ABI.

## Architecture

The implementation is divided into five cooperating areas:

| Area | Primary types | Responsibility |
|---|---|---|
| Driver | `CodeGenerator`, `CodegenSettings` | Validate configuration, assemble generators, order artifacts, and write files |
| Model | `SmithyModel`, `WaiterTraitNormalizer` | Query the JSON AST, resolve mixin members and service closures, and normalize codegen-specific metadata |
| Symbols | `PhpSymbolProvider`, `PhpType`, `ShapeCategory` | Map Smithy IDs and members to PHP classes, properties, namespaces, and type hints |
| Generators | `StructureGenerator`, `EnumGenerator`, `UnionGenerator`, `OperationGenerator`, `ServiceGenerator`, `ClientGenerator`, and related generators | Emit one artifact family from model and settings |
| Output | `PhpCodeWriter`, `GeneratedFile` | Render PHP source, normalize imports, and write target files |

Generators do not consume each other's generated source. Shared decisions flow
through `SmithyModel`, `PhpSymbolProvider`, `CodegenSettings`, and, for
regeneration, `PriorCodegenEvaluator`.

### Generation flow

`CodeGenerator::generate()` performs the following work:

1. `CodegenSettings` validates configuration when it is constructed.
2. `CodeGenerator` inspects a prior `Schemas.php`, when member-order
   preservation is enabled, and constructs the shared generators.
3. `generate()` validates that the model contains at most one non-prelude
   service and builds the service closure.
4. Emit reachable structures, enums, and union hierarchies.
5. Emit service metadata, the type registry, endpoint artifacts, the typed
   client, and client configuration.
6. Emit reachable operation metadata classes.
7. Emit `Unit` when an operation omits input/output or references
   `smithy.api#Unit`.
8. Emit the centralized `Schemas` registry.

Models without a service are supported for standalone-shape generation and
tests. In that case every declared non-prelude shape seeds the closure.

`generateAndWrite()` writes each `GeneratedFile` after generation succeeds and
returns the number of files written.

## Model layer

`SmithyModel::fromFile()` and `SmithyModel::fromJson()` load the JSON AST. The
model retains the original shape maps and provides codegen-oriented queries.

Important model responsibilities include:

- resolving mixin members recursively, with local members overriding inherited
  members
- classifying structures as input, output, shared shape, or error
- resolving service operations, including operations reachable through
  resources and nested resources
- calculating effective service/operation authentication schemes
- merging and validating service/operation pagination metadata
- indexing pagination token and page-size members
- normalizing and validating waiters and acceptors
- preserving declaration order where it has runtime meaning

The model does not perform full Smithy semantic validation. Validation that is
already guaranteed by a Smithy build should remain upstream. Validation belongs
in the PHP layer when it protects a PHP code-generation invariant or prevents
the generator from emitting an unusable runtime contract.

### Service closure

The service closure begins with every operation bound to the service. A graph
walk follows:

- operation input, output, and modeled errors
- structure and union member targets
- mixin parents
- list member targets
- map key and value targets

Prelude shapes are excluded. Reachability is not restricted by namespace,
because services commonly reference shared shapes declared elsewhere.

Mixin shapes themselves are not emitted, but their resolved members participate
in generated structures and unions.

## Symbols and file organization

`PhpSymbolProvider` is the single source of truth for generated PHP names.

- The local part of a shape ID is the initial class name.
- Error structures receive the `Exception` suffix.
- PHP-reserved class names are prefixed with the configured service short name.
- When multiple shape IDs collapse to the same short name, the alphabetically
  first shape keeps the short name and later shapes receive a deterministic
  prefix derived from their final namespace segment.
- Member names are converted to legal PHP property names.
- An `@httpPayload` blob member can use `string|StreamInterface`.
- Ordinary blobs remain strings.

The `flat` organization places generated shape classes directly in the
configured namespace. The `categorized` organization uses configured
sub-namespaces for inputs, outputs, shared shapes, errors, operations, and
endpoint artifacts.

Shape categorization controls placement and error inheritance:

| Category | Condition | Default placement |
|---|---|---|
| `Input` | Used as operation input but not output | `Input/` |
| `Output` | Used as operation output but not input | `Output/` |
| `Shape` | Shared between roles or only transitively reachable | `Shape/` |
| `Error` | Carries `smithy.api#error` | `Exception/` |

## Generated artifacts

### Structures

Every generated structure implements `SerializableStructInterface`. The
generated class includes:

- `SHAPE_ID` and optional configured shape constants
- a constructor using generated PHP types
- `schema()` and `getSchema()`
- `serialize()` and an unrolled `serializeMembers()`
- positional `getMemberValue()`
- `fromMembers()` and `withMemberValue()` through `FromMembersTrait`

The centralized `Schemas` class owns canonical schema construction. A
structure's `schema()` method memoizes that schema locally and, when the shape
has members, caches the member schemas used by unrolled serialization calls.

On a fresh generation, constructor and member-index order is:

1. required members without defaults
2. members with renderable non-null defaults
3. optional members

On regeneration, historical members retain their existing slots and new
members append in the fresh-order partition. See
[Regeneration and ABI stability](#regeneration-and-abi-stability).

Most members are emitted as readonly promoted properties. Scalar enum members
use an explicit readonly property so the constructor can accept either an enum
instance or its wire value and normalize it once. Pagination token and
page-size members use private writable storage so immutable `with...()` methods
can clone the request and update the continuation state.

Additional behavior is generated only when needed:

- modeled pagination inputs implement `PaginatedRequestInterface` and, when
  applicable, `PageSizePaginatedRequestInterface`
- configured categories can implement readonly `ArrayAccess`
- structures containing sensitive data use `SensitiveStringableTrait`
- modeled errors extend the configured service exception or runtime API
  exception and preserve modeled members that collide with inherited
  exception properties

Associative-array conversion is centralized in `ArrayShapeSerializer`.
Generated structures do not each emit `fromArray()` or `toArray()`.

### Enums

Smithy `enum` and `intEnum` shapes produce final classes implementing
`EnumInterface`.

Each enum provides:

- a private constructor and readonly backing value
- a named factory for every modeled value
- `fromValue()` accepting both known and unknown values
- cached singleton instances for known values
- `cases()` for the modeled values
- schema and serialization methods

Unknown values remain representable for forward compatibility. String enums
also implement `__toString()`.

### Unions

A Smithy union produces:

- an abstract readonly base implementing `UnionInterface`
- one internal final readonly class per modeled variant
- an internal unknown-variant class

The base provides named factories, `unknown()`, schema access, serialization,
`create($tag, $value)`, and `createByIndex($index, $value)`. Variants expose
their tag, member index, and typed value.

Creation methods normalize nested generated structures, unions, and enums
through their runtime conversion contracts. The member index is emitted so
codecs do not need to search the schema by tag on the generated path.

### Operations

Every operation produces a final singleton implementing `OperationInterface`.
It exposes:

- operation, service, input, and output identities
- the ordered effective authentication scheme IDs
- input, output, and modeled error schemas
- input/output construction from hydrated positional members
- modeled error discriminator lookup
- optional configured request target and operation constants
- input/output streaming-member metadata
- operation schema traits used by protocols and the client pipeline

The operation schema can contain precomputed HTTP binding metadata, pagination
metadata, error metadata, checksum requirements, request-compression
encodings, idempotency/read-only markers, and configured extension traits.
HTTP path members use preserved member indexes so protocol binding remains
compatible across regeneration.

Selected string-keyed traits that cannot be represented by a global packed
index, such as endpoint context parameters, are preserved in
`Schema::$directTraitsById`.

### Service metadata, client, and client configuration

`ServiceGenerator` emits a metadata class implementing `ServiceInterface`. It
contains the service name, shape ID, version, operation class list, and a
lazily initialized service schema. Configured schema traits are retained in
both packed and shape-ID keyed views. The metadata class is not the callable
client.

`ClientGenerator` emits the typed public client. Each operation method is a
thin wrapper around the configured base client's `executeOperation()` method
and accepts an optional `RequestOverrideConfig`.

The client generator also emits:

- paginator factory methods for validated `@paginated` operations
- waiter methods for normalized `smithy.waiters#waitable` definitions

Generated clients associate the runtime `ClientConfig` with the generated
service metadata before the shared base client resolves plugins. Modeled
request compression and HTTP checksum behavior is registered by the runtime;
its interceptors inspect the current operation schema when a request executes.
Dynamic clients use the same plugin path.

`ClientConfigGenerator` emits a service-specific subclass of the runtime
`ClientConfig`. `CodegenSettings::$clientBaseClass`, `generateClient`, and
`generateClientConfig` allow SDK layers to replace the base client or suppress
these artifacts.

### Service exceptions

When `serviceExceptionClass` is configured, the generator emits the
per-service exception base. Error structures extend that class. Without a
service-specific class, error structures use the configured base service
exception or the runtime `ApiException`.

### Schema and type registries

`SchemasGenerator` emits the canonical schema graph for the service closure.
Schema instances and member schemas are initialized lazily and referenced by
generated shape classes.

`TypeRegistryGenerator` emits two codegen-time maps:

- shape ID to generated class
- generated class to shape ID

The generated registry exposes `classFor()`, `shapeIdFor()`, `schemaFor()`, and
`all()`. Dynamic clients use the separate model-backed runtime `TypeRegistry`
and `SchemaConverter`.

### Endpoint artifacts

When a service contains `smithy.rules#endpointBdd`,
`EndpointBytecodeGenerator` compiles the BDD into a generated
`<Service>EndpointBytecode` class. Runtime code reconstructs the immutable
bytecode once. It does not parse or compile a ruleset for each process.

The compiler function registry contains:

1. the Smithy standard endpoint functions from `StdFunctions::all()`
2. caller-provided functions from `CodegenSettings::$rulesFunctions`, which
   override functions with the same name

SDK layers supply vendor functions explicitly through settings. Extended
opcodes are supplied through `CodegenSettings::$extendedOpcodes`. The core
generator does not probe for vendor packages.

When `generateEndpointKeyBuilder` is enabled, the endpoint generator also
emits `<Service>EndpointKeyBuilder`. Its memoized closure builds the same cache
key as the generic runtime resolver while avoiding a reflective walk over the
bytecode input register map.

`EndpointRulesetIntrospector` is a supporting utility for SDK generators. It
reads parameters from either endpoint BDD or endpoint ruleset traits and maps
them to PHP property metadata while allowing an SDK layer to exclude
base-owned parameters.

See [Endpoint Resolution](../client/endpoint/endpoint-resolution.md) for the runtime side.

## Trait representation and extension

Runtime schemas store common traits in integer-indexed arrays using
`Schema::T_*` constants. Generated schemas can read these with integer lookups
without allocating trait objects.

Structure and centralized-schema generation each maintain a built-in mapping
from Smithy trait IDs to packed schema indexes. `CodegenSettings` extends this
model in several ways:

- `schemaTraitIndices` maps additional trait IDs to packed indexes
- `operationSchemaTraits` supplies preprocessed operation trait values by
  integer index
- `shapeConstants` and `operationConstants` add SDK-owned generated constants

Traits whose null value is semantically distinct from absence cannot be tested
with `isset()` alone. The model and schema APIs use presence-aware checks where
Smithy permits explicit null, particularly around defaults.

Adding a built-in packed trait requires these steps:

1. assigning a unique `Schema::T_*` index
2. adding the trait ID/index mapping to every schema generator that can emit it
3. implementing the runtime behavior that consumes it
4. adding codegen and runtime coverage

SDK-specific traits should use settings or direct trait preservation when the
runtime can interpret them without making the core generator vendor-aware.

## Codegen-time and runtime decisions

| Decision | Codegen | First runtime use | Per request |
|---|:---:|:---:|:---:|
| PHP class/property naming | yes | | |
| Constructor and member-index order | yes | | |
| Effective auth scheme order | yes | | |
| Error discriminator maps | yes | | |
| Pagination and waiter method shape | yes | | |
| Default plugin requirements | yes | | |
| HTTP path-member indexes | yes | | |
| Endpoint BDD compilation | yes | | |
| Endpoint cache-key closure generation | yes | | |
| Canonical `Schema` construction | | yes, lazy | |
| Generated endpoint bytecode reconstruction | | yes, lazy | |
| Schema extension construction | | yes, lazy | |
| Request serialization and response deserialization | | | yes |
| Endpoint evaluation, signing, retries, and transport | | | yes |

Generated shape source remains protocol-independent. See
[Client Execution](../client/client-runtime-design.md) for runtime execution decisions.

## Regeneration and ABI stability

Constructor parameter positions and schema member indexes are observable
generated contracts. The runtime also embeds those indexes in operation
metadata for streaming members and HTTP path bindings.

When `preserveExistingMemberOrder` is enabled, `CodeGenerator` asks
`PriorCodegenEvaluator` to inspect the existing generated `Schemas.php`. The
evaluator tokenizes the file without loading or evaluating generated classes
and recovers historical member order by shape ID.

That order is consumed by three generators:

- `StructureGenerator` preserves constructor slots and local member schema
  caches
- `SchemasGenerator` preserves `getMembersByIndex()` ordering
- `OperationGenerator` preserves member indexes embedded in stream and HTTP
  path metadata

Members still present retain their prior positions. New members append after
historical members. Removed members disappear and their old slots collapse.
The upstream Smithy compatibility process is expected to prevent incompatible
model removals.

If prior output exists but cannot be inspected safely, generation fails rather
than silently reverting to fresh order. Set
`preserveExistingMemberOrder: false` only for first-time generation, tests, or
an intentional compatibility break.

The current mechanism preserves position by member name. It does not detect a
member that retains its name while changing target type.

## Output and determinism

`PhpCodeWriter` manages indentation, docblocks, and imports. Imports are
deduplicated and sorted before rendering. Generators sort public surfaces where
order is not modeled, such as generated client methods, while preserving Smithy
declaration order for metadata where order is meaningful.

`GeneratedFile` contains a target path and complete source string. Individual
files retain containment checks for callers that write them directly.

`CodeGenerator::generateAndWrite()` treats `outputDir` as a managed,
generated-only directory. Generation first completes in memory, then
`GeneratedOutputPublisher` writes every file to a unique sibling staging
directory. Publication moves the prior output to a backup, moves the staged
tree into place, and removes the backup. If publication fails, the prior tree
is restored. Replacing the complete tree removes stale artifacts without a
persistent generated-file manifest. To avoid adopting an arbitrary directory,
a nonempty existing output must contain a `Schemas.php` with the generated
registry signature. Symlink roots and roots containing the current working
directory are rejected before staging.
Staging begins with owner-only access, then receives the existing output
directory's permissions immediately before publication. If backup cleanup
fails after publication commits, the new output remains installed and the
error identifies the remaining backup.

Prior member ordering is inspected from the current `Schemas.php` when
`CodeGenerator` is constructed, before staging or publication can modify the
output directory. As with direct generated-file writes, callers must prevent
concurrent mutation of the configured output tree.

The writer intentionally emits plain source rather than a round-trippable PHP
AST. Generated diffs therefore remain close to the generator decisions that
caused them.

## Testing

Run the codegen suite from the smithy-php repository root:

```shell
vendor/bin/phpunit tests/Codegen
```

The suite covers:

- settings and command-line configuration
- model closure, mixins, auth schemes, pagination, and waiter validation
- structures, enums, unions, operations, services, clients, and client config
- schema and type registries
- endpoint bytecode, extended functions, and generated key builders
- source rendering and generated behavior
- prior-generation member-order preservation

SDK repositories add coverage for SDK-specific settings, protocols, vendor
traits, endpoint functions, and regenerated service clients. Exact test and
assertion counts are intentionally omitted because they are build results, not
design invariants.

## Related designs

- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Shape to PHP Type Mapping](smithy-php-shape-to-php-type-mapping.md)
- [Endpoint Resolution](../client/endpoint/endpoint-resolution.md)
- [Client Execution](../client/client-runtime-design.md)
- [Pagination](../client/pagination/pagination-runtime-design.md)
- [Waiters](../client/waiter/waiter-runtime-design.md)
- [Dynamic Client](../client/dynamic/dynamic-client-design.md)

## Open questions

- **Generated-source diagnostics.** Mapping generated PHP lines back to Smithy
  source locations would make generator and model failures easier to diagnose.

- **Trait registration.** Settings support explicit custom trait indexes and
  operation traits, but there is no discovery mechanism for independently
  distributed integrations.

- **Generated registry extension.** Generated type registries are immutable
  codegen-time maps. Supporting externally registered generated classes would
  require mutable runtime state.

- **Resource-oriented APIs.** Resource operations participate in the service
  closure, but generated clients expose the conventional flat operation
  surface rather than resource handle classes.

- **IDL frontend.** The generator consumes JSON AST and relies on the Smithy
  toolchain to parse and validate IDL.

- **Member target-change detection.** Regeneration preserves a slot by member
  name but does not yet diagnose a compatible-looking member whose target type
  changed.
