# Protocol Test Harness

**Status:** Evidence recorded November 11, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

## Scope

The protocol test harness runs official Smithy request, response, and error
fixtures against generated and dynamic clients.

Smithy PHP owns the AWS JSON, Rest JSON, Rest XML, and RPC v2 protocol suites.
The AWS SDK owns:

- AWS Query
- EC2 Query

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

Smithy PHP commits generated artifacts under
`codegen-output/<protocol>/src`. The AWS SDK commits its protocol artifacts
under each suite's `Generated/` directory. Each dynamic path reads the same
fixture and constructs operations from the model.

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

The Smithy PHP AWS protocol suites are green on PHP 8.2.35:

| Protocol | Projection | Tests | Assertions |
|---|---|---:|---:|
| AWS JSON 1.0 | Generated | 60 | 225 |
| AWS JSON 1.0 | Dynamic | 56 | 370 |
| AWS JSON 1.1 | Generated | 122 | 527 |
| AWS JSON 1.1 | Dynamic | 118 | 858 |
| Rest JSON | Generated | 223 | 903 |
| Rest JSON | Dynamic | 220 | 1,542 |
| Rest XML | Generated | 179 | 722 |
| Rest XML | Dynamic | 176 | 1,240 |
| **Total** |  | **1,154** | **6,387** |

Generated suites include focused protocol and error tests in addition to
their shared conformance cases.

The remaining AWS SDK protocol suites contain 277 tests:

| Protocol | Tests |
|---|---:|
| AWS Query | 155 |
| EC2 Query | 122 |
| **Total** | **277** |

## Coverage Boundaries

The current harness does not provide:

- dynamic-client streaming coverage
- live network interoperability

Codec unit tests and HTTP integration tests cover those components
separately.

## Related Documents

- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Protocol Architecture](protocol-design.md)
- [Dynamic Client Validation](../client/dynamic/testing-and-validation.md)
