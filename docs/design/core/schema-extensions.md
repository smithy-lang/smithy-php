# Schema Extensions

**Status:** Current as of September 30, 2026

**Package:** `smithy-php`

## Summary

Schema extensions attach lazily computed metadata to `Schema` objects. Codecs
and protocols use them to move trait resolution, target lookup, and routing
table construction out of repeated request paths.

Each extension value belongs to one schema and one provider key.

## Core Contract

`SchemaExtensionKey` owns:

- a unique integer ID
- the provider for that extension

```php
final class SchemaExtensionKey
{
    public readonly int $id;

    public function __construct(
        public readonly SchemaExtensionProviderInterface $provider
    ) {}
}
```

Providers implement:

```php
interface SchemaExtensionProviderInterface
{
    public function provide(Schema $schema): mixed;
}
```

Each provider exposes a singleton key through `key()`. The key stores the
provider directly. There is no global provider registry.

## Lookup

`Schema::getExtension()` uses the key ID as an array offset:

```text
schema + key
    -> extensions[key.id], when cached
    -> key.provider.provide(schema), on first access
    -> cache non-null result
```

Non-null values are cached. A provider that returns `null` runs again on the
next access because the extension slot uses null-coalescing assignment.

Extension IDs are process-local. They are array indexes, not serialized
identifiers.

## Implemented Extensions

| Provider | Metadata |
|---|---|
| `JsonSchemaExtensionProvider` | JSON wire names, decode entries, collections, unions, and member templates |
| `XmlSchemaExtensionProvider` | XML names, attributes, namespaces, flattened collections, and member templates |
| `CborSchemaExtensionProvider` | Encoded field names, lookup tables, collections, unions, and member templates |
| `FormUrlEncodedSchemaExtensionProvider` | Query wire names, prefixes, flattened collections, and unions |
| `HttpStructBindingExtensionProvider` | Structure member routing for HTTP bindings |
| `HttpOperationBindingExtensionProvider` | Method, path, labels, static query, and response code |

## JSON Metadata

`JsonSchemaExtension` stores:

- wire-name to member-index maps
- member-index to wire-name maps
- raw Smithy member-name maps for AWS JSON
- packed decode entries
- declaration-ordered member entries
- timestamp formats
- collection target types
- enum and union target metadata
- sparse collection flags
- positional member templates

RestJson selects maps that honor `@jsonName`. AWS JSON selects the raw
member-name maps.

## XML Metadata

`XmlSchemaExtension` stores:

- resolved XML names and namespaces
- attribute and child-element maps
- declaration-ordered child entries
- flattened list and map metadata
- collection item names
- union lookup maps
- timestamp formats
- target schemas and generated classes
- positional member templates

The XML serializer reads ordered attributes and children. The deserializer
uses element-name maps and an ordered-child hint before its map fallback.

## CBOR Metadata

`CborSchemaExtension` stores:

- encoded member-name bytes
- member-name lengths and hashes
- ordered member schemas, indexes, and types
- short-name lookup tables
- list and map target metadata
- union encode and decode entries
- positional member templates

The byte-level decoder uses these tables directly.

## Form URL Encoding Metadata

`FormUrlEncodedSchemaExtension` stores:

- AWS Query and EC2 Query member names
- flattened list behavior
- list and map assignment prefixes
- map entry, key, and value names
- timestamp formats
- union member names and encode entries

Settings determine whether XML names are honored, whether names are
capitalized, whether lists are always flattened, and whether maps are
supported.

## HTTP Binding Metadata

`HttpStructBindingExtension` stores request and response routing for one
structure:

| Metadata | Use |
|---|---|
| `writeInfo` | Fused request route and target by member index |
| `bodySchema` | Request document members |
| `payloadSchema` | Request or response payload |
| `responseHeaders` | Response header decode entries |
| `prefixHeaderDeserMembers` | Response prefix headers |
| `responseCodeMemberIndex` | `@httpResponseCode` target |
| `responseBodySchema` | Response document members |

`HttpOperationBindingExtension` stores:

- HTTP method
- modeled path
- compiled path segments
- static query string
- expected response code

## Lifetime and Caching

Schemas own their extensions. Generated service schemas live for the process
lifetime through generated static accessors. Dynamic schemas live with their
runtime model and client references.

Extensions do not use a process-wide value cache. Multiple schema objects for
the same shape compute separate extension values.

## Performance

Schema extensions remove repeated work from codec and protocol loops:

- trait lookup
- wire-name resolution
- target resolution
- filtered body-schema construction
- member-buffer allocation setup
- collection and union dispatch-table construction

Extensions are retained where they remove repeated work from the generic
codec and protocol paths.

## Validation

Smithy tests cover extension-key identity, non-null caching, provider
invocation, JSON, XML, CBOR, form URL encoding, HTTP structure bindings, and
operation bindings.

## Related Documents

- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Codec Design](../codec/serialization-codecs.md)
- [Protocol Design](../protocol/protocol-design.md)
