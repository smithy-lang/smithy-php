# HTTP Runtime Design

**Status:** Current as of September 30, 2026. Limitations are listed below.

**Package:** `smithy-php` (`Smithy\Http\`, `Smithy\Async\`)

## Summary

The Smithy PHP HTTP runtime sends PSR-7 requests through a shared libcurl
multi handle. Callers use a blocking method signature. The client suspends
the current flow while network I/O runs, which lets requests in other fibers
make progress on the same event loop.

The runtime automatically selects buffered or streamed responses. It also
supports streamed request bodies, bounded response buffering, cancellation,
response trailers, connection reuse, HTTP/2 negotiation, and libcurl-managed
DNS resolution.

Request trailer types are implemented at the generic HTTP layer. `CurlClient`
rejects request trailers because the PHP cURL extension does not expose
libcurl's request trailer callback. Libcurl owns HTTP transfer framing.

## Documents

[Messages and Streaming](message/http-messages-and-streaming.md) defines request and
response body contracts, framing, backpressure, and trailers. [Curl
Transport](client/curl-http-transport.md) describes the transfer state machine, callback
behavior, timeouts, and cleanup. [Async Runtime](../async/async-runtime-and-http-concurrency.md) covers
fibers, cancellation, connection
permits, pooling, and DNS ownership. [Testing and
Validation][http-testing-validation] describes focused tests, stress
matrices, production S3 validation, and profiling.

## Design Goals

The generic client transport boundary accepts `HttpTransportRequest` envelopes
and returns `HttpTransportResponse` envelopes. `HttpClientTransport` unwraps
requests for the PSR-7 HTTP client boundary and wraps its responses. The
Smithy HTTP response adds body disposal and trailer access to the PSR-7
response contract. Incrementally produced requests and streamed responses use
bounded queues, so neither path requires whole-body buffering.

The client records whether a request body started, finished, and can be
rewound. Retry policy above the HTTP layer uses that state to decide whether
replay is safe. Connection reuse is limited to healthy transfers, and all
requests on one client share an event loop and libcurl multi handle.

Retry classification, request signing, modeled checksum policy, protocol
serialization, and service error handling belong to layers above this
runtime.

## Architecture

```text
Generated or dynamic client
        |
        | HttpTransportRequest
        v
HttpClientTransport
        |
        | PSR-7 RequestInterface
        v
HttpClientInterface
        |
        +--> HttpRequestOptions
        |
        v
CurlClient --------------------------------------+
        |                                         |
        | lease                                   | suspend/resume
        v                                         v
CurlConnectionManager                       EventLoopInterface
        |                                         |
        +--> FIFO route pools                     +--> RevoltEventLoop
        +--> concurrency waiters                  +--> PHP fibers
        |
        v
CurlConnection (exchange) -> cURL easy handle
        |
        v
shared CurlMultiHandle
        |
        +--> native resource or upload read callback
        +--> response header callback
        +--> response body callback
        |
        v
Smithy ResponseInterface
        |
        +--> PSR-7 StreamInterface
        |       +--> Stream (buffered)
        |       +--> StreamingResponseStream (live)
        +--> ResponseTrailers
        +--> discardBody()
        |
        v
HttpTransportResponse
```

`CurlConnection` owns a libcurl easy handle. Libcurl owns the physical TCP
and TLS connections associated with easy and multi handles. Under HTTP/2,
libcurl may multiplex easy-handle transfers over a physical connection.
`FifoConnectionPool` retains idle easy handles. It does not replace libcurl's
physical connection cache. Disabling easy-handle retention regressed uploads,
tail latency, and memory in the retained transport benchmark.

## Package Boundaries

| Namespace | Responsibility |
|---|---|
| `Smithy\Http\Client` | Client contract, request options, transfer state, request trailers |
| `Smithy\Http\Message` | PSR-7 messages, body streams, and response trailers |
| `Smithy\Http\Client\Curl` | Libcurl options, callbacks, polling, and connections |
| `Smithy\Http\Client\Connection` | Transport-neutral connection, exchange, pool, and route contracts |
| `Smithy\Http\Client\Configuration` | Request, connection, TLS, HTTP/2, and pool configuration |
| `Smithy\Http\Client\Cancellation` | HTTP request cancellation signals and reasons |
| `Smithy\Http\Client\Exception` | Transport, cancellation, and acquisition exceptions |
| `Smithy\Async` | Event-loop abstraction, fibers, and bounded fan-out |

## Request Flow

`CurlClient::sendRequest()` begins with explicit request options or the client's
defaults. It validates transport support and checks cancellation. The
connection manager then acquires capacity for the route and leases an easy
handle. Libcurl resolves the request hostname during connection setup.

The client applies request and connection options, creates response state,
registers libcurl callbacks, and adds the easy handle to the shared multi
handle. The calling flow suspends while the event-loop poller drives all
active transfers. Final normalized response headers determine whether an
automatic response remains buffered or switches to streaming. Buffered calls
resume after the transfer completes. Streamed calls resume after final
headers, while the body remains attached to the active transfer.

Completion, failure, cancellation, active body closure, and client
destruction all remove the easy handle. Healthy easy handles return to the
pool. Failed or aborted easy handles close on release.

## Configuration Layers

Configuration is split by ownership:

| Type | Scope |
|---|---|
| `HttpRequestOptions` | One request, or client defaults for every request |
| `ConnectConfiguration` | TCP, TLS, and HTTP version behavior for connections |
| `ConnectionManagerConfiguration` | Concurrency, acquisition, and idle retention |

Per-request settings do not mutate connection settings. Pooled handles receive
connection settings again after `curl_reset()`.

Configuration constructors reject invalid counts, relationships, timeouts,
HTTP/2 stream limits, and cipher suite names before the transport creates a
handle. Setting both idle limits to zero disables idle easy-handle retention.
The connection manager defaults to 100 concurrent requests and 100 retained
idle easy handles per host and in total. Live S3 validation selected this
capacity over 50. Applications can lower it when memory is more important than
latency-hidden throughput.

## Buffered and Streamed Responses

Response ownership is selected automatically from the final normalized
headers. A known body no larger than `maxBufferedResponseBytes` is buffered.
An unknown or larger body is streamed. The threshold defaults to 1 MiB.
Selection uses the final response
after interim responses, followed redirects, and content-decoding header
normalization. A bodyless response is buffered. A body-bearing response is
buffered only when it has one valid `Content-Length` value at or below the
threshold. Missing, repeated, malformed, and out-of-range lengths select
streaming, as do decoded responses and responses above the threshold.

Buffered responses remain in libcurl and become ordinary seekable `Stream`
instances. Unknown and larger responses use a caller-pulled, queue-backed
path. Response downloads do not use `php://temp`, `CURLOPT_FILE`, or a
disk-backed sink.

`responseBufferSize` defaults to 64 KiB. It supplies streamed transfers with
their preferred cURL receive-buffer size and bounds each live
`StreamingResponseStream` queue. The queue uses `SplQueue`. An indexed-array
replacement regressed every measured Smithy metric and was reverted. When the
next libcurl chunk does not fit, the response callback returns
`CURL_WRITEFUNC_PAUSE`. The stream schedules a resume after the consumer
reaches the low water mark and the retained chunk can fit. Reads before
backpressure do not notify the transport.

`bufferedDownloadBufferSize` controls libcurl's initial preferred receive
buffer when the request has no body or transfer encoding. It defaults to
256 KiB. Requests with an upload use `responseBufferSize`, capped at 512 KiB.
Final response selection does not resize this transport buffer.

`AsyncRequestStream` applies the same default bound to request data. A producer
write suspends cooperatively when its chunk does not fit and resumes after the
transport drains enough capacity.

See [Messages and Streaming](message/http-messages-and-streaming.md) for body lifecycle rules and
[Curl Transport](client/curl-http-transport.md) for callback details.

## Concurrency Model

The API remains synchronous from one caller's perspective:

```php
$response = $client->sendRequest($request);
```

Outside an existing fiber, the Revolt suspension drives the event loop until
the request can return. Inside a fiber, the same call yields while I/O is
pending.

`Async::all()` and `Async::map()` create fibers that share the transport.
`Async::map()` uses a fixed worker count and a synchronized shared iterator,
so it does not allocate one fiber per input item.

## Smithy Java Alignment

The design follows the same ownership principles as the Smithy Java HTTP
client. Request and connection configuration have separate lifetimes.
Streaming responses transfer body ownership to the caller. Discard drains a
response so its connection can be reused, while closing an incomplete active
response aborts the exchange. Both clients enforce route capacity and
acquisition timeouts, and both expose trailers after the response transfer
reaches a terminal state.

PHP uses libcurl for protocol and socket management. Smithy Java owns its
HTTP/1.1 and HTTP/2 framing, flow control, and physical connection state.
PHP therefore selects buffered or streamed response delivery automatically
and leaves HTTP/2 stream placement and socket reuse to libcurl. The PHP cURL
extension also prevents `CurlClient` from sending request trailers.

## Current Limitations

`CurlClient` rejects non-null `HttpRequestOptions::$trailers`.
`RequestTrailers` provides a transport-neutral trailer provider, but the Curl
transport does not use it.

`CurlConnectionManager` is the active connection owner.
`FifoConnectionPool` stores idle easy handles for each route. Call
`HttpClientInterface::close()` to fail active transfers and release transport
resources. Destructors provide fallback cleanup.

`CurlConnectionManager` uses the client's `EventLoopInterface` for permit
waiters and defaults to `RevoltEventLoop` when none is supplied.

Transport failures use `HttpException` subclasses. `CurlClient` preserves
existing `HttpException` instances and wraps other exceptions thrown during
connection acquisition, release, or upload cleanup by injected dependencies.
A cleanup failure does not replace an existing transfer failure. Requests
retained by exceptions omit `Authorization` and `Proxy-Authorization` headers.

The internal message model remains immutable PSR-7. Protocols and trusted
runtime mutations preserve the normalized header representation through
`fromNormalized()`, `withNormalizedHeader()`, and
`withNormalizedHeaders()`. Protocol producers validate
application-controlled header names and values before using these trusted
paths. A mutable-message prototype improved isolated throughput by 18.49 to
29.56 percent, but its required PSR-7 transport snapshot made the complete
no-network S3 request pipeline 0.77 to 2.33 percent slower. After the mutable
abstractions were removed, the optimized immutable pipeline improved
no-network throughput by 5.69 to 7.51 percent and HTTP transfer throughput by
1.25 to 2.82 percent. The retained design keeps the normalized immutable fast
paths. See [Message Representation Evaluation][http-testing-validation].

`ResourceRequestStream` can wrap an already-open resource or retain a lazy
resource opener. Opener-backed bodies report replayability independently from
seekability and open a fresh resource after `rewindForRetry()`. `fromFile()`
provides the file-backed form. `close()` and `detach()` remain terminal.

## Performance Summary

### Compared Clients

* **Smithy PHP: asynchronous I/O.** The direct-return `sendRequest()` API
  suspends the active fiber through Revolt while a shared libcurl multi handle
  performs I/O.
* **ReactPHP: asynchronous I/O.** Promises run network work through the React
  event loop and socket transport.
* **Amp: asynchronous I/O.** Fibers and Revolt drive Amp's socket transport.
* **Symfony: deferred synchronous.** `CurlHttpClient` returns lazy responses,
  then response consumption blocks the caller while `curl_multi` processes
  concurrent transfers.
* **Guzzle: promise-based synchronous wait.** `GuzzleHttp\Pool` schedules
  concurrent transfers on `CurlMultiHandler`, then `wait()` blocks the
  caller until they complete.

### Current Results

The comparison uses x86 Linux, HTTP/1.1, 1,000 two MiB transfers per run,
concurrency 50, 50 untimed warmup requests, and ten runs per mode. The tables
report one fixed payload size rather than payload-size bands. Throughput and
latency include complete body transfer, consumption, and SHA-256 validation.
Every transfer passed byte-count and hash validation.

The Guzzle streamed-download adapter uses a benchmark-specific hashing PSR-7
sink to validate chunks without retaining the payload. This is not the normal
AWS SDK for PHP download path or a Guzzle default.

| Mode | Smithy | Symfony | Guzzle | ReactPHP | Amp | Rank |
|---|---:|---:|---:|---:|---:|---:|
| Buffered download | 4.892 Gbps | 4.220 Gbps | 2.777 Gbps | 2.967 Gbps | 3.012 Gbps | 1 |
| Buffered upload | 13.423 Gbps | 9.902 Gbps | 7.285 Gbps | 5.196 Gbps | 4.376 Gbps | 1 |
| Streamed download | 6.521 Gbps | 6.596 Gbps | 6.505 Gbps | 5.050 Gbps | 3.687 Gbps | 2 |
| Streamed upload | 19.044 Gbps | 11.597 Gbps | 11.140 Gbps | 9.362 Gbps | 6.014 Gbps | 1 |

| Smithy mode | p50 | p99 | CPU | RSS |
|---|---:|---:|---:|---:|
| Adaptive download | 126.062 ms | 165.247 ms | 2.3508 s | 49.56 MiB |
| Buffered download | 171.979 ms | 202.250 ms | 3.2609 s | 47.16 MiB |
| Buffered upload | 57.316 ms | 96.674 ms | 1.0449 s | 49.24 MiB |
| Streamed download | 126.231 ms | 166.505 ms | 2.3384 s | 49.68 MiB |
| Streamed upload | 42.954 ms | 58.264 ms | 0.5516 s | 50.67 MiB |

Symfony performs concurrent I/O internally through `curl_multi`, but its
`stream()` iterator drives that transport loop synchronously on the calling
thread. The comparison therefore measures the same concurrent transfer work
across clients with different application-level execution models.

Cold enforced-HTTP/1.1 runs showed the effect of disabling
`CURLOPT_PIPEWAIT`. Smithy throughput increased between 10.00 and 19.61
percent across the three modes, while p50 fell between 15.97 and 28.35
percent. Warmed runs showed no Smithy-specific throughput change, which
localizes the benefit to connection establishment. `PIPEWAIT` asks libcurl to
wait for a connection that may become multiplexable instead of immediately
opening another. Enforced HTTP/1.1 disables that wait.

See [Testing and Validation][http-testing-validation] for complete
cross-client tables, configuration, and measurements.

## Validation

Focused tests, deterministic local stress, live S3 validation, and profiling
cover the implementation at different boundaries. See [Testing and
Validation][http-testing-validation] for the methodology, current evidence,
and remaining coverage.

[http-testing-validation]: testing-and-validation.md
