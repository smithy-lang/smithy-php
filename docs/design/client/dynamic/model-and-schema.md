# Dynamic Client Model and Schema Design

**Status:** Current as of September 30, 2026

**Package:** `smithy-php`

## Summary

The dynamic client converts a Smithy JSON AST into the same runtime `Schema`
graph consumed by protocols, codecs, plugins, and generated clients. The
conversion is lazy. Each client creates schemas as its service and operations
need them, then reuses those schema objects for the client lifetime.

```text
Smithy JSON AST
    |
    v
SmithyModel
    |
    v
SchemaTraitRegistry
    |
    v
SchemaConverter
    |
    v
TypeRegistry
    |
    +-> DynamicService
    +-> DynamicOperation
    +-> DocumentFactory
```

## Runtime Model

`SmithyModel` reads a Smithy JSON AST from a file, JSON string, or decoded
array. It provides the model queries required by the runtime:

- service discovery
- service and resource operation closure
- operation input, output, and error references
- effective members and traits inherited from mixins
- effective service and operation auth schemes
- original shape IDs retained by model transforms

`SmithyModel` is a runtime reader rather than a Smithy model assembler. It
expects a valid Smithy JSON AST. Its validation covers the representation
needed by the PHP runtime.

Prelude scalar shapes and `smithy.api#Unit` are available even when the AST
does not contain explicit definitions for them.

## Service Selection

The client accepts an explicit service shape ID. When the caller omits it,
the model must contain one service.

`DynamicService` resolves operations declared directly on the service and
operations reachable through modeled resources. It records the operation
shape ID under the operation's original short name.

Operations are created on first lookup and cached by name:

```text
getOperation("GetForecast")
    -> operation shape ID
    -> DynamicOperation
    -> cached DynamicOperation
```

This keeps client construction independent of the service operation count.

## Schema Conversion

`SchemaConverter` maps each Smithy shape to `Schema`:

| Smithy shape | Runtime schema data |
|---|---|
| scalar | Shape type and traits |
| list | Item member schema |
| map | Key and value member schemas |
| structure | Ordered member schemas |
| union | Ordered variant schemas |
| enum or intEnum | Modeled member names and values |
| operation | Operation traits and error map |
| service or resource | Shape type and traits |

The converter assigns member indices in effective modeled order. Protocols,
codecs, documents, and generated shapes use those integer indices for
positional access.

### Canonical shape IDs

Model transforms may attach an original shape ID. The converter stores that
original ID on the schema while `TypeRegistry` accepts both the transformed
model ID and canonical schema ID.

This lets error discriminators and operation metadata resolve a schema even
when a model transform renamed the source shape.

### Member schemas

A member schema presents the target shape's type and shape graph. It also
retains:

- the container-qualified member ID
- member name
- member index
- effective traits
- traits applied directly to the member
- a target schema or lazy target reference

Effective traits merge target traits with member traits. Member traits take
precedence. Direct-trait access remains available for behavior such as
Smithy defaults, where the declaration site matters.

### Recursive shapes

`SchemaReference` closes recursive graphs before the referenced target has
been converted. It resolves the target on first access and caches it.

```text
Node
  -> next: Node

Schema(Node)
  -> member(next)
       -> SchemaReference(Node)
```

The converter therefore creates one schema identity for a modeled shape and
does not duplicate recursive subgraphs.

## Trait Materialization

`SchemaTraitRegistry` converts raw trait nodes into runtime values.

Built-in serde and client traits receive dense integer keys on `Schema`.
Examples include:

- `smithy.api#http`
- `smithy.api#httpHeader`
- `smithy.api#jsonName`
- `smithy.api#xmlName`
- `smithy.api#timestampFormat`
- `smithy.api#required`
- `smithy.api#requestCompression`

Dense keys keep protocol and codec trait access on integer array offsets.

Every trait is also retained by shape ID. An unregistered trait becomes a
`DynamicTraitValue` containing its ID and raw value. Provider runtimes may
register a materializer and an optional dense schema key.

`AwsDynamicClient` uses this extension point for AWS traits such as
`aws.protocols#ec2QueryName` and `aws.protocols#httpChecksum`.

## Type Registry

`DynamicService` owns one model-backed `TypeRegistry`. The registry:

