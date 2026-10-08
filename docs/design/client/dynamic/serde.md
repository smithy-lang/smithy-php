# Dynamic Client Serde Design

**Status:** Current as of September 30, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

## Summary

Dynamic clients serialize and deserialize schema-backed documents through the
same protocols and codecs used by generated clients. The dynamic path replaces
generated shape classes with `StructDocument` values and runtime
`DynamicOperation` metadata.

Serde correctness remains in the protocol, HTTP binding, and codec layers.
This document describes the dynamic-client serde architecture present in this
repository.

## Value Model

Operation inputs and outputs use `StructDocument`. Each document stores:

- a structure schema
- positional member values keyed by modeled member index

Raw positional values satisfy `SerializableStructInterface`. Protocols can
serialize a dynamic document through the same interface implemented by a
generated shape.

Child documents are created when callers use the document inspection API.
Ordinary protocol access reads the raw values and does not allocate child
wrappers.

## Input Coercion

`DynamicClient::call()` accepts an associative array, a `DocumentInterface`,
or `null`.

```text
caller value
    |
    v
DynamicOperation::createInputFromValue()
    |
    v
DocumentFactory
    (internal)
    |
    v
StructDocument
```

`DynamicOperation::createInputFromValue()` passes the operation input schema
and caller value to the internal `DocumentFactory`. The factory handles:

- required members and defaults declared directly on members
- integer bounds
- enums and intEnums
- one-entry unions
- lists, maps, and nested structures
- blobs and timestamps
- big integers and big decimals
- existing document and serializable shape values

Validation completes before endpoint resolution or HTTP transmission.

Target-shape defaults inherited through a member are not yet applied by the
dynamic coercer. Preview-release alignment must add effective default lookup
while preserving a direct `@default(null)` as an inherited-default override.

## Serialization

Protocols receive a `DynamicOperation` and a `StructDocument` through the
shared client pipeline.

```text
StructDocument
    |
    v
serializeMembers()
    |
    v
protocol serializer
    |
    +-> HTTP bindings
    +-> wire codec
    |
    v
HTTP request
```

`StructDocument::serializeMembers()` walks the schema's serialization members
in member-index order. It dispatches each present value to the matching method
on `ShapeSerializerInterface`.

The serializer uses the member schema at the current boundary. Member traits
therefore control wire names, timestamp formats, XML behavior, HTTP bindings,
and provider extensions in the same way they do for generated shapes.

## Deserialization

Protocols allocate a positional member buffer for the operation output
schema. Codec and HTTP binding deserializers populate that buffer.

```text
HTTP response
    |
    v
protocol deserializer
    |
    +-> HTTP bindings
    +-> wire codec
    |
    v
positional member buffer
    |
    v
DynamicOperation::createOutput()
    |
    v
StructDocument
```

`DynamicOperation::createOutput()` constructs the final document through the
operation factory contract. Protocols do not need a generated output class
name.

`DynamicClient::call()` verifies that the returned schema ID matches the
operation output schema. A mismatched shape raises a `LogicException`.

## HTTP Bindings

RestJson and RestXml divide operation members by their HTTP binding traits.
The shared HTTP binding implementation handles:

- URI labels and query parameters
- request and response headers
- prefix headers
- response status codes
- payload members
- document body members

Unbound request members are passed to the selected body codec. Unbound
response members are populated from the response body.

The operation's materialized `smithy.api#http` trait contains parsed URI path
segments. Dynamic and generated operations therefore use the same URI
construction path.

## Codec Paths

| Protocol | Request encoding | Response encoding |
|---|---|---|
| RPC v2 CBOR | CBOR | CBOR |
| AWS JSON 1.0 | JSON | JSON |
| AWS JSON 1.1 | JSON | JSON |
| RestJson | HTTP bindings and JSON | HTTP bindings and JSON |
| RestXml | HTTP bindings and XML | HTTP bindings and XML |
| AWS Query | Form URL encoded | XML |
| EC2 Query | Form URL encoded | XML |

The generic package registers RPC v2 CBOR. `AwsDynamicClient` registers the
AWS protocol implementations.

## Errors

Modeled errors use the operation's error schema map. The protocol:

1. Extracts a wire discriminator.
2. Resolves the modeled error schema through `DynamicOperation`.
3. Deserializes modeled members through the protocol codec.
4. Creates a `DocumentException`.

`DocumentException` retains the error schema and positional member values.
Protocol-specific unmodeled errors retain their existing exception behavior.

## Streaming Boundary

The developer preview rejects operations with streaming input or output
members before input coercion and transmission. This includes streaming blobs
and event streams.

The existing protocol streaming contracts remain unchanged. Dynamic streaming
requires a separate value and lifetime design.

## Invariants

1. Dynamic and generated operations enter the same protocol implementations.
2. Member indices determine positional storage and remain stable.
3. Member schemas control wire behavior at each serialization boundary.
4. Protocols construct outputs and errors through operation factories.

## Related Documents

- [Dynamic Client Overview](dynamic-client-design.md)
- [Model and Schema](model-and-schema.md)
- [Resolution and Pipeline](resolution-and-pipeline.md)
- [Testing and Validation](testing-and-validation.md)
