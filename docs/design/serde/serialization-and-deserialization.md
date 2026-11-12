# Serialization and Deserialization

**Status:** Current as of November 12, 2026

**Package:** `smithy-php`

## Summary

Smithy PHP uses runtime schemas to drive request serialization and response
deserialization. Generated and dynamic clients share the codec, HTTP binding,
and protocol implementations.

Generated shapes contain PHP construction and member-access code. They do not
contain wire-format rules. Protocols select a codec and apply protocol framing.

## Design Rules

1. Schemas carry shape IDs, member indexes, targets, and serde traits.
2. Generated shapes remain protocol agnostic.
3. Codecs own body formats.
4. Protocols own HTTP framing and service error interpretation.
5. Schema extensions precompute repeated routing and format metadata.

## Layers

```text
Client pipeline
    |
    v
ProtocolInterface
    |
    +-> HTTP binding routing
    |
    +-> codec
            |
            +-> JSON
            +-> XML
            +-> CBOR
            +-> form URL encoding
    |
    v
Schema and shape contracts
```

The layers have distinct responsibilities:

| Layer | Responsibility |
|---|---|
| Client pipeline | Endpoint, auth, retry, interceptors, and transport |
| Protocol | Request target, headers, status rules, and error envelopes |
| HTTP binding | Labels, query parameters, headers, payloads, and document body |
| Codec | Format-specific body encoding and decoding |
| Schema | Shape graph, member indexes, targets, and traits |
| Shape | PHP values and generated construction methods |

## Runtime Schemas

`Schema` represents root shapes and members. Structure and union schemas store
members by modeled name and integer member index. Collection schemas store
their item or key and value schemas.

Generated schemas use dense integer trait keys for the serde hot path. Runtime
model schemas also retain shape-ID keyed trait views for open trait access.

`Schema::getExtension()` attaches computed metadata to one schema. Each
`SchemaExtensionKey` owns its provider and integer slot. The schema computes
the extension on first access and reuses it for its lifetime.

## Serialization

Generated structures implement `SerializableStructInterface`.
`serializeMembers()` reads public properties directly and calls the matching
typed method on `ShapeSerializerInterface`.

```php
public function serializeMembers(
    ShapeSerializerInterface $serializer
): void
{
    if (isset($this->bucket)) {
        $serializer->serializeString(
            self::$SCHEMA_BUCKET,
            $this->bucket,
            0
        );
    }
}
```

The generated method selects the serializer operation at code generation time.
The runtime does not switch on the member type for these calls.

`getMemberValue()` remains part of the common structure contract. Dynamic
documents and generic schema walks use it when they need positional member
access.

Each codec serializer builds its body representation and returns bytes from
`flush()`. HTTP binding serialization routes each member to one of these
locations:

- URI label
- query parameter
- query-parameter map
- header
- prefix-header map
- payload
- document body

`HttpBindingSerializer` owns those buckets. Body members are forwarded to the
selected codec.

## Deserialization

`AbstractCodec::createMembersBuffer()` creates a positional member array.
JSON, XML, and CBOR codecs override it with cached templates from their schema
extensions.

`ShapeDeserializerInterface::deserialize()` parses the source and writes each
value to its member index:

```text
wire field
    -> member schema
    -> memberIndex
    -> members[memberIndex]
    -> operation output factory
```

The operation constructs the result through
`OperationInterface::createOutput()`. Generated operations return generated
output classes. Dynamic operations return schema-backed document structures.

HTTP binding deserialization fills the same member buffer from response
headers, prefix headers, status code, payload, and document body.

## Codecs

| Codec | Request use | Response use |
|---|---|---|
| JSON | RestJson and AWS JSON bodies | RestJson and AWS JSON bodies |
| XML | RestXml bodies | RestXml, AWS Query, and EC2 Query bodies |
| CBOR | RPC v2 CBOR bodies | RPC v2 CBOR bodies |
| Form URL encoded | AWS Query and EC2 Query bodies | None |

Form URL encoding has no deserializer. Query protocol responses use the XML
codec.

[Codec Design](../codec/serialization-codecs.md) records format-specific settings and schema
extensions.

## Protocol Families

All protocols implement `ProtocolInterface`.

| Protocol | Framing | Body codec |
|---|---|---|
| RestJson | Smithy HTTP bindings | JSON |
| RestXml | Smithy HTTP bindings | XML |
| AWS JSON 1.0 | AWS JSON RPC | JSON |
| AWS JSON 1.1 | AWS JSON RPC | JSON |
| AWS Query | POST form body and XML response wrappers | Form URL encoded and XML |
| EC2 Query | EC2 form conventions and XML response wrappers | Form URL encoded and XML |
| RPC v2 CBOR | Smithy RPC v2 request target and headers | CBOR |

AWS JSON, AWS Rest JSON, AWS Rest XML, AWS Query, EC2 Query, the Smithy RPC v2
protocols, and generic HTTP binding components live in `smithy-php`.

[Protocol Design](../protocol/protocol-design.md) records request and response behavior.

## Generated and Dynamic Clients

Generated and dynamic operations implement `OperationInterface`. Both expose
input, output, error, and operation schemas to the same protocol methods.

Generated shapes provide direct PHP property access and generated factories.
Dynamic shapes use `StructDocument`, `UnionDocument`, and runtime schema
conversion.

The dynamic client selects a protocol from the service model. Generated client
configuration constructs the corresponding protocol class directly.

## Error Handling

Protocols own success status rules and error envelopes. `HttpErrorDeserializer`
uses the protocol codec, modeled error schemas, headers, and response status to
construct a modeled exception. Unmatched service errors use the fallback
exception class supplied by the operation.

Codec failures report malformed body data. Protocol failures report invalid
framing, missing required headers, or invalid response status behavior.

## Validation

Smithy tests cover generated and dynamic AWS JSON, Rest JSON, Rest XML, AWS
Query, and EC2 Query clients, the RPC v2 protocols, codec behavior, HTTP
bindings, schema extensions, generated shape contracts, and dynamic-client
serde behavior.

## Related Documents

- [Codec Design](../codec/serialization-codecs.md)
- [Protocol Design](../protocol/protocol-design.md)
- [Schema Extensions](../core/schema-extensions.md)
- [Dynamic Client Serde](../client/dynamic/serde.md)
