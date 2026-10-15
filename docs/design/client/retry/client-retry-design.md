# Client Retry Design

**Status:** Current as of September 30, 2026

**Package:** `smithy/smithy-php` (`Smithy\Client\Retry\`)

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

`NoRetryStrategy` is the default. It allows one attempt and rejects every
refresh request.

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
            +-> strategy.refresh() enforces strategy policy and capacity
            +-> restore request body and trailers
            +-> Async::delay()
            +-> next attempt from the baseline
```

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

Before retrying, `ClientPipeline` calls
`TransportInterface::prepareRequestForRetry()`. For HTTP,
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
