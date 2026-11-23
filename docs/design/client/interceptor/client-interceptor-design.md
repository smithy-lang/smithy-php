# Client Interceptor Design

**Status:** Current as of September 30, 2026

**Package:** `smithy/smithy-php` (`Smithy\Client\Interceptor\`)

## Scope

Interceptors observe or transform a client call at fixed lifecycle stages.
They can also wrap a complete call for timing, caching, fallback, hedging, or
circuit-breaking behavior.

The namespace [README](../../../../src/Client/Interceptor/README.md) is the
usage guide. This document records lifecycle, propagation, and error
semantics.

## Contract Shape

`InterceptorInterface` groups four kinds of hooks:

- `modifyBeforeCall()` derives one call-specific `ClientConfig`
- `interceptCall()` wraps the complete execution when `interceptCalls()`
  returns `true`
- read hooks observe the current context
- modify hooks return the value passed to later interceptors and stages

`AbstractInterceptor` supplies forwarding defaults. Custom interceptors
override only the methods they need.

## Scopes

```text
client call
    |
    +-> call-scoped plugins
    +-> modifyBeforeCall
    +-> call wrappers
            |
            v
        execution hooks
            |
            +-> serialize relative request
            +-> modifyBeforeRetryLoop
            |
            v
        retry loop
            |
            +-> attempt hooks
            +-> auth, endpoint binding, signing
            +-> transmit and deserialize
            +-> attempt completion
            |
            v
        execution completion
```

Execution hooks run at most once per pipeline execution. Attempt hooks repeat
for retries. A call wrapper can invoke its continuation more than once, and
each invocation creates a complete execution with its own retry loop.

## Chain Semantics

`InterceptorChain` stores interceptors in registration order.

Read hooks receive the current context. Modify hooks pass each returned value
into the next interceptor by deriving a context with that replacement. This
keeps contexts explicit while allowing each transport to define its own
request and response envelopes.

Call wrappers are filtered when the chain is constructed. The first
registered wrapper is outermost. Ordinary interceptors do not enter the
wrapper fold.

## Lifecycle

| Scope | Hooks |
|---|---|
| Call configuration | `modifyBeforeCall` |
| Call wrapper | `interceptCall` |
| Execution input | `readBeforeExecution`, `modifyBeforeSerialization`, `readBeforeSerialization` |
| Serialized request | `readAfterSerialization`, `modifyBeforeRetryLoop` |
| Attempt request | `readBeforeAttempt`, `modifyBeforeSigning`, `readBeforeSigning`, `readAfterSigning`, `modifyBeforeTransmit`, `readBeforeTransmit` |
| Attempt response | `readAfterTransmit`, `modifyBeforeDeserialization`, `readBeforeDeserialization` |
| Deserialization | `readAfterDeserialization` |
| Attempt completion | `modifyBeforeAttemptCompletion`, `readAfterAttempt` |
| Execution completion | `modifyBeforeCompletion`, `readAfterExecution` |

Endpoint resolution and identity resolution occur while preparing an attempt,
before `modifyBeforeSigning`. The request context visible to signing hooks is
already endpoint-bound and contains endpoint and override headers.

## Completion and Recovery

Completion modify hooks receive the current error separately from
`OutputContext`. The default implementation rethrows a non-null error or
returns the output.

An override can:

- return an output to suppress the current error
- transform a successful output
- throw another error to replace the current result

Completion hooks therefore must not use a no-op implementation that
unconditionally rethrows before later interceptors run. The chain passes the
current output/error state through completion interceptors in order.

`OutputContext` carries the exact identity and resolver selected for the
attempt. An error-classification interceptor can invalidate a service-rejected
identity without guessing which cache or provider supplied it.

## Error Propagation

Most read and modify stages fail fast. An exception stops that stage and
enters the nearest completion boundary.

Four boundary read stages invoke every interceptor and surface the most recent
error afterward:

- `readBeforeExecution`
- `readBeforeAttempt`
- `readAfterAttempt`
- `readAfterExecution`

This gives telemetry and cleanup interceptors a chance to observe boundaries
even when another interceptor fails.

## Context Ownership

| Context | Values |
|---|---|
| `CallContext` | Operation, input, call configuration, call attributes |
| `InputContext` | Operation, input, configuration, call attributes, and current client |
| `RequestContext` | Operation, input, configuration, call attributes, request |
| `ResponseContext` | Operation, input, configuration, call attributes, request, response |
| `OutputContext` | Operation, input, configuration, call attributes, nullable request/response/output, attempt identity and resolver |

`attributes` is a `MutableContext` created once per operation call and shared
by every hook of that call, so a value stored in an early hook is readable in
a later one. Each call starts with an empty bag.

Contexts belong to one execution path. Interceptor instances belong to the
client and can be reused concurrently. Shared mutable interceptor properties
must therefore be concurrency-safe. Local wrapper state is preferred.

## Built-in Interceptors

- `IdempotencyTokenInterceptor` fills absent modeled token members before
  serialization
- `RequestCompressionInterceptor` creates the transmitted compressed
  representation before signing
- `HttpChecksumInterceptor` buffers non-replayable bodies before the retry
  loop when needed and computes the checksum before signing

The default dynamic-client registration orders compression before checksum.
Custom plugin collections must preserve that order when both traits apply.

## Validation

Tests cover every hook, ordering, context replacement, wrapper nesting,
short-circuiting, multiple continuation calls, boundary error collection,
completion recovery, retries, built-in interceptor behavior, and identity
propagation.

Run:

```shell
vendor/bin/phpunit tests/Client/Interceptor
```

## Related Designs

- [Client Runtime](../client-runtime-design.md)
- [Plugins](../plugin/client-plugin-design.md)
- [Authentication](../../auth/auth-runtime-design.md)
