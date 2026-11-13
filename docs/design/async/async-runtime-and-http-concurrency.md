# Async Runtime and HTTP Concurrency

**Status:** Current as of September 30, 2026

**Package:** `smithy-php` (`Smithy\Async\`,
`Smithy\Http\Client\Cancellation\`, `Smithy\Http\Client\Connection\`,
`Smithy\Http\Client\Curl\`)

## Blocking API, Cooperative I/O

The HTTP client exposes a blocking return type:

```php
$response = $client->sendRequest($request);
```

`CurlClient` suspends through `EventLoopInterface` while the transfer remains
pending. `RevoltEventLoop` adapts that contract to Revolt suspensions,
deferred callbacks, and timers.

Calls outside an existing fiber block the calling flow while Revolt drives
I/O. Calls inside fibers yield to other work on the same event loop. Both
paths use the same Curl transport.

## Event Loop Contract

`EventLoopInterface` provides:

| Method | Use |
|---|---|
| `run()` | Start one fiber per callback and return ordered results |
| `suspend()` | Register resume and throw continuations for current flow |
| `delay()` | Suspend for a timer without sleeping the process |
| `schedule()` | Schedule a cancellable delayed callback |
| `cancel()` | Cancel a scheduled callback |
| `defer()` | Schedule state mutation on the next event-loop turn |

The Curl transport uses `suspend()`, `schedule()`, `cancel()`, and `defer()`.
Deferral keeps libcurl handle mutation out of body producer, cancellation,
and body consumer callbacks. It also drives bounded bursts after transfer
callbacks or selective counter scans report progress. Cancellable timers
enforce burst boundaries, drive idle poll backoff, and run the earliest
active request timeout without suspending an event-loop callback.

`CurlConnectionManager` uses its injected `EventLoopInterface` for permit
waiters. It defaults to `RevoltEventLoop` when constructed without a loop.

## Fan-Out Helpers

`Async::all()` runs each callable in its own fiber and returns results in
input order. It waits until every callable terminates. If callables fail, it
throws the exception with the lowest input index.

`Async::map()` provides bounded concurrency over any iterable. It starts 25
worker fibers by default. Workers claim items lazily from one shared iterator
through a small cooperative lock, assign monotonic result indexes, and store
completed values by index. Sorting those indexes after all workers finish
restores input order without materializing the iterable first.

`RevoltEventLoop::run()` creates an explicit `Fiber` for each callback.
Revolt suspensions resume the fiber that owns the pending HTTP operation.
Per-callback result holders avoid writes to one shared PHP array while fibers
are suspended.

## Cancellation

`Smithy\Http\Client\Cancellation\CancellationInterface` defines the observable
signal contract. `Cancellation` supplies the mutable source and signal
implementation.

A cancellation creates one `CancelledException` and stores it as the
permanent reason. It detaches the current subscriber list before invoking
each subscriber once. If subscriber callbacks throw, cancellation still
notifies the rest before propagating the first callback exception.

Subscribers added after cancellation are invoked immediately and receive
subscription ID zero.

The HTTP stack checks cancellation before transfer setup.
The connection manager observes the same signal while a request waits for a
permit, and the Curl client subscribes during an active transfer.
Subscriptions are removed when waiters or transfers terminate.

## Connection Model

The transport-neutral connection layer contains:

```text
RequestContext
    |
    v
RequestContext key (scheme + host + port + proxy)
    |
    v
ConnectionManagerInterface
    |
    +--> route permit
    +--> ConnectionPoolInterface
    |
    v
HttpConnectionInterface
    |
    v
ExchangeInterface
```

The active Curl implementation maps one `CurlConnection` to one libcurl easy
handle. The leased `CurlConnection` is also the exchange for one request.

Libcurl owns physical sockets. More than one easy handle can use the same
HTTP/2 socket through the shared multi handle. Pool and active counts therefore
measure easy-handle capacity, not physical socket count.

## Route Keys

Connections and permits are keyed by:

```text
scheme://host:port|proxy=<proxy URI>
```

The proxy suffix is omitted for direct connections. Separating proxy routes
prevents a handle configured for one tunnel or proxy from entering another
route's pool.

## Permit Acquisition

`CurlConnectionManager` enforces `maxConcurrency` per route key.

When capacity exists, acquisition increments the active count and proceeds.
When capacity is full, the manager creates a FIFO waiter. The waiter holds a
Revolt suspension, an optional acquisition timer, the cancellation signal,
and its cancellation subscription ID.

Releasing an exchange decrements active count and resumes the oldest waiter.
The resumed waiter receives the released permit directly.

Acquisition timeout throws `ConnectionAcquireTimeoutException`. Cancellation
throws `CancelledException`, which `CurlClient` wraps as
`RequestCancelledException`.

## Connection Creation

`CurlConnectionFactory` creates an easy handle and applies the TCP connect
timeout and keepalive settings. Keepalive uses a 30-second idle interval and
a 15-second probe interval. The factory also applies HTTP version policy,
and TLS configuration.

TLS configuration covers peer and hostname verification, client certificate
and private key paths, CA file and directory, and separate TLS 1.2 and TLS
1.3 cipher allowlists. The transport enforces TLS 1.2 as the minimum version
for every secure connection.

The `Automatic` version policy uses `CURL_HTTP_VERSION_2TLS`, which lets ALPN
select HTTP/2 or HTTP/1.1 over TLS. `EnforceHTTP_1_1` selects HTTP/1.1.
`EnforceHTTP_2` selects HTTP/2 prior knowledge.

## Pooling

The manager stores idle easy handles in one `FifoConnectionPool` per route.
FIFO reuse rotates handles through the pool instead of repeatedly selecting
the newest handle.

This pool does not own physical TCP or TLS connections. The persistent
libcurl multi handle owns that connection cache and decides socket reuse and
HTTP/2 multiplexing. `CurlConnection` is the transport's lifecycle wrapper
around one easy handle.

On lease, the manager closes entries that are already closed, older than the
idle timeout, unhealthy, or not `CurlConnection` instances.

A reused easy handle is reset with `curl_reset()`. The manager then reapplies
connection configuration before request options are added.

On release, the manager closes unhealthy and closed easy handles, along with
handles that report a libcurl error. It also closes a healthy handle when the
route reached `maxIdlePerHost` or the aggregate pools reached `maxIdleTotal`.
Every other handle is reset and appended to its route's FIFO pool.

`prune()` closes entries older than the idle timeout. `clear()` closes every
idle easy handle and fails queued waiters.

Setting both idle limits to zero preserves permits and waiters but closes each
easy handle on release. The retained x86 comparison showed upload throughput
regressions, higher upload CPU, and higher RSS in every mode with retention
disabled. Easy-handle reuse therefore remains enabled by default. That result
does not establish that route-specific FIFO storage is superior to a simpler
easy-handle pool. See [Easy-Handle
Retention][http-testing-validation].

## Connection Health

A new `CurlConnection` starts connected with unknown health because the
wrapper models easy-handle availability. Libcurl performs the physical
connection later. Successful transfer completion calls `markUsed()`, which
updates last-used time, increments the request count, and marks the connection
healthy.

Transport failure, cancellation of an active transfer, and early streamed
body closure mark the connection unhealthy. The manager closes it on release.

HTTP error status codes do not mark a connection unhealthy because the
transport completed successfully.

## DNS

Libcurl resolves request hostnames and owns DNS caching, address selection,
proxy resolution, and connection-stage failover. The PHP client does not
pre-resolve hosts or pin addresses through `CURLOPT_RESOLVE`.

A live S3 comparison measured Amp throughput changes of -2.24 percent for
downloads and +5.04 percent for uploads. Amp increased p50 latency by 0.62
percent for downloads and 3.62 percent for uploads. Peak RSS increased by
2.01 MiB and 4.53 MiB. See [DNS
Ownership][http-testing-validation].

## Defaults

### Request defaults

| Setting | Default |
|---|---:|
| Request or header timeout | 30 seconds |
| Follow redirects | `false` |
| Proxy | `null` |
| Response delivery | Automatic buffering or streaming |
| Decode content | `true` |
| Request trailers | `null` |
| Cancellation | `null` |
| Response buffer | 64 KiB |
| Maximum automatically buffered response | 1 MiB |
| Response read inactivity timeout | 30 seconds |
| Maximum decoded response bytes | Unlimited |
| Libcurl upload buffer | 128 KiB |
| Libcurl bodyless buffered-download buffer | 256 KiB |

### Connection defaults

| Setting | Default |
|---|---:|
| Connect timeout | 10 seconds |
| HTTP version policy | Automatic |
| HTTP/2 max streams | 100 |
| Max concurrency per route | 100 |
| Max idle per route | 100 |
| Max idle across routes | 100 |
| Idle timeout | 60 seconds |
| Connection acquisition timeout | 10 seconds |

The 64 KiB response buffer bounds queued response data per active stream. The
response queue uses `SplQueue`. An indexed-array alternative regressed every
measured Smithy metric. The separate 128 KiB upload buffer and 256 KiB
buffered-download buffer are preferred libcurl transfer buffers, not request
or response queue limits. The buffered-download option applies only to
bodyless buffered requests.

The connection limits are measured SDK defaults rather than service maxima.
A low-latency two MiB fixture favored an admission limit of 50, but a ten-run
live S3 comparison at offered concurrency 450 consistently favored 100.
Relative to 50, the 100-request limit improved download throughput by 14.55
percent and upload throughput by 28.04 percent. Peak RSS increased by
14.06 MiB for downloads and 12.31 MiB for uploads. Limits of 250 and 450
increased memory and tail latency. `maxIdlePerHost` matches the default
admission limit so a single hot route can retain its warm easy handles.
`maxIdleTotal` remains 100. Workloads with different latency or memory
constraints can provide `ConnectionManagerConfiguration` to
`CurlConnectionManager`. See [Connection Limit
Selection][http-testing-validation].

## Configuration Validation

Configuration constructors reject invalid values before creating Curl
resources. Concurrency and HTTP/2 stream limits must be positive. Idle limits
may be zero, which disables retention when both limits are zero.
`maxIdleTotal` must be greater than or equal to `maxIdlePerHost`.

Idle and connect timeouts must be finite and positive. Connection acquisition
timeout must be finite and non-negative, with zero disabling its deadline.
The streamed-response buffer must be at least 16 KiB. The upload buffer must
be between 16 KiB and 2 MiB. The bodyless buffered-download buffer accepts
zero, which leaves libcurl's default unchanged, or a value from 16 KiB
through 10 MiB.
Cipher allowlists accept literal cipher suite names instead of OpenSSL cipher
expressions.

Libcurl does not expose HTTP/2 initial-window configuration or a separate TLS
handshake timeout. Those settings are absent from the public configuration
model. The transport also omits low-throughput thresholds because they would
count intentional streaming backpressure as network failure.

## Resource Lifetime

`CurlClient` owns its multi handle and active transfer registry.
`CurlConnectionManager` owns idle easy handles and permit waiters.

`HttpClientInterface::close()` provides explicit shutdown. `CurlClient`
rejects new requests, fails active transfers, closes its multi handle, and
clears the connection manager. Destructors provide the same final safety net.
`CurlConnectionManager` closes idle easy handles and fails waiters, while
`FifoConnectionPool` closes queued easy handles. `CurlConnection` closes its
easy handle when instructed by the manager.

## Tuning Guidance

Increase `maxConcurrency` when requests queue for permits and the service,
host, and process still have capacity. Keeping `maxIdlePerHost` near expected
burst concurrency preserves warm easy handles between bursts.
`maxIdleTotal` should account for every route that needs warm capacity.

Reducing `responseBufferSize` lowers memory use for slow consumers. An
increase is justified when measurements show pause and resume overhead
limiting throughput. The 128 KiB `uploadBufferSize` default favors upload
throughput and CPU efficiency. Smaller values reduce memory at high upload
concurrency, while larger values require workload-specific measurements.
The 256 KiB `bufferedDownloadBufferSize` default favors completed buffered
download throughput. Zero leaves libcurl's buffer unchanged. It does not
affect streamed downloads.
`Async::map()` concurrency should remain at or below transport capacity
unless queued work and acquisition timeout are deliberate.

The local stress fixture uses a large listen backlog and warms persistent
connections before timing. This keeps connection establishment outside the
measured transfer window at high concurrency. Default changes require both
local and production endpoint measurements.

[http-testing-validation]: ../http/testing-and-validation.md