1. Checks its shape-ID cache.
2. Verifies that the model contains the requested shape.
3. Requests conversion from `SchemaConverter`.
4. Caches the schema under the requested and canonical IDs.

Operations share this registry. Service metadata, inputs, outputs, nested
shapes, and errors therefore resolve to the same schema objects.

Schema identity matters because schema extensions use the schema object as a
cache key.

## Dynamic Operation Metadata

`DynamicOperation` implements the same `OperationInterface` used by generated
operations. It exposes:

- operation, input, and output schemas
- ordered effective auth scheme IDs
- service and operation error schemas
- the service's canonical shape ID
- input and output streaming members
- input and output value factories
- a process-unique runtime identity

The runtime identity includes the service ID, operation ID, protocol ID, and
a process-monotonic sequence. It does not depend on recyclable PHP object
identifiers. Two dynamic clients can load the same shape IDs without
colliding in protocol or pipeline caches.

### Request targets

Request-target construction belongs to the selected protocol rather than the
dynamic client:

- RPC v2 CBOR derives `<Service>.<Operation>` from the canonical service
  shape ID and operation name.
- AWS JSON derives its target from service metadata and the operation name.
- AWS Query protocols derive the action from the operation name.
- HTTP binding protocols use the operation's `smithy.api#http` trait.

`DynamicOperation::getRequestTarget()` returns `null`. Protocols may still
honor a non-null explicit request target supplied by generated or custom
operation metadata, but dynamic operations provide the canonical service and
operation identities needed to derive the wire target.

## Documents

Dynamic inputs and outputs use schema-backed document types.

The internal `DocumentFactory` accepts a schema and PHP value for each
creation request. It handles:

- required members and defaults declared directly on members
- integer bounds
- enum and intEnum values
- one-entry unions
- lists and maps
- nested structures
- blobs, timestamps, big integers, and big decimals
- existing document and serializable shape values

Structure values are stored in `StructDocument` as a positional member array.
Nested document wrappers are created only when the inspection API requests
them. Protocol serialization reads the raw positional values through
`SerializableStructInterface`.

`DynamicOperation::createInputFromValue()` passes its input schema to the
factory. Protocol deserialization fills a positional member buffer and calls
`DynamicOperation::createOutput()`.

The complete Smithy default model also permits a member to inherit a default
from its target shape, while a direct `@default(null)` clears that inherited
value. Applying those effective defaults during dynamic input coercion
remains preview-release alignment work.

## Errors

Each operation registers errors declared by the service and by the operation.
Discriminators include:

- full modeled shape ID
- short shape name
- canonical shape ID after transforms
- provider-registered protocol discriminator values

`resolveErrorSchema()` performs a direct discriminator lookup. The protocol
then deserializes modeled members through the selected codec.

Without a generated exception class, modeled failures are represented by
`DocumentException`. The exception retains the error schema and positional
member values.

## Cache Lifetime

| Cache | Owner | Key |
|---|---|---|
| Converted schemas | `SchemaConverter` | Modeled shape ID |
| Resolved schemas | `TypeRegistry` | Requested and canonical shape ID |
| Dynamic operations | `DynamicService` | Operation name |
| Schema extensions | `Schema` | Extension key |
| Protocol operation metadata | Protocol instance | Operation runtime identity |

Mutable protocol caches are client-scoped. Built-in endpoint functions and
other immutable registrations may be shared, but a protocol instance that
retains operation metadata must remain reachable only through its owning
client. The generic default runtime creates fresh protocol instances for this
reason. Provider runtimes must preserve the same lifetime.

A dynamic client's reachable schema count is bounded by the loaded model.

## Model Trust Boundary

The model controls request paths, protocol traits, endpoint bytecode, auth
metadata, and value coercion. Applications should treat a Smithy JSON AST as
executable configuration and load it from a trusted source.

The endpoint BDD evaluator and serde implementations consume normalized
schema data. Unknown traits remain data unless a registered runtime component
interprets them.

## Invariants

1. One modeled shape resolves to one schema identity per converter.
2. Member indices remain stable for the schema lifetime.
3. Member schemas expose the target type and effective traits.
4. Direct member traits remain distinguishable from inherited target traits.
5. Generated and dynamic operations satisfy the same `OperationInterface`.
6. Protocols construct values through operation factories rather than target
   class names.
