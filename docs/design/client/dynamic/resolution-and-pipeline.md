# Dynamic Client Resolution and Pipeline Design

**Status:** Current as of September 30, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

## Summary

`DynamicClientFactory` turns a model and runtime registrations into a
configured dynamic client. Its immutable `ClientConfig` is consumed by
`ClientPipeline`. Explicit caller arguments replace registered
implementations. Provider subclasses supply their defaults through
an internal `DynamicClientRuntime` and configuration hooks.

The resulting client uses `AbstractClient::executeOperation()` and one shared
`ClientPipeline`.

## Resolution Flow

```text
DynamicClient::create()
    |
    +-> load SmithyModel
    +-> select service
    +-> merge class runtime defaults and caller registrations
    +-> select protocol
    +-> resolve endpoint strategy
    +-> build ClientConfig
    +-> construct DynamicClient
            |
            +-> construct DynamicService
            +-> apply plugins
            +-> construct ClientPipeline
```

## Runtime Registrations

`DynamicClientRuntime` is the internal typed container for implementations
that a provider subclass makes available to dynamic clients:

| Registration | Key |
|---|---|
| Protocol | Protocol shape ID |
| Auth scheme | Auth scheme shape ID |
| Endpoint function | Function name |
| Endpoint auth alias | Wire name |
| Protocol preference | Ordered protocol shape IDs |
| Trait registry | Trait shape ID |
| Error discriminator trait | Trait shape ID |

`DynamicClient::getDefaultRuntime()` returns class-level registrations for
provider additions and Smithy endpoint standard functions. The shared
`ProtocolResolver` supplies the generic RPC v2 CBOR implementation. A
subclass may return a merged runtime.

Explicit `protocols`, `supportedAuthSchemes`, `endpointFunctions`, and
`endpointAuthSchemeIds` arguments overlay the class registrations. The
runtime container is not part of the public construction API.

Protocol and endpoint function maps use their declared string keys.
Authentication schemes are deduplicated by scheme ID. The last registration
for an ID wins.

## Service Resolution

An explicit `serviceId` must identify a service shape. Without an explicit
ID, the model must contain one service.

The resolver fails during construction when the model contains zero or
multiple services. This keeps operation lookup independent of ambient model
order.

## Protocol Resolution

The caller may provide:

1. A protocol instance.
2. A protocol shape ID.
3. A protocol preference list.
4. Protocol implementations through runtime registration.

An explicit protocol instance wins. When the caller also supplies its shape
ID, it must match `ProtocolInterface::getId()`. Without an explicit ID, the
resolver uses the implementation's declared ID.

The resolved ID must be modeled as a service trait. An explicit protocol ID
without an instance requires a registered implementation under that ID.

The dynamic factory contributes runtime registrations to the same
`ProtocolResolverInterface` used by generated clients. After the dynamic
service metadata is attached, client construction asks that resolver to:

1. Combine caller preference, runtime preference, and registered order.
2. Remove duplicate IDs while preserving the first occurrence.
3. Select the first registered protocol whose trait is present on the
   service.

Construction fails when no registered protocol matches the service.

### Generic and AWS registrations

`smithy-php` registers RPC v2 CBOR.

`AwsDynamicClient` creates service-aware protocol instances for:

- AWS JSON 1.0
- AWS JSON 1.1
- RestJson
- RestXml
- AWS Query
- EC2 Query

RestXml receives the modeled XML namespace. Query protocols receive the
modeled service version.

The AWS preference order starts with RPC v2 CBOR, followed by AWS JSON,
RestJson, RestXml, AWS Query, and EC2 Query.

## Endpoint Resolution

The caller may pass an `EndpointResolverInterface` or a static endpoint
string. Passing both is invalid.

When the caller supplies a resolver, the client uses it directly.

Otherwise, the resolver inspects `smithy.rules#endpointBdd`:

- A modeled BDD produces `BytecodeEndpointResolver`.
- A supplied endpoint string becomes the modeled `Endpoint` parameter when
  the BDD declares that parameter.
- A supplied endpoint string produces `StaticEndpointResolver` when no BDD
  needs to evaluate it.
- Construction fails when none of these endpoint sources is available.

Endpoint functions come from the runtime registry and explicit caller
arguments. Endpoint auth aliases map rule result names to canonical Smithy
auth scheme IDs.

Client endpoint parameters are stored in `ClientConfig::$context` under
`EndpointContextKeys::parameters()`. The
endpoint resolver combines them with operation static context, operation
context paths, and input context parameters. See
[Endpoint Resolution](../endpoint/endpoint-resolution.md) for bytecode compilation,
parameter precedence, and cache behavior.

Raw `smithy.rules#endpointRuleSet` traits are retained by the model but are
not executable in the current runtime.

## Client Configuration

`DynamicClient::createClientConfig()` builds the provider-neutral
`ClientConfig`. It resolves:

- protocol
- HTTP client
- endpoint resolver
- interceptors
- supported auth schemes
- auth scheme resolver and preference
- retry strategy and scope
- context values

