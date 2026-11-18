# Client Runtime Design

**Status:** Current as of October 12, 2026

**Package:** `smithy/smithy-php` (`Smithy\Client\`)

## Scope

This document describes the shared client runtime used by generated and
dynamic clients. The namespace README is the user-facing guide. This document
focuses on ownership, execution scopes, ordering, retries, and extension
boundaries.

Detailed designs for authentication, endpoint resolution, interceptors,
plugins, pagination, waiters, and dynamic clients live in the linked
component documents.

## Summary

`AbstractClient` is the common base for generated clients. It resolves plugins,
owns an immutable `ClientConfig`, constructs a reusable `ClientPipeline`, and
provides operation, paginator, and waiter execution helpers.

Generated clients supply generated operation metadata and typed structures.
Dynamic clients supply `DynamicOperation` and schema-backed documents. Both
paths enter the same pipeline through `OperationInterface` and
`SerializableStructInterface`.

`ClientPipeline` owns:

- protocol serialization and endpoint binding
- authentication and identity resolution
- interceptor dispatch
- retry sequencing
- transport invocation and retry preparation
- protocol deserialization
- attempt and execution completion

## Goals

1. Use one execution path for generated and dynamic clients.
2. Keep client construction, call configuration, execution, and attempt work
   in distinct scopes.
3. Serialize operation-relative requests once while resolving attempt-specific
   endpoint and signing state for every attempt.
4. Give interceptors stable lifecycle stages and recovery semantics.
5. Start every retry from the same pre-attempt request.
6. Refuse retries that cannot safely restore body or trailer state.
7. Keep protocols, transports, auth schemes, endpoint resolvers, and retry
   strategies replaceable through immutable configuration.
8. Avoid interceptor context allocation when a client has no execution
   interceptors.

## Main types

| Type | Responsibility |
|---|---|
| `AbstractClient` | Resolve construction/call plugins, run call hooks and wrappers, and invoke the pipeline |
| `ClientConfig` | Immutable runtime dependency graph, shared protocol resolution, and plugin state |
| `RequestOverrideConfig` | Call-specific plugins, HTTP settings, headers, retries, and auth preference |
| `ClientPipeline` | Execute one operation and its attempts |
| `InterceptorChain` | Execute ordered call and lifecycle hooks |
| `RetryStrategyInterface` | Own retry classification, quotas, delay, and strategy state |
| `TransportInterface` | Send protocol messages and prepare transport state for retry |
| `MessageExchangeInterface` | Declare the request and response envelope types shared by a protocol and transport |

## Client construction

Generated clients pass a `ClientConfig` and `PluginCollection` to
`AbstractClient`.

```text
base ClientConfig
    |
    v
generated or dynamic service metadata
    |
    v
ProtocolResolverInterface
    |
    v
PluginCollection::apply()
    |
    v
resolved ClientConfig
    |
    +--> stored on the client
    |
    +--> ClientPipeline
```

`ClientConfig` starts with `PluginState::defaults()`. The framework default
plugin contributes idempotency-token handling and automatically registered
Composer plugins. Generated clients may append modeled defaults such as
request compression and HTTP checksum handling.

The initial config may omit a protocol implementation. Generated and dynamic
clients first attach their `ServiceInterface`, then the shared protocol
resolver selects a registered implementation whose protocol trait is present
on the service schema. An explicit implementation or protocol shape ID
overrides automatic selection. Protocol resolution completes before plugins
and the pipeline are constructed.

Only the first accepted instance of each concrete plugin class is applied.
Plugin state is retained when configurations are copied so already-applied
plugins do not run again.

## Call boundary

The common operation boundary is:

```php
executeOperation(
    OperationInterface $operation,
    SerializableStructInterface $input,
    ?RequestOverrideConfig $overrides = null
): SerializableStructInterface
```

Before entering the pipeline, `AbstractClient` performs call-scoped work:

1. Apply plugins from `RequestOverrideConfig`.
2. Run `modifyBeforeCall()` through the resulting interceptor chain.
3. Reuse the client pipeline when configuration identity is unchanged, or
   construct a call-specific pipeline for a replacement configuration.
4. Fold interceptors that opt into `interceptCall()` around the complete
   pipeline execution.

Call wrappers are outside the retry loop. A wrapper can continue with another
input, short-circuit with an output, invoke the pipeline multiple times, or
re-enter the client. Re-entry begins a new call and therefore applies call
plugins and wrappers again.

## Runtime configuration

`ClientConfig` contains:

- protocol
- transport
- nullable HTTP-client compatibility view when using `HttpClientTransport`
- endpoint resolver
- interceptor chain
- supported auth schemes and auth resolver
- auth preference
- retry strategy and scope
- extension context
- immutable plugin registration/application state

`withXxx()` methods return copied configurations. The pipeline treats the
configuration selected for one call as immutable.

The extension context carries optional runtime integrations such as retry
request-flag resolution. Runtime dependencies shared by every client should
become explicit properties instead of anonymous context entries.

## Execution scopes

| Scope | Work |
|---|---|
| Construction | Apply construction plugins and create the reusable pipeline |
| Call | Apply call plugins, modify configuration, and run wrappers |
| Execution | Resolve transport options, serialize the relative request, establish the retry baseline, and initialize retry state |
| Attempt | Resolve modeled auth and identity, resolve the endpoint, reconcile endpoint auth, bind the endpoint, sign, transmit, and deserialize |
| Completion | Run attempt completion for each started attempt and execution completion once |
| Stream lifetime | Track consumption and restore request bodies and trailers before retry |

Serialization is execution-scoped. Endpoint resolution, endpoint binding,
identity resolution, and signing are attempt-scoped so retries can observe
fresh endpoint and identity state.

## Serialization and endpoint binding

The protocol first serializes an operation-relative transport request:

```php
$protocol->serializeRequest($operation, $input);
```

This request contains the modeled path, query, headers, and body but does not
contain the final service authority. Request override headers are applied,
interceptors observe and modify it, and the result becomes the retry baseline.

Each attempt then:

1. Resolves the modeled auth scheme and an identity.
2. Resolves the endpoint using the operation, input, and call configuration.
3. Reconciles the modeled auth scheme with endpoint-provided auth options.
4. Resolves another identity if the endpoint selects another scheme.
5. Calls `ProtocolInterface::setServiceEndpoint()` to bind the service
   endpoint and endpoint headers to the relative request.
6. Reapplies request override headers so explicit call values win.

The final precedence is:

```text
protocol headers
    < endpoint headers
    < request override headers
```

Endpoint binding is repeated for every attempt. Attempt-local endpoint,
signing, or interceptor changes never mutate the retry baseline.

## Interceptor lifecycle

When execution interceptors are present, the lifecycle is:

```text
modifyBeforeCall
interceptCall wrappers
    readBeforeExecution
    resolve effective transport options
    modifyBeforeSerialization
    readBeforeSerialization
    serialize relative request
    apply request override headers
    readAfterSerialization
    modifyBeforeRetryLoop
    snapshot retry request
    resolve retry flags
    begin retry state
    attempt loop
        restore retry request
        readBeforeAttempt
        resolve modeled auth and identity
        resolve endpoint
        reconcile endpoint auth and identity
        bind endpoint and endpoint headers
        reapply request override headers
        modifyBeforeSigning
        readBeforeSigning
        sign
        readAfterSigning
        modifyBeforeTransmit
        readBeforeTransmit
        transmit
        readAfterTransmit
        modifyBeforeDeserialization
        readBeforeDeserialization
        deserialize response or error
        readAfterDeserialization
        modifyBeforeAttemptCompletion
        readAfterAttempt
        retry decision, replay preparation, and delay
    modifyBeforeCompletion
    readAfterExecution
```

An error before an attempt starts skips attempt completion but still enters
execution completion. Once `readBeforeAttempt()` begins, attempt completion
runs for success or failure.

See [Interceptor Design](interceptor/client-interceptor-design.md) for hook propagation and error
semantics.

## Attempt preparation

`prepareAttemptRequest()` returns the endpoint-bound request and writes these
values to reference parameters:

- the final `ResolvedAuthScheme`
- the `IdentityResolverInterface` that produced the identity
- the exact `IdentityInterface` used for signing

The pipeline caches the modeled `ResolvedAuthScheme` per operation when the
request sets no auth scheme preference override. Identity and endpoint
resolution still run on every attempt.

The selected identity and resolver are copied into `OutputContext`. Error
classification interceptors can therefore invalidate the exact identity
rejected by a service without guessing which resolver supplied it.

Attempt execution returns the attempt error and writes the attempt's
`OutputContext` to a reference parameter. Completion hooks consume both before
the retry strategy sees the final attempt failure.

## Authentication and signing

Auth resolution occurs in two steps:

1. Resolve an ordered modeled `AuthSchemeOption` to the first supported
   scheme, merging scheme defaults and option overrides separately for
   identity and signer properties.
2. After endpoint resolution, treat endpoint auth options as authoritative
   when present and merge their signer-property overrides.

Identity resolution returns `IdentityResult`. Expected source misses are
values. Unexpected resolver failures are exceptions. The pipeline unwraps the
selected result before signing.

Signing returns `SignerResult`, which contains the signed request and optional
signature metadata.

See [Authentication Design](../auth/auth-runtime-design.md).

## Retry model

The retry strategy owns:

- classification
- quota or token acquisition
- backoff delay
- strategy state

The pipeline owns:

- attempt sequencing
- request-level maximum attempts
- the retry request baseline
- invoking transport retry preparation
- success recording

Before the first attempt, the pipeline resolves optional request flags and
calls `RetryStrategyInterface::begin()`.

After a failed attempt:

1. Completion interceptors produce the final attempt error.
2. The request-level maximum-attempt limit is checked.
3. The transport prepares the transmitted request state for replay. This runs
   before `refresh()`, so a non-replayable request never spends retry tokens.
4. `refresh()` decides whether retry is allowed and returns the delay.
5. The delay runs through `Async::delay()`.
6. The next attempt starts from the original retry baseline.

The default strategy is `StandardRetryStrategy` (exponential backoff with
jitter and a per-scope token bucket); `NoRetryStrategy` is the explicit
opt-out. Classification and the package split between `Smithy\Client\Retry`
(strategy) and `Smithy\Core\Retry` (the `RetryInfoInterface` / `RetrySafety`
contracts) are described in the [Client Retry Design](retry/client-retry-design.md).

`TokenAcquisitionFailedException` terminates retry. Its optional terminal delay
is honored before the current attempt error is surfaced. `recordSuccess()` is
called once after a successful execution.

### Retry baseline

The request returned by `modifyBeforeRetryLoop()` becomes the retry baseline.
Every attempt begins by restoring that request.

Features that need to replace a non-replayable body with a replayable
representation, such as required checksum buffering or request compression,
must do so before the baseline is captured. Signing headers, endpoint binding,
and transmission-stage mutations remain attempt-local.

### Replay safety

For HTTP, `HttpClientTransport` prepares another attempt by:

- replayable `RequestStreamInterface` bodies use `rewindForRetry()`
- other seekable PSR-7 bodies use `rewind()`
- request trailers use `RequestTrailers::rewindForRetry()`
- non-replayable bodies may retry only when no response was received and
  transport/body state proves that upload never started or consumed data

Other transports implement their own equivalent preparation. Any preparation
failure terminates retry and preserves the current attempt error.

## Request overrides

`RequestOverrideConfig` supports:

| Entry | Behavior |
|---|---|
| call plugins | Apply configuration changes for this call |
| `@http` | Complete or partial `HttpRequestOptions`, plus headers |
| `@transport` | A transport-defined request override |
| `@retries` | Number of retries after the initial attempt |
| `@auth` | Ordered auth-scheme preference names |

Explicit null configuration values are ignored.

Partial HTTP options merge with the defaults exposed by
`HttpClientTransport`. A complete `HttpRequestOptions` replaces those
defaults. HTTP overrides are rejected when the configured transport is not
HTTP.

Custom transports can define their own request mutation and option types by
providing a `TransportRequestOverrideInterface` value through `@transport`.
The generic pipeline invokes that contract without inspecting its payload.

On the interceptor path, option resolution occurs after
`readBeforeExecution()` inside the guarded lifecycle, so completion hooks can
observe validation failures.

## Completion and recovery

The attempt's output context and error enter:

1. `modifyBeforeAttemptCompletion()`
2. `readAfterAttempt()`

A completion modifier can recover by returning a valid output or replace the
failure by throwing another error. The retry strategy receives the error that
remains after these hooks.

Execution completion runs once:

1. `modifyBeforeCompletion()`
2. `readAfterExecution()`

The execution modifier can recover from failures that happened before an
attempt as well as final attempt failures.

## No-interceptor fast path

An empty interceptor chain uses a direct path without allocating lifecycle
contexts or dispatching hooks. The chain is first filtered per operation:
`InterceptorChain::forOperation()` drops each `AbstractInterceptor` whose
`appliesTo()` returns false. The built-in idempotency token, request
compression, and checksum interceptors apply only to operations with the
matching trait, so other operations take this path. It preserves the same
functional boundaries:

```text
resolve transport options
    -> serialize relative request
    -> begin retry state
    -> per-attempt auth, endpoint, signing, transport, and deserialization
    -> replay or record success
```

The fast path uses the same request overrides, retry baseline, endpoint
binding, auth resolver, transport retry preparation, protocol, and transport.

## Pagination and waiters

`AbstractClient::createPaginator()` returns a schema-driven `Paginator` that
invokes the same `executeOperation()` boundary for every page.

`createWaiter()` binds modeled acceptors and delays to an operation. A
`WaiterConfig` can supply request overrides, which are applied to every poll.

See [Pagination Design](pagination/pagination-runtime-design.md) and
[Waiter Design](waiter/waiter-runtime-design.md).

## Failure timing

| Failure | Attempt completion | Execution completion |
|---|:---:|:---:|
| Transport option resolution | No | Yes |
| Input modification | No | Yes |
| Serialization | No | Yes |
| Retry initialization | No | Yes |
| Attempt-start hook | Yes | Yes |
| Modeled auth or identity resolution | Yes | Yes |
| Endpoint resolution or endpoint auth | Yes | Yes |
| Endpoint binding or endpoint-header application | Yes | Yes |
| Signing or signer cleanup | Yes | Yes |
| Transport | Yes | Yes |
| Deserialization | Yes | Yes |
| Retry refresh or replay preparation | Attempt already completed | Yes |
| `recordSuccess()` | Attempt already completed | Yes |

The no-interceptor path surfaces the corresponding failure directly because
there are no completion observers.

## Invariants

1. Generated and dynamic operations enter the same pipeline.
2. Serialization runs once per pipeline execution.
3. Endpoint resolution, auth/identity preparation, signing, transmission, and
   deserialization run per attempt.
4. Every attempt starts from the request produced before the retry loop.
5. Endpoint and signing changes remain attempt-local.
6. A retry never proceeds when request body or trailer state cannot be safely
   restored.
7. Attempt completion runs once for every started attempt.
8. Execution completion runs once when execution interceptors are present.

## Testing

The client suite covers:

- construction and call-scoped plugin application
- call configuration and wrapper ordering
- exact lifecycle hook ordering
- completion recovery and error replacement
- relative serialization and per-attempt endpoint binding
- endpoint and override header precedence
- modeled and endpoint auth changes across retries
- fresh identity resolution and signing
- signer cleanup
- retry request restoration and body/trailer replay
- checksum-buffered and compressed request reuse
- consumed non-replayable body rejection
- both interceptor and no-interceptor paths
- paginator and waiter execution through the shared boundary

Run:

```shell
vendor/bin/phpunit tests/Client
```

## Related designs

- [Authentication](../auth/auth-runtime-design.md)
- [Endpoint Resolution](endpoint/endpoint-resolution.md)
- [Interceptors](interceptor/client-interceptor-design.md)
- [Plugins](plugin/client-plugin-design.md)
- [Retries](retry/client-retry-design.md)
- [Pagination](pagination/pagination-runtime-design.md)
- [Waiters](waiter/waiter-runtime-design.md)
- [Dynamic Client](dynamic/dynamic-client-design.md)
- [HTTP Client](../http/client/curl-http-transport.md)
- [HTTP Messages](../http/message/http-messages-and-streaming.md)
- [Protocol](../protocol/protocol-design.md)
