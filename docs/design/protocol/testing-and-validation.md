# Protocol Test Harness

**Status:** Evidence recorded August 30, 2026

**Package:** `aws-sdk-php`

## Scope

The protocol test harness runs the official Smithy request, response, and
error fixtures against generated and dynamic AWS clients.

It covers:

- AWS Query
- EC2 Query
- AWS JSON 1.0
- AWS JSON 1.1
- RestJson
- RestXml

RPC v2 CBOR conformance lives in the smithy-php codec and protocol suites.

## Fixture Flow

Each protocol fixture is a Smithy JSON AST containing operations with
`smithy.test#httpRequestTests` and `smithy.test#httpResponseTests` traits.
Error response tests are attached to modeled error shapes.

The validation flow is:

```text
Smithy protocol fixture
    -> smithy-php code generation
    -> generated schemas, operations, shapes, and client
    -> protocol data providers
    -> generated client and dynamic client
    -> MockHttpClient
    -> request, output, or exception assertions
```

Generated artifacts are committed under each protocol's `Generated/`
directory. The dynamic path reads the same fixture through
`DynamicProtocolTestHelper` and constructs operations from the model.

## Test Loader

`ProtocolTestLoader` extracts:

- client-applicable request tests
- client-applicable response tests
- client-applicable modeled error response tests
- operation IDs

Tests with another `appliesTo` value are excluded. Missing request `params`
default to an empty object so unit inputs follow the same path as explicit
empty inputs.

## Request Validation

Each request fixture invokes the operation through a generated client and a
dynamic client. `MockHttpClient` captures the serialized request.

Assertions cover:

- HTTP method
- path and query parameters
- endpoint overrides from fixture `host` values
- expected headers
- required headers
- forbidden headers
- protocol-specific body content

JSON bodies are compared as decoded values. XML bodies are compared
semantically. Query bodies are parsed as form data before comparison.
Fixtures that require exact bytes use a direct string comparison.

## Response Validation

Each response fixture is queued on `MockHttpClient` and deserialized through
both client forms.

The harness verifies:

- the generated output class on generated-client paths
- member values by schema index
- nested structures, lists, maps, documents, and unions
- headers, response codes, payloads, and protocol body members
- stream contents when the generated-client fixture supports streaming

Dynamic-client streaming fixtures are skipped because streaming is outside
the current dynamic-client preview scope.

## Error Validation

Modeled error fixtures verify:

- protocol error-code extraction
- error schema selection
- generated exception construction
- dynamic error structure construction
- modeled error members from headers and bodies

This exercises protocol-specific error envelopes in addition to successful
responses.

## Current Results

The AWS protocol suite is green on PHP 8.2.33:

| Protocol | Tests |
|---|---:|
| AWS Query | 155 |
| EC2 Query | 122 |
| AWS JSON 1.0 | 115 |
| AWS JSON 1.1 | 237 |
| RestJson | 443 |
| RestXml | 356 |
| **Total** | **1,428** |

The full suite reports 4,906 assertions and 33 PHPUnit deprecations. The
deprecations do not change protocol behavior, but they should be removed
before upgrading to a PHPUnit version that treats them as errors.

## Coverage Boundaries

The current harness does not provide:

- dynamic-client streaming coverage
- RPC v2 CBOR fixtures in the AWS protocol suite
- live network interoperability

Codec unit tests and HTTP integration tests cover those components
separately.

## Related Documents

- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Protocol Architecture](protocol-design.md)
- [Dynamic Client Validation](../client/dynamic/testing-and-validation.md)
