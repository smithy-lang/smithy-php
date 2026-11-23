# Client Retry Design

**Status:** Current as of September 30, 2026

**Packages:** the retry strategy lives in `smithy/smithy-php`
(`Smithy\Client\Retry\`); the classification contracts live in `smithy/core`
(`Smithy\Core\Retry\`).

The split keeps error shapes free of a client dependency. `RetryInfoInterface`
and `RetrySafety` sit in `smithy/core` so core and provider error shapes
(`ApiException`, `AwsProtocolException`) can carry their own retry
classification while depending only on core. The strategy that consumes that
classification (`StandardRetryStrategy`, `NoRetryStrategy`,
`RetryStrategyInterface`, `RetryState`, the token bucket) stays in
`Smithy\Client\Retry`. Core never depends on client, so there is no cycle and
each package installs alone.

## Scope

The retry package defines strategy contracts and per-invocation state. The
client pipeline owns attempt execution, request-baseline restoration,
invoking transport replay preparation, and interceptor completion. Each
transport owns the replay preparation of its message state. Provider packages
own service-specific classification, quotas, backoff, and adaptive behavior.

## Ownership

| Concern | Owner |
|---|---|
| Classify a failure | `RetryStrategyInterface` implementation |
| Acquire retry capacity | Strategy |
| Compute delay | Strategy |
| Track strategy state | `RetryState` |
| Enforce request attempt cap | `ClientPipeline` |
| Restore request baseline | `ClientPipeline` |
| Prepare request state for replay | `TransportInterface` implementation |
| Delay cooperatively | `Async::delay()` |
| Record successful capacity recovery | Strategy |

This split keeps retry policy outside the generic pipeline while allowing each
transport to enforce replay safety for its own message types.

## Strategy Contract

`RetryStrategyInterface` has four operations:

1. `begin(scope, requestFlags)` creates state before the first attempt.
2. `refresh(state, error)` either returns the delay for another attempt or
   throws `TokenAcquisitionFailedException`.
3. `recordSuccess(state)` lets the strategy restore quota after success.
4. `maxAttempts()` returns the strategy-wide attempt limit, including the
   initial attempt.

`StandardRetryStrategy` is the default. It retries with exponential backoff,
jitter and a per-scope token bucket, matching smithy-java's
`StandardRetryStrategy`:

- 3 attempts by default (the initial attempt plus 2 retries).
- Exponential backoff with jitter: base delay 50 ms, throttling base delay
  1 s, capped at 20 s. A server-suggested wait is clamped to between the
  computed delay and that delay plus five seconds.
- A token bucket per retry scope: 500 tokens, a retry costs 14, a throttling
  retry costs 5, and up to 128 scope buckets are kept (least recently used
  evicted). A retry that cannot cover its cost surfaces the failure instead of
  retrying; a successful call returns tokens.

`NoRetryStrategy` is the explicit opt-out. It allows one attempt and rejects
every refresh request.

### Classification

Classification follows CG13. The strategy decides retryability from the error
and its previous-chain:

1. If an exception in the chain implements `RetryInfoInterface`, it decides:
   `getRetrySafety()` (`Yes`/`No`/`Maybe`, where `Maybe` is treated as not
   retryable), `isThrottle()`, and `getRetryAfter()`.
2. Otherwise a transport `NetworkException` or a server-fault (5xx)
   `ApiException` is retried, and everything else is not.

`ApiException` (core) and `AwsProtocolException` (protocol-aws) implement
`RetryInfoInterface`, so the default classification needs no per-service code.
`ApiException` maps its fault to safety (Server -> Yes, Client -> No,
Other -> Maybe); `AwsProtocolException` also retries 500/502/503/504 and
treats 429 as a retryable throttle. Codegen emits an override for a modeled
`@retryable` error, forcing it retryable (and a throttle when the trait says
so).

An `x-amz-retry-after` header (milliseconds) is captured for modeled and
unmodeled errors during deserialization. It is the only retry hint header
read; the standard HTTP `Retry-After` header is left to the transport.

`StandardRetryStrategy` retries any error classified as retryable. It does not
yet promote classification by operation idempotency: raising a `Maybe` to
retryable for `@readonly` or `@idempotent` operations is a planned interceptor,
not implemented here (follow-up).

Provider strategies can mutate the non-readonly fields on `RetryState`.
The generic pipeline treats those fields as opaque and passes the same state
object through the invocation.

## Request Flags

`RetryRequestFlagResolverInterface` can derive one bitset from the operation
and call configuration before `begin()`. The resolver is registered through
the client context because it is an optional provider integration.

Core currently defines `RetryRequestFlags::LONG_POLLING`. Providers decide how
that flag changes quota, terminal delay, or classification.

## Attempt Flow

```text
serialize and capture retry baseline
    |
    +-> resolve request flags
    +-> strategy.begin()
    |
    v
attempt
    |
    +-> completion interceptors
    +-> success ----------> strategy.recordSuccess()
    |
    +-> failure
            |
            +-> enforce request attempt cap
            +-> prepare request state for replay (TransportInterface)
            +-> strategy.refresh() enforces strategy policy and capacity
            +-> restore request body and trailers
            +-> Async::delay()
            +-> next attempt from the baseline
```

The pipeline checks replay preparation *before* calling `refresh()`, so a
non-replayable request that can never be retried does not spend retry tokens
first.

The retry strategy sees the error after
`modifyBeforeAttemptCompletion()` and `readAfterAttempt()`. Completion
interceptors can therefore recover, replace, or classify an attempt before
retry policy runs.

`TokenAcquisitionFailedException` signals that the strategy cannot grant
another attempt. Its `previous` error identifies the failed attempt, its
optional state captures exhaustion details, and its delay supports policy
that waits before surfacing a terminal failure.

## Attempt Limits

Two limits can stop another attempt:

- the strategy's policy and attempt limit, enforced when `refresh()` refuses
  another attempt
- the request override derived from `@retries`, enforced by `ClientPipeline`

`RetryStrategyInterface::maxAttempts()` reports the strategy-wide limit,
including the initial attempt. Implementations remain responsible for
enforcing that limit through `refresh()`.

`@retries` counts retries after the initial attempt. The parser stores it as
`maxAttempts = retries + 1`.

## Retry Baseline

`modifyBeforeRetryLoop()` produces the request baseline. Every attempt starts
by restoring that request before endpoint binding, signing, or transmission.
Attempt-local headers and URI changes therefore do not accumulate.

Features that convert a one-shot body into a replayable representation must do
so before this baseline is captured. Required checksum buffering and request
compression use that boundary.

## Replay Safety

Before spending any retry tokens, `ClientPipeline` calls
`TransportInterface::prepareRequestForRetry()`. This replay check runs *before*
`refresh()`, so a request whose body cannot be replayed never depletes the
token bucket for a retry that could not happen. For HTTP,
`HttpClientTransport` applies these rules:

- `RequestStreamInterface` bodies must report replayability and rewind with
  `rewindForRetry()`
- generic seekable PSR-7 bodies rewind through `rewind()`
- `RequestTrailers` rewind their providers
- a non-replayable body can continue only when transport state proves no data
  was consumed and no response was received

If restoration fails, the pipeline stops retrying and surfaces the attempt
failure. It does not send a partial, empty, or stale-body retry.

## Concurrency and Scope

`RetryState` belongs to one operation invocation. Strategy implementations can
share quota by the configured retry scope, but must be safe when multiple
fibers call the same client concurrently.

Backoff uses `Async::delay()` rather than process-blocking sleep, allowing
other fibers and HTTP transfers to progress.

## Validation

Core tests cover no-retry behavior, state and request flags, terminal
exceptions, request caps, completion ordering, retry delays, fresh endpoint
and auth preparation, body/trailer restoration, and both interceptor and
fast-path execution.

Run:

```shell
vendor/bin/phpunit tests/Client/Retry tests/Client/ClientPipelineTest.php
```

## Related Designs

- [Client Runtime](../client-runtime-design.md)
- [Interceptors](../interceptor/client-interceptor-design.md)
- [HTTP Messages and Streaming](../../http/message/http-messages-and-streaming.md)
