# Serialization Codecs

**Status:** Current as of September 30, 2026

**Package:** `smithy-php`

## Summary

Codecs convert Smithy values to and from message-body formats. They do not
apply HTTP bindings, request targets, service headers, or error envelopes.

Four codecs are implemented:

| Namespace | Format |
|---|---|
| `Smithy\Codec\Json` | JSON |
| `Smithy\Codec\Xml` | XML |
| `Smithy\Codec\Cbor` | CBOR |
| `Smithy\Codec\FormUrlEncoded` | Query-protocol request bodies |

## Codec Contract

Each codec extends `AbstractCodec`:

```php
abstract class AbstractCodec
{
    public function createMembersBuffer(Schema $schema): array;

    abstract public function createSerializer(): CodecSerializerInterface;

    abstract public function createDeserializer(
        StreamInterface $source
    ): ShapeDeserializerInterface;
}
```

`CodecSerializerInterface` extends `ShapeSerializerInterface` with
`flush(): string`, which returns the encoded payload.

JSON, XML, and CBOR codecs override `createMembersBuffer()` with a cached
positional template from their schema extension.

## Serialization

`ShapeSerializerInterface` defines one typed method for each serializable
Smithy type. Generated structures call these methods from
`serializeMembers()`.

The serializer receives:

- the member schema
- the PHP value
- the member index in the parent structure

The member index selects precomputed wire names and format metadata. Generated
code selects the typed serializer method at code generation time.

Serializers keep nested structure state until `flush()` returns the completed
body. JSON, CBOR, and form serializers can be borrowed from codec-owned pools
for internal protocol calls.

## Deserialization

`ShapeDeserializerInterface` has one operation:

```php
public function deserialize(
    Schema $schema,
    array &$members
): void;
```

The deserializer writes values into `$members` by `memberIndex`. The operation
factory constructs the generated or dynamic output after parsing.

Schema extensions precompute wire-name maps, target types, timestamp formats,
collection metadata, union dispatch, and reusable member templates.

## JSON

`JsonCodec` uses `JsonCodecSettings` to configure:

- default timestamp format
- whether `@timestampFormat` is honored
- whether `@jsonName` is honored
- service namespace behavior

The serializer builds PHP values and uses PHP's JSON extension for final
encoding. Big integers and big decimals retain number-token form instead of
becoming quoted strings.

The deserializer decodes the source once, then uses
`JsonSchemaExtension` for:

- wire-name to member-index dispatch
- declaration-order speculative matching
- scalar and complex member decode metadata
- list and map target metadata
- union lookup
- positional member templates

AWS JSON protocols disable `@jsonName` handling. RestJson honors it.

## XML

`XmlCodec` uses `XMLWriter` for serialization and `SimpleXMLElement` for
deserialization.

`XmlCodecSettings` configures:

- wrapper elements
- default XML namespace
- default timestamp format
- `@timestampFormat` handling

The codec parses with `LIBXML_NOBLANKS`, `LIBXML_NONET`, and `LIBXML_COMPACT`.
It then descends through configured response wrappers.

`XmlSchemaExtension` precomputes:

- element and attribute wire names
- namespaces
- ordered child lookup
- flattened list and map behavior
- union members
- collection target metadata
- timestamp formats
- positional member templates

The codec pools inactive `XMLWriter` instances. Each serializer has exclusive
state while active.

## CBOR

`CborCodec` implements the Smithy RPC v2 CBOR body format in PHP.

`CborSchemaExtension` precomputes encoded field names, member-name lengths,
lookup tables, target metadata, union entries, and positional member
templates.

Dynamic and generated structures use the same serializer contract. Response
deserialization uses the optimized generic decoder.

## Form URL Encoding

`FormUrlEncodedCodec` serializes AWS Query and EC2 Query request bodies.

Its settings control:

- XML name handling
- EC2 capitalization
- list flattening
- map support
- timestamp formats

The serializer caches resolved member names, assignment prefixes, flattened
collection rules, and union metadata through
`FormUrlEncodedSchemaExtension`.

The codec rejects deserializer creation. AWS Query and EC2 Query responses use
the XML codec.

## Validation

Codec tests cover JSON, XML, CBOR, and form URL encoding across scalar,
aggregate, timestamp, document, union, and error paths. Protocol suites
exercise the codecs through generated and dynamic clients.

## Related Documents

- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Protocol Design](../protocol/protocol-design.md)
- [Schema Extensions](../core/schema-extensions.md)
- [XML Follow-ups](xml-followups.md)
