# Curl HTTP Transport

**Status:** Current as of September 30, 2026

**Package:** `smithy-php` (`Smithy\Http\Client\`,
`Smithy\Http\Client\Curl\`)

## Class Roles

| Class | Responsibility |
|---|---|
| `CurlClient` | Request setup, multi-handle polling, callbacks, timeouts, and transfer cleanup |
| `CurlResponseState` | Headers, trailers, flow control, body limits, and read activity |
| `CurlConnectionManager` | Route permits, waiters, easy-handle pooling, and idle limits |
| `FifoConnectionPool` | Per-route idle easy-handle storage and timeout pruning |
| `CurlConnectionFactory` | New easy handles and connection-level options |
| `CurlConnection` | Easy-handle ownership, health, route, and usage state. Also the exchange for one active request |
| `CurlTransferException` | Libcurl failure plus retry-relevant request state |

`PendingRequest`, `UploadState`, and `CurlResponseState` hold mutable callback
state. A callback must not depend on stack variables that disappear while the
calling fiber is suspended.

## Shared Multi Handle

Each `CurlClient` owns one persistent `CurlMultiHandle`. All requests sent by
that client add their leased easy handles to this multi handle.

The multi handle lets easy handles make concurrent progress and share
libcurl's connection cache. It also provides HTTP/2 multiplexing when both
the server and libcurl support it. One poll loop drives every active transfer
on the client.

The client configures:

| Multi option | Value |
|---|---|
| `CURLMOPT_PIPELINING` | `CURLPIPE_MULTIPLEX` |
| `CURLMOPT_MAX_CONCURRENT_STREAMS` | HTTP/2 `maxStreams`, default 100 |
| `CURLMOPT_MAX_HOST_CONNECTIONS` | Manager `maxConcurrency`, default 100 |
| `CURLMOPT_MAXCONNECTS` | Manager `maxIdleTotal`, default 100 |

## Connection Setup

New and pooled easy handles use the same connection configurator. It applies
the 10-second connect timeout, TCP keepalive, HTTP version policy, and TLS
settings. Reapplying the same path after `curl_reset()` keeps pooled handles
consistent with newly created handles.

The FIFO pool retains easy handles, while the persistent multi handle owns
libcurl's physical connection cache. A ten-run A/B comparison set both idle
limits to zero so every released easy handle closed while route admission
remained unchanged. Zero retention regressed both upload modes and increased
RSS in every mode. The client therefore retains easy-handle reuse. The
experiment did not compare route-specific FIFO reuse with another easy-handle
pool topology. See [Easy-Handle
Retention][http-testing-validation].

A ten-run live S3 comparison selected the default host limit of 100 over 50.
At offered concurrency 450, 100 improved download throughput by 14.55 percent
and upload throughput by 28.04 percent. Peak RSS increased by 14.06 MiB for
downloads and 12.31 MiB for uploads. Higher exploratory limits increased
memory and tail latency. See [Connection Limit
Selection][http-testing-validation].

TLS 1.2 is the minimum protocol version for target and HTTPS proxy
connections. TLS configuration can replace trust roots, provide a client
certificate and private key, set separate TLS 1.2 and TLS 1.3 cipher
allowlists, or turn off peer verification. The same cipher policy applies to
the target and proxy legs. Constructor validation rejects malformed cipher
suite names before they reach OpenSSL.

## Send State Machine

```text
validate
   |
lease exchange
   |
configure easy handle and callbacks
   |
add to multi handle
   |
register PendingRequest
   |
suspend caller
   |
poll curl_multi
   |
   +--> final headers --> select response mode
   |                          |
   |                          +--> streamed --> return response
   |
   +--> success -------> finish body and trailers
   |
   +--> failure -------> fail caller or delivered body
   |
   +--> cancellation --> fail caller or delivered body
   |
remove easy handle
   |
release healthy connection or close unhealthy connection
```

The transfer owns the exchange after the easy handle joins the multi handle.
All terminal paths call `finalizePendingRequest()`.

## Polling

The first pending request defers an immediate poll to the next event-loop
turn. The loop calls `curl_multi_exec()` until libcurl no longer requests an
immediate repeat, then reads completed handles from
`curl_multi_info_read()`.

Upload read callbacks, response header callbacks, and response write
callbacks set one client-wide activity flag during `curl_multi_exec()`.
Transfers whose byte movement is visible through those callbacks require no
progress-counter scan.

The client keeps a compact scan mask only for activity hidden inside
libcurl. Native resource uploads and `CURLOPT_POSTFIELDS` inspect uploaded
bytes. Buffered responses inspect downloaded bytes. Requests without an
upload inspect pretransfer state until response timeout tracking starts.
Paused and fully callback-observable transfers remain outside the scan set.

Completion processing gives stored upload and response callback errors
precedence over the libcurl result. It then handles cancellation, libcurl
failure, or success. Another poll is scheduled while any request remains.

Callback activity, scanned byte progress, or completion starts a productive
polling streak. The client continues polling through deferred callbacks for
at most 1 ms and 64 polls. Reaching either limit schedules the next poll on a
zero-delay timer. This forces a complete event-loop turn without adding an
intentional transport delay.

A poll that observes no progress ends the productive streak. The backoff state
starts at 1 ms and doubles before the next idle poll is scheduled, producing
2, 4, 8, and then 10 ms delays. Further idle polls remain capped at 10 ms.
Any observed progress resets the state to 1 ms. Adding or resuming work cancels
a backed-off timer and requests an immediate poll.

Callbacks can request another immediate poll while a poll is running. The
client records that request instead of entering `curl_multi_exec()`
recursively. The current iteration finishes, then the next event-loop turn
runs the requested poll.

## Request Setup

Per-request setup maps options to libcurl:

| Behavior | Curl setting |
|---|---|
| URL | `CURLOPT_URL` |
| Method | `CURLOPT_CUSTOMREQUEST` |
| Native buffered response | `CURLOPT_RETURNTRANSFER` |
| Response receive-buffer preference | `CURLOPT_BUFFERSIZE` |
| Redirects | `CURLOPT_FOLLOWLOCATION` |
| Initial request timeout | `CURLOPT_TIMEOUT_MS` |
| Proxy | `CURLOPT_PROXY` |
| Content decoding | `CURLOPT_HTTP_CONTENT_DECODING`, `CURLOPT_ENCODING` |
| Native resource upload | `CURLOPT_UPLOAD`, `CURLOPT_READDATA` |
| Callback upload | `CURLOPT_UPLOAD`, `CURLOPT_READFUNCTION` |
| Upload buffer size | `CURLOPT_UPLOAD_BUFFERSIZE` |
| Known upload length | `CURLOPT_INFILESIZE_LARGE` or `CURLOPT_INFILESIZE` |
| Streamed response | `CURLOPT_WRITEFUNCTION`, `CURLOPT_BUFFERSIZE` |
| Response headers and trailers | `CURLOPT_HEADERFUNCTION` |
| Connection availability | `CURLOPT_PIPEWAIT` |

Request headers are copied from the PSR-7 request. When decoding is enabled,
the client lets `CURLOPT_ENCODING` own `Accept-Encoding` so negotiation and
decoding remain consistent.

Every response begins with native return capture. For a request with no body
or transfer encoding, `bufferedDownloadBufferSize` controls
`CURLOPT_BUFFERSIZE` and defaults to 256 KiB. Requests with an upload use
`responseBufferSize`, capped at 512 KiB. The default is 64 KiB. Final-header
selection does not resize that buffer. `responseBufferSize` also bounds a live
streamed response queue.

`CURLOPT_PIPEWAIT` is enabled for Automatic and enforced HTTP/2 policies. It
asks libcurl to wait for an existing connection that is still negotiating to
become available for multiplexing instead of immediately opening another
connection. Enforced HTTP/1.1 disables it because that connection cannot
multiplex, and waiting only delays cold request admission.

## Request Framing

An explicit `Content-Length` supplies the upload length. An explicit
`Transfer-Encoding` suppresses libcurl upload-length configuration, and the
client rejects a request that contains both headers. For an unknown-length
upload, libcurl selects the protocol framing: HTTP/1.1 may use chunked transfer
encoding, while HTTP/2 carries the body in DATA frames without an HTTP/1.1
`Transfer-Encoding` header.

`aws-chunked` is an AWS signing and payload transformation represented by
`Content-Encoding: aws-chunked`. The signing and checksum layers above the
transport create that representation. Libcurl owns HTTP transfer framing.
PHP does not expose libcurl's request-trailer callback, so `CurlClient`
rejects request trailers.

## Upload Sources

Readable `ResourceRequestStream` instances backed by native PHP stream wrappers
use `CURLOPT_READDATA`. Libcurl reads file, memory, and temporary resources
directly. `ResourceRequestStream` derives started and fully consumed state from
the resource cursor and the transfer's `CURLINFO_SIZE_UPLOAD_T` value. The
byte counter covers native file reads that do not advance the PHP-visible
cursor.
Opener-backed streams allocate the attempt resource lazily and retain this
native path when the returned resource uses a native wrapper.

Resources backed by user-space wrappers use the upload callback. Exceptions
from a user-space wrapper can escape an ext-curl resource read through the
event-loop callback. Reading those resources through
`CURLOPT_READFUNCTION` keeps the exception inside the transport error path.

Generic PSR-7 streams and asynchronous producer streams also use the upload
callback.

`HttpRequestOptions::$uploadBufferSize` controls libcurl's preferred upload
buffer size. The 128 KiB default reduces upload CPU and improves throughput
over libcurl's 64 KiB default while keeping the memory cost bounded per
active upload.

Generated SDK streaming inputs preserve a caller-supplied
`ResourceRequestStream` through HTTP binding serialization, signing, checksum
calculation, and request construction. The SDK does not convert generic
PSR-7 streams or PHP resources into `ResourceRequestStream`. Callers reach the
native upload path only when they pass that body implementation explicitly.

### Upload callback

The upload callback returns a non-empty string with at most the requested
byte count when data is available. It returns an empty string at EOF or after
capturing a read failure. When an asynchronous source has neither data nor a
terminal state, it returns `CURL_READFUNC_PAUSE`.

Reading an `AsyncRequestStream` also releases producer capacity. A blocked
producer resumes on a deferred event-loop callback once its next chunk fits
the request queue.

The callback catches every `Throwable` and stores it on `UploadState`.
Libcurl sees EOF, then the poller turns the stored cause into `HttpException`.
This preserves exceptions thrown by user-provided streams.

### Length enforcement

For a known upload length, the callback caps every read to the remaining byte
count. Reaching the declared count triggers an exhaustion check.

Synchronous streams are checked immediately. An asynchronous stream that has
not ended retains the final chunk and pauses. EOF after readiness releases
the retained bytes. Extra data raises `UnexpectedValueException`, and a
producer failure becomes the previous exception on the transport error.

Retained delivery obeys the current libcurl read limit. If libcurl requests a
smaller chunk after resume, the state returns a prefix and retains the rest.

### Upload pause and resume

`AsyncRequestStreamInterface::subscribe()` registers a readiness callback. The
callback schedules resume through `EventLoopInterface::defer()`.

Resume is deferred because producer notification can occur inside body
mutation. The deferred callback rechecks pending state and calls
`curl_pause()` only when the transfer still exists and remains paused.

## Header Callback

Libcurl invokes the header callback for status lines, header fields, blank
lines, interim responses, proxy CONNECT responses, redirects, and trailers.

`CurlResponseState` keeps a current header block separate from final response
metadata. A non-interim blank line commits the current status, protocol
version, and headers. Redirect blocks are interim when redirect following is
enabled. Content-decoding normalization removes encoded representation
headers before response-mode selection.

Bodyless responses and responses with one valid `Content-Length` value no
larger than `maxBufferedResponseBytes` remain buffered. The threshold defaults
to 1 MiB. Unknown, ambiguous, decoded, and larger bodies select streaming.

When the headers select streaming, the header callback disables the native
whole-transfer timeout and return capture, installs the write callback,
creates the bounded `StreamingResponseStream`, and resumes the caller. These steps
complete before libcurl delivers the first response body byte. Known-small
responses retain native buffering and complete before the caller
resumes.

The adaptive response path uses native return capture or the write callback.
It excludes `CURLOPT_FILE`, `php://temp`, and other file sinks.

Header blocks after final response headers populate response trailers.

## Body Callback

The body callback runs for every streamed response. It also runs for a
buffered response when `maxResponseBodyBytes` is enabled, because the client
must inspect each decoded chunk before accepting it.

It rejects a chunk before accepting any bytes that would exceed the decoded
body limit.

For a streamed response, it calls
`StreamingResponseStream::append()`. If the chunk would exceed the high water mark,
it records the blocked chunk length and returns `CURL_WRITEFUNC_PAUSE`.
Libcurl retains the callback chunk and replays it after receive resume.
Accepted chunks remain individually queued in `SplQueue`. An indexed-array
queue was benchmarked and reverted after every measured Smithy metric
regressed.

Receive resume uses a low water mark equal to half of the configured response
buffer size. The consumer must drain the buffer to that mark, and the retained
callback chunk must fit under the high water mark, before the client resumes
libcurl. This hysteresis reduces repeated pause and resume cycles while
preserving the configured memory bound. Discard mode resumes immediately
because it does not retain response chunks.

The response stream arms its space-available notification when `append()`
rejects a chunk. Reads made before backpressure do not call into the
transport. After the buffer drains enough for the retained chunk, the stream
invokes the notification once and disarms it. This avoids download-resume
scheduling and pending-transfer lookups on ordinary streamed reads.

For a size-limited buffered response, it appends to a private string.
Unlimited buffered responses remain inside libcurl and are retrieved with
`curl_multi_getcontent()`.

## Combined Pause State

One easy handle can have a paused upload, a paused download, or both. Every
resume computes the complete state:

```text
upload paused   -> CURLPAUSE_SEND
download paused -> CURLPAUSE_RECV
both paused     -> CURLPAUSE_SEND | CURLPAUSE_RECV
neither paused  -> 0
```

This prevents an upload readiness event from accidentally resuming a
backpressured download, and prevents response capacity from resuming an
unavailable request source.

## Timeout Semantics

| Option | Buffered outcome | Streamed outcome |
|---|---|---|
| `timeout` | Libcurl whole-transfer timeout | Time from attempt start to final response headers |
| `readTimeout` | Response inactivity | Response inactivity |
| `connectTimeout` | Connection establishment | Connection establishment |
| `connectionAcquireTimeout` | Wait for route permit | Wait for route permit |

The request initially uses libcurl's whole-transfer timeout. If final headers
select streaming, the header callback disables that timeout before body
delivery. The returned body may then outlive `timeout`. `readTimeout`
continues to guard response inactivity.

Zero disables request, read, and acquisition timeouts. Connect timeout must
be positive because libcurl assigns its own default when configured with
zero.

The read inactivity timer starts on the first response activity. If no
response has arrived, upload completion starts it. Later header or body
activity resets it. Time spent paused because the caller's response buffer is
full does not count as read inactivity.

Manual header and read timeouts share one event-loop timer set to the earliest
active deadline. Timeout expiry scans pending requests only when that timer
fires. The callback cancels a delayed poll and runs one final
`curl_multi_exec()` iteration before evaluating deadlines. Ready I/O and
completed transfers therefore win over timer callback ordering at the timeout
boundary. A later read extends its deadline without replacing the earlier
timer. The earlier timer recomputes the next deadline when it fires.

Libcurl owns hostname resolution, DNS caching, address selection, and
connection-stage failover within the configured request and connect
timeouts.

## Completion

Successful completion first verifies that final headers arrived, marks the
connection used, and completes response trailers. The buffered path then
releases the size-limited callback buffer or the bytes held by libcurl. It
then removes the easy handle, releases the exchange, and resumes the caller.

The streamed path creates a response only if the header callback did not
already deliver one. It removes the easy handle and releases the exchange
before ending the body stream. Queued bytes remain readable because the body
object owns them independently of the Curl connection.

## Failure Routing

Before response delivery, a failure resumes the suspended `sendRequest()`
call by throwing.

After streamed response delivery, the same failure is stored on the body and
trailers. A body reader receives queued bytes first and then the failure.

Every transfer failure marks the connection unhealthy. Release closes an
unhealthy handle instead of pooling it. `HttpException` retains a sanitized
copy of the PSR-7 request, without authorization or proxy-authorization
headers, and, when available, the stream, cancellation, or libcurl cause. It
also records whether the request body started, was fully consumed, and can be
sought.

Failures classified by `CurlClient` as transport failures are exposed through
`HttpException` or a subclass and preserve the underlying cause. Invalid
request options and framing, incompatible custom connection-manager results,
and violated internal invariants remain argument or logic errors rather than
transport failures.

The built-in connection manager reports acquisition timeout through
`ConnectionAcquireTimeoutException`, which extends `HttpException`.
`CurlClient` preserves existing `HttpException` subclasses and wraps any
other exception thrown by an injected connection manager or connection
factory during acquisition. Standalone release and upload-cleanup failures
are also wrapped with the original exception as their cause.

Cleanup always continues through connection release. When a transfer and its
cleanup both fail, the transfer failure remains primary. A cleanup failure
after a streamed response was delivered is stored on its body and trailers.
Invalid exchange types remain contract violations reported through
`LogicException`.

HTTP status codes are successful transport results. Protocol code above the
HTTP client maps modeled error responses.

## Cancellation

Cancellation is checked before connection acquisition. The connection manager
also observes cancellation while a request waits for capacity.

An active transfer subscribes to the same signal. Cancellation notification
stores the reason and defers removal from the multi handle. This avoids
mutating transfer collections inside an arbitrary subscriber callback.

`RequestCancelledException` preserves request-body state and chains the
`CancelledException`.

## Streamed Body Closure and Abandonment

Closing an active `StreamingResponseStream` invokes its transport callback.
`CurlClient` removes the pending request, marks the connection unhealthy,
fails trailers, and releases the exchange.

After response delivery, `CurlResponseState` keeps only a weak reference to
the body. Abandoning both response and body allows the body destructor to
close the transfer. The transport does not keep an unread response alive.

## Cleanup Invariants

Every terminal path removes cancellation and upload-readiness subscriptions,
then clears retained upload data and both pause states. It removes the easy
handle from the multi handle and releases the route permit exactly once. The
current consumer is also resumed or failed exactly once.

The client destructor applies the same resource cleanup to any transfer still
pending when the client leaves scope.

[http-testing-validation]: ../testing-and-validation.md