The base client creates `CurlClient` when the caller omits an HTTP client.
It adds `NoAuthScheme` through the `ClientConfig` constructor.

Provider subclasses may override this factory. `AwsDynamicClient` builds
`AwsDynamicClientConfig` and resolves region, credentials, signing name,
SigV4, retry settings, checksum settings, and HTTP configuration.

## Plugin Configuration

`AbstractClient` applies plugins before it freezes the resolved config and
constructs the pipeline.

The idempotency-token plugin is included once for every client.
`DynamicClient` also installs request compression followed by required HTTP
checksum handling. Both interceptors inspect operation traits, so installing
them for every dynamic client does not alter operations that do not model the
corresponding behavior.

`AwsDynamicClient` also installs:

- AWS client configuration
- flexible checksums when the service models them
- standard retry behavior

Plugins modify `ClientConfig`. They do not receive a separate dynamic-client
configuration type in the generic runtime.

## Operation Invocation

`DynamicClient::call()` performs these steps:

1. Resolve the operation by modeled name.
2. Reject streaming operations outside the preview.
3. Parse the explicit request overrides, when provided.
4. Call `DynamicOperation::createInputFromValue()` to validate and create the
   schema-backed input.
5. Call `AbstractClient::executeOperation()`.
6. Verify that the returned schema matches the operation output schema.
7. Return a `StructDocument`.

Operation lookup retries the name with an uppercase first character. This
supports PHP method calls such as `$client->getItem()` for modeled
`GetItem`.

For modeled operation methods, `__call()` accepts the same positional or named
`input` and `overrides` arguments as `call()`. PHP does not invoke `__call()`
when an operation name matches an existing public client method,
case-insensitively. The explicit `call('OperationName', ...)` form is therefore
the canonical invocation API and remains available for every modeled
operation.

## Pipeline Reuse

The pipeline receives a `DynamicOperation` and `StructDocument`. Both
implement the same contracts as generated operation metadata and generated
input shapes.

```text
DynamicOperation + StructDocument
    |
    v
ClientPipeline
    |
    +-> interceptors
    +-> endpoint resolver
    +-> protocol
    +-> auth scheme and identity resolver
    +-> signer
    +-> HTTP client
    +-> retry strategy
```

The pipeline uses `OperationInterface::getRuntimeIdentity()` for caches.
Generated operations return a class-based identity. Dynamic operations
include service, operation, protocol, and a process-monotonic sequence in the
identity.

Interceptors see `StructDocument` values through the normal input and output
contexts. Schema-driven plugins can inspect the same operation traits for
dynamic and generated calls.

## Authentication

`DynamicOperation` resolves effective auth schemes from the service and
operation model. `ClientPipeline` delegates selection to the configured auth
scheme resolver, which applies endpoint auth results and customer preference
before returning the first supported scheme.

`ClientConfig` installs no-auth automatically. Applications that use HTTP
bearer authentication register `HttpBearerAuthScheme` with an identity
resolver. A provider runtime can supply additional schemes.

`AwsDynamicClient`:

- derives the SigV4 signing name from `aws.auth#sigv4` or
  `aws.api#service`
- installs SigV4 when the service or an operation requires it
- accepts credentials and bearer token providers
- maps endpoint auth names such as `sigv4` to Smithy auth scheme IDs

See [Auth Runtime](../../auth/auth-runtime-design.md) for selection and identity caching.

## Retries and Overrides

The retry strategy belongs to `ClientConfig` and is shared with generated
clients. The base dynamic client uses `NoRetryStrategy` unless the caller or
a provider configures another strategy.

Request overrides use the existing `RequestOverrideConfig` parser. They can
change request headers, HTTP timeouts, retry count, and the other options
supported by the shared client.

Input coercion completes before request overrides enter the pipeline.

## Failure Timing

| Failure | Raised |
|---|---|
| Invalid model path or JSON | Model load |
| Missing or ambiguous service | Client construction |
| Missing protocol implementation | Client construction |
| Missing endpoint strategy | Client construction |
| Invalid runtime registration | Client construction |
| Unknown operation | Before input coercion |
| Streaming preview exclusion | Before input coercion and transmission |
| Invalid input value | Input coercion |
| Unsupported auth scheme | Pipeline auth selection |
| Modeled service error | Protocol deserialization |
| Retry exhaustion | Pipeline attempt completion |

Construction resolves dependencies that do not depend on operation input.
Protocol serialization runs once per operation execution. Endpoint selection,
auth selection, identity resolution, signing, and transport run per request
attempt.

## Extension Hooks

A provider-specific dynamic client can override two protected or static
hooks:

| Hook | Use |
|---|---|
| `getDefaultRuntime()` | Register provider protocols, traits, auth, and endpoint functions that feed shared client resolution. |
| `createClientConfig()` | Build provider-specific immutable configuration. |

These hooks keep provider rules outside `smithy-php` while preserving the
shared resolver and pipeline. Protocol-specific request-target construction
belongs to protocol implementations. Dynamic operations expose canonical
service and operation identity instead of requiring a provider client hook.
