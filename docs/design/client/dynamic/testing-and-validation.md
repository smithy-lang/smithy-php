# Dynamic Client Testing and Validation

**Status:** Evidence recorded October 12, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

## Scope

This document records the correctness, protocol, and performance evidence for
the dynamic client developer preview.

The evidence covers:

- Smithy JSON AST loading and runtime schema conversion
- document-backed inputs, outputs, and modeled errors
- service, operation, protocol, endpoint, auth, retry, and transport
  resolution
- request serialization and response deserialization for every preview
  protocol
- preview rejection paths

The current protocol evidence uses mock HTTP transport. A representative
generated-versus-dynamic AWS call through complete provider configuration
remains a preview release check.

## Generic Protocol Parity

The generated and dynamic clients run the same 72 official RPC v2 CBOR client
cases from `tools/codegen-output/models/rpcv2-cbor.json`. The shared fixture
harness covers request serialization, successful response deserialization,
and modeled error deserialization without projection-specific expectations.

Request bodies are compared as type-preserving CBOR trees. This permits
equivalent map ordering and encoding widths while still detecting changes
between text and byte strings, arrays and maps, integers and floats, and
tagged and untagged values.

## Preview Guardrails

The client rejects these conditions before HTTP transmission:

| Condition | Current behavior |
|---|---|
| Unknown operation | Raises `InvalidArgumentException` |
| Streaming blob input | Raises `LogicException` |
| Event stream input or output | Raises `LogicException` |
| Invalid request override | Raises `InvalidArgumentException` |
| Empty auth preference | Raises `InvalidArgumentException` |
| Invalid input value | Raises `InvalidShapeValueException` |
| Missing endpoint strategy | Client construction fails |
| Raw `smithy.rules#endpointRuleSet` without another endpoint strategy | Client construction fails |

Runtime loading rejects malformed JSON AST representation needed by the PHP
runtime, models without a service, and ambiguous service selection without an
explicit service ID. It assumes the Smithy toolchain has already performed
full semantic model validation.

Modeled service errors produce schema-backed `DocumentException` instances.
Unmodeled service errors use the protocol's `ApiException` fallback.

## Preview Readiness

| Requirement | Status | Evidence |
|---|---|---|
| Runtime model and schema conversion | Complete | Preview model path |
| Shared generated and dynamic protocol behavior | Complete for non-streaming operations | Shared official RPC v2 CBOR suite and seven supported provider protocols |
| Generic client resolution and pipeline behavior | Complete for preview scope | Shared client pipeline |
| AWS protocol, endpoint, retry, and auth configuration | Implementation alignment in progress | AWS dynamic runtime |
| Generated-versus-dynamic AWS end-to-end call | Planned | Required before preview release |

The end-to-end check must exercise a generated client and an
`AwsDynamicClient` against AWS through the normal provider chain. It should
cover endpoint resolution, credentials, signing, retry configuration,
protocol execution, transport, and response hydration for the same operation.

## Implementation Alignment

The combined design is implemented across several preview work streams. These
items still need to align with it before the preview release:

- Remove the now-unused AWS dynamic-client operation-target helper. AWS JSON
  and Query protocols already derive dynamic targets from service and
  operation metadata.
- Apply target-shape defaults during dynamic input coercion while preserving
  direct `@default(null)` as the mechanism that clears an inherited default.
- Verify that caller-supplied plugin collections cannot place required HTTP
  checksum calculation before request compression.

## Deferred Validation

- Replace or exclude the invalid empty AWS JSON healthcheck response fixture.
- Add dynamic streaming after its value and lifetime contracts are designed.

## Related Documents

- [Dynamic Client Overview](dynamic-client-design.md)
- [Model and Schema](model-and-schema.md)
- [Resolution and Pipeline](resolution-and-pipeline.md)
- [Serde](serde.md)
