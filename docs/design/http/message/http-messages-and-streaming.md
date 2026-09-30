# HTTP Messages and Streaming

**Status:** Current as of September 30, 2026. Curl request trailer transmission
remains unsupported.

**Package:** `smithy-php` (`Smithy\Http\Client\`, `Smithy\Http\Message\`)

## Message Boundary

`HttpClientInterface` accepts `Psr\Http\Message\RequestInterface` and returns
`Smithy\Http\Message\ResponseInterface`.

The Smithy response extends the PSR-7 response contract with:

```php
interface ResponseInterface extends
    \Psr\Http\Message\ResponseInterface
{
    public function discardBody(): void;

    public function getTrailers(): ResponseTrailers;
}
```

The extended interface keeps body disposal and trailer state at the HTTP
boundary. Protocol deserializers can still consume the response as PSR-7.

## PSR-7 Implementation

`Request`, `Response`, `Uri`, and the stream types implement PSR-7 contracts.
Request and response mutation methods clone the message. `MessageTrait`
stores headers by lowercase lookup key alongside the currently stored
field-name casing and ordered value list.

The runtime uses these immutable PSR-7 messages throughout request processing.
Trusted runtime paths preserve normalized headers through serialization,
endpoint application, checksums, and signing.
`fromNormalized()`, `withNormalizedHeader()`, and
`withNormalizedHeaders()` bypass repeated name and value normalization while
retaining ordinary validated PSR-7 methods for external input.

The mutable message experiment cut isolated CPU by 15.60 to 22.82 percent and
peak memory by 20.83 to 22.86 percent. Converting each completed mutable
request into PSR-7 at the transport boundary added 548 to 652 nanoseconds. In
the complete no-network S3 request pipeline, the mutable path reduced peak
request-boundary memory by 9.47 to 11.86 percent and reduced throughput by
0.77 to 2.33 percent. Removing the mutable abstractions while retaining the
normalized-header fast paths improved no-network throughput by 5.69 to 7.51
percent and HTTP transfer throughput by 1.25 to 2.82 percent. The runtime
retains immutable PSR-7 messages and the normalized-header fast paths. See
[Message Representation
Evaluation][http-testing-validation].

`HttpMessageValidator` validates methods, protocol versions, status codes,
header names, header values, and reason phrases. A request derives its Host
header from the URI when the caller did not provide one.

### Header Representation

External header arrays enter as field names mapped to a string or list of
strings. `Request` and `Response` normalize that form into the internal
representation:

```text
lowercase field name => [original field name, [values]]
```

Normalization validates each field name and value, trims field values, and
allocates the internal array. The work scales with the number of fields.
Profiling `restXml_CopyObjectRequest_M` showed request construction consuming
54 percent of samples when `Request` normalized headers that the protocol
serializer had already produced in internal form. Median serialization time
increased from 5.541 to 13.458 microseconds.

Protocol serializers pass internal request headers directly to
`Request::fromNormalized()`. Fixed modeled names are validated when cached
HTTP binding metadata is created. Protocol serializers validate
application-controlled header values and complete prefix-header names before
adding them to the internal representation. Generated mutations use the
trusted normalized single-header and bulk-header methods for values that are
safe by construction. These paths assign internal tuples without another
validation, lowercasing, trimming, or reshaping pass. External request
headers still use the validated constructor and ordinary PSR-7 mutation
methods. Response headers collected from libcurl use the external form and
are normalized once when `Response` is constructed. New message paths should
normalize only when crossing from an external representation into the
internal representation.

`Uri` parses and normalizes arbitrary URI strings. `ComponentUri` accepts
prevalidated components and skips parsing and regex filtering on protocol
serialization paths.

String bodies become `StringRequestStream` instances. Existing PSR-7 streams are
wrapped by `StreamBodyAdapter`, which preserves the underlying stream while
adding request-body state.

## Body Stream Contract

`RequestStreamInterface` extends PSR-7 `StreamInterface` with transport state:

| Method | Meaning |
|---|---|
| `length()` | Remaining body length known before transmission, or `null` |
| `hash()` | Hash the complete logical body when the stream supports it |
| `isReplayable()` | Whether the logical body can be consumed again |
| `isAvailable()` | Whether another body consumption can begin |
| `rewindForRetry()` | Restore the initial transport position |
| `started()` | Whether the transport consumed body bytes |
| `fullyConsumed()` | Whether all body bytes reached the transport consumer |

`started()`, `fullyConsumed()`, and `isSeekable()` are copied into
`HttpException`. Retry policy above the HTTP client also consults
`isReplayable()` on the request body before resetting it. Replayability is
separate from seekability: a body can recreate its resource without seeking
the resource used by the previous attempt. The HTTP client itself does not
perform modeled operation retries.

### Implementations

`StringRequestStream` represents an in-memory request body.
`ResourceRequestStream` wraps a PHP resource and remembers its initial
position for retries. `StreamBodyAdapter` adds the request-body state contract
to an arbitrary PSR-7 stream. Buffered responses use the ordinary seekable
`Stream`. Live responses use `StreamingResponseStream`.

`ResourceRequestStream` accepts an already-open resource through its constructor.
`temp()` creates the temporary-resource convenience body. `create()` accepts a
resource opener and optional known length. It defers allocation until the body
is hashed, inspected by the transport, or consumed. The opener must return a
fresh resource positioned at the logical body start. `fromFile()` retains a
path and known file size while opening a fresh file resource for each attempt.

For opener-backed bodies, `rewindForRetry()` closes the current attempt
resource and leaves the opener intact. The next signing, checksum, or transport
operation opens a replacement. `close()` and `detach()` are terminal and
discard the opener. Existing directly wrapped resources retain their seek and
rewind behavior.

The two live streaming implementations have different ownership.
`AsyncRequestStream` receives bytes from a request producer, while
`StreamingResponseStream` receives bytes from the transport through a bounded
queue.

## Request Body Selection

`CurlClient` normalizes every PSR-7 request body through
`StreamBodyAdapter::wrap()`.

An empty body with a resolved length of zero is omitted. A non-empty
`StringRequestStream` without `Transfer-Encoding` uses `CURLOPT_POSTFIELDS`.
Readable `ResourceRequestStream` instances backed by native PHP wrappers use
`CURLOPT_READDATA`, which lets libcurl read the resource without a PHP
callback for every chunk. This includes lazily opened native resources. The
stream refreshes its started and consumed state from the resource cursor and
from libcurl's uploaded-byte count. Native reads through `CURLOPT_READDATA`
can leave the PHP-visible cursor unchanged, so the transport counter is
authoritative for those transfers.

Generic PSR-7 streams, asynchronous producer streams, and resource streams
backed by user-space wrappers use `CURLOPT_READFUNCTION`. User-space wrappers
stay on the callback path so read exceptions can be attached to the
request-aware transport exception.

The buffered string and native resource paths avoid callback overhead. The
read-callback path preserves streaming and exception handling for other body
representations.

HTTP payload serialization returns a caller-supplied stream unchanged.
Generated S3 and Lambda operations with blob payloads can therefore carry a
`ResourceRequestStream` to `CurlClient`. No SDK adapter creates this type from a
generic PSR-7 stream or raw PHP resource. A generic stream remains on
`CURLOPT_READFUNCTION` even when its inner storage is a native resource.

## Request Framing

An explicit `Content-Length` is authoritative. Without it, the transport uses
`RequestStreamInterface::length()`. If neither supplies a length, the upload
remains unknown-length. An explicit `Transfer-Encoding` suppresses upload
length configuration and leaves framing to the transfer encoding.

The client rejects conflicting `Content-Length` and `Transfer-Encoding`
headers. It also rejects malformed, repeated, negative, and out-of-range
lengths, along with a known body length that differs from the declared
length. During transmission, ending early or producing extra data fails the
request. A synchronous stream must not return an empty string before EOF.
Asynchronous streams express that temporary state through readiness and
pause instead.

Libcurl selects protocol framing for unknown-length uploads. For HTTP/1.1,
this can produce chunked transfer encoding. For HTTP/2, libcurl maps the body
to DATA frames.

## Asynchronous Request Producers

`AsyncRequestStream` separates producer readiness from EOF:

```text
unavailable --write(bytes)--> readable
unavailable --end()---------> EOF
unavailable --fail(error)---> failed
readable ----read(all)------> unavailable or EOF
```

The producer passes each non-empty chunk to `write($chunk)` and calls `end()`
after its final chunk. If production cannot continue, `fail($error)` makes
that error observable to the transport.

The request queue has a 64 KiB high water mark by default. When the next
chunk does not fit, `write()` suspends through the event loop. A transport
read schedules the producer to resume after enough capacity becomes
available. Resumption is deferred so producer code does not run inside
libcurl's read callback.

One producer may wait for capacity at a time. A chunk larger than the high
water mark is accepted when the queue is empty, which bounds queued memory by
the greater of the configured limit and the largest individual write.
Removing the last transport readiness subscription wakes a blocked producer
with an error, so cancellation and transfer failure cannot strand its fiber.

The Curl upload callback reads only when `isReady()` is true. If the stream is
unavailable, it returns `CURL_READFUNC_PAUSE`. A readiness subscription
schedules upload resume on the event loop.

One producer can write the final declared bytes and call `end()` on a later
event-loop turn. The transport withholds that final chunk until the producer
reaches a terminal state. EOF at the declared length releases the retained
chunk. Extra data fails with a length error, while producer failure preserves
the producer exception.

This rule prevents libcurl from completing the request before asynchronous
production has reached EOF.

`AsyncRequestStream` is not seekable or replayable. A retry after consumption
must fail unless a higher layer supplies a new body.

## Signing Boundary

The HTTP runtime does not select request signing policy. It exposes whether a
body is seekable and provides `RequestStreamInterface::hash()` so a signer can
hash a complete logical body without converting it to a string. Resource
streams use PHP's incremental hashing API and restore their original
position. A signer must reject a non-seekable stream unless the request
already carries a content hash or the service selects a supported streaming
signing mode.

The AWS SigV4 layer follows that contract. Eligible S3-family HTTPS uploads
use `UNSIGNED-PAYLOAD` for non-seekable bodies. Other non-seekable uploads
fail before the transport consumes bytes.

`aws-chunked` is an AWS payload-signing representation identified by
`Content-Encoding: aws-chunked`. It is distinct from HTTP/1.1
`Transfer-Encoding: chunked`. Modeled checksum policy, `aws-chunked`
transformation, and signing remain above the generic HTTP client. The
transformed body and its encoded length would enter the transport as ordinary
request content.

## Request Trailers

`RequestTrailers` models a lazy trailer provider. A provider can declare its
encoded field-section length and a callback that resets provider state before
retry.

Trailer resolution validates HTTP token field names and requires at least one
string value per field. Values cannot contain prohibited control bytes.
Resolution also rejects fields prohibited by HTTP trailer rules and verifies
the declared field-section length when one was supplied.

`CurlClient` rejects request trailers before leasing a connection. PHP does
not expose libcurl's `CURLOPT_TRAILERFUNCTION`, so the transport cannot attach
trailers to libcurl-managed HTTP/1.1 or HTTP/2 framing. `RequestTrailers`
remains available to transports that expose native trailer support. This
currently prevents the Curl transport from sending `aws-chunked` variants
that require checksum trailers.

## Response Body Selection

`CurlClient` waits for final normalized response headers, then buffers a body
only when one valid `Content-Length` value is no larger than
`maxBufferedResponseBytes`. The default threshold is 1 MiB.

Bodyless responses, including HEAD responses and status codes that prohibit a
body, are buffered. Missing, repeated, malformed, out-of-range, and
above-threshold lengths select streaming. When libcurl decodes a non-identity
content coding, response normalization removes the encoded
`Content-Length`, so the decoded body is streamed.

## Buffered Responses

Buffered delivery returns after the complete response arrives. Unlimited
responses use `CURLOPT_RETURNTRANSFER` and read the result through
`curl_multi_getcontent()`. When `maxResponseBodyBytes` is nonzero,
`CurlResponseState` installs a body callback and buffers accepted bytes so it
can reject the first chunk that crosses the decoded size limit. Both paths
return an ordinary `Stream`.

Buffered responses do not use `php://temp`, `CURLOPT_FILE`, or a disk-backed
sink. The body remains in memory until it becomes a response `Stream`.

`bufferedDownloadBufferSize` is a separate initial cURL transfer-buffer
preference for requests with no body or transfer encoding. It defaults to
256 KiB.

## Streamed Responses

Streamed delivery returns after final response headers. The transport creates
the `StreamingResponseStream` lazily when final headers select streaming, and
the response body remains attached to the active transfer.

```text
libcurl body callback
        |
        v
StreamingResponseStream::append()
        |
        +--> queue chunk and wake reader
        |
        +--> reject chunk when high water mark would be exceeded
                    |
                    v
             CURL_WRITEFUNC_PAUSE

caller read/discard
        |
        v
capacity becomes available
        |
        v
event-loop deferred curl_pause(..., receive enabled)
```

`responseBufferSize` provides the preferred cURL receive-buffer size for
requests with an upload, capped at 512 KiB for the cURL option. It also sets
the live queue's high water mark without that cap. The default is 64 KiB. The
minimum accepted request option is 16 KiB because libcurl may deliver a
response callback chunk of that size. The high water mark applies per active
streamed response.

The stream stores chunks in an `SplQueue` and keeps an offset into the current
chunk. A read returns bytes from at most one queued chunk, even when the
caller requests more. PSR-7 permits returning fewer bytes than requested.
Returning a complete queued chunk directly avoids copying it into a joined
string. Partial reads copy only the requested substring.

An indexed PHP array was benchmarked as the response queue representation.
It regressed throughput, CPU, latency, and memory relative to the retained
`SplQueue` implementation and was reverted. See [Response Queue
Representation][http-testing-validation] for
the measured comparison and host-variation caveat.

The response stream arms its transport notification only after `append()`
rejects a chunk. It records that chunk's length and invokes the notification
once the consumer reaches the low water mark and the retained chunk can fit.
Ordinary reads while libcurl is producing data do not invoke the transport
closure.

### Read behavior

`read($length)` returns buffered bytes immediately. When no bytes are
available and the transfer is active, it suspends the current flow. The body
callback wakes the reader when data arrives. Transfer completion, failure,
or closure also wakes the reader. Callers that need the complete remaining
body use `getContents()`, which continues reading across queued chunks.

The stream permits one active reader. Concurrent reads, or a read concurrent
with discard, throw `RuntimeException`.

### Failure behavior

If a transfer fails after the response has been returned, the body retains
the failure. Reads consume bytes that were already buffered, then throw the
original transport exception.

If a transfer fails before final headers, `sendRequest()` throws instead of
returning a response.

## Response Ownership

A streamed response has five terminal consumption paths:

| Caller action | Transport action | Connection outcome |
|---|---|---|
| Read through EOF | Complete transfer | Eligible for reuse |
| `discardBody()` | Drain without retaining bytes | Eligible for reuse |
| Close while transfer is active | Abort transfer | Mark unhealthy and close |
| Close after transfer completion | Drop queued bytes | Already released |
| Abandon an active response/body | Body destructor closes stream | Abort transfer |

`discardBody()` is the preferred way to ignore a response while retaining
connection reuse. It clears queued bytes, accepts future chunks without
storing them, and waits for transfer completion.

Closing a body while its transfer is active prioritizes bounded resource
lifetime over connection reuse. It removes the transfer from the multi handle
and releases an unhealthy connection to the manager, which closes it. If the
transfer already completed, the connection was already released and close
only drops queued body bytes.

`CurlResponseState` owns the body strongly until the response is delivered.
It then keeps a weak reference. This lets abandonment destroy the body and
trigger the close callback instead of keeping an unread transfer alive.

## Response Trailers

Every Smithy response carries a `ResponseTrailers` object.

For buffered responses, successful transfer completion makes trailers
available before `sendRequest()` returns. For streamed responses, the object
remains pending until the body transfer completes.

Trailer access before completion throws `LogicException`. A failed transfer
stores the same transport exception in the trailer object, and trailer access
throws it.

Header matching is case-insensitive. The object preserves the first observed
field-name casing when returning all fields.

Libcurl sends both response headers and trailers through the header callback.
`CurlResponseState` classifies header lines after final headers as trailer
fields and completes the shared trailer object at transfer completion.

## Content Decoding

`decodeContent` defaults to `true`.

When enabled, libcurl negotiates and decodes supported content codings. If a
non-identity coding was decoded, `CurlResponseState` removes
`Content-Encoding` and `Content-Length` from the returned response because
they describe the encoded representation.

When disabled and the request has no `Accept-Encoding`, the client requests
`identity`.

`maxResponseBodyBytes` counts decoded bytes received by the body callback.

[http-testing-validation]: ../testing-and-validation.md
