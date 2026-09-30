# HTTP Testing and Validation

**Status:** Evidence recorded August 30, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

## Scope

Validation uses PHPUnit for state and failure paths, local fixtures for
transport faults and timing, live S3 for production network behavior, and
profiling for request-construction costs. The low-level HTTP client is the
target. Service-specific retry, signing, checksum, and protocol behavior
remains owned by `aws-sdk-php`.

## Focused Runtime Tests

The HTTP suite completed 319 tests with 1,077 assertions. The complete Smithy
suite on PHP 8.2.33 completed 1,784 tests with 5,033 assertions and six PHPUnit
deprecations.

Message and stream tests cover PSR-7 behavior, framing, body state,
backpressure, trailers, replayability, and terminal cleanup. `CurlClient`
tests cover protocol versions, redirects, proxies, pause states, timeouts,
cancellation, upload sources, adaptive responses, and exception routing.
Connection, async, and configuration tests cover permit limits, pooling,
fiber scheduling, defaults, option bounds, and TLS settings.

## AWS Integration Tests

The focused AWS auth suite completed 23 tests with 53 assertions. The protocol
suite completed 1,428 tests with 4,906 assertions and 33 PHPUnit deprecations.
The endpoint suite completed 94 tests with 186 assertions.

AWS tests cover SigV4 hashing, checksums, streaming payload serialization,
client configuration, and replay-aware retries. The end-to-end retry test
closes a four MiB upload after 64 KiB, opens a fresh file for the second
attempt, validates the complete SHA-256, and verifies resource cleanup.

The current checksum suite has three failing response-validation activation
cases. The credential suite has 14 errors and three failures because its
injected `MockHttpClient` does not implement the `sendRequest()` method used by the
providers. These failures prevent a current complete AWS-suite result. They do
not exercise `CurlClient`, whose focused suite is green.

## Local Transport Stress

A forked HTTP/1.1 fixture controls payloads, delays, truncation, trailers, and
connection closure. Upload, download, duplex, mixed, and lifecycle modes
validate body bytes, hashing, backpressure, cancellation, and cleanup.

Measurements include throughput, latency, RSS, PHP peak memory, file
descriptors, and easy-handle counts. Steady-state runs warm one connection per
concurrency slot. Cold runs include connection establishment.

### Cross-Client Transport Comparison

The comparison used x86 Linux, enforced HTTP/1.1, 1,000 two MiB transfers,
concurrency 50, 50 warmup transfers, and ten runs per client and mode. Every
transfer passed byte-count and SHA-256 validation.

* **Smithy PHP: asynchronous I/O.** Its direct-return `sendRequest()` API
  suspends the active fiber through Revolt while a shared libcurl multi handle
  performs I/O.
* **ReactPHP: asynchronous I/O.** Promises run network work through the React
  event loop and socket transport.
* **Amp: asynchronous I/O.** Fibers and Revolt drive Amp's socket transport.
* **Symfony: deferred synchronous.** `CurlHttpClient` returns lazy responses,
  then response consumption blocks the caller while `curl_multi` processes
  concurrent transfers.
* **Guzzle: promise-based synchronous wait.** The harness schedules
  concurrent transfers with `GuzzleHttp\Pool`, then blocks in `wait()` until
  `CurlMultiHandler` completes them.

The Guzzle streamed-download benchmark uses the same sink mechanism exposed
by S3 `GetObject` through `SaveAs`. Its custom PSR-7 sink hashes and discards
chunks instead of writing them to a destination.

| Mode | Rank | Client | Gbps | p50 | p99 | CPU | RSS |
|---|---:|---|---:|---:|---:|---:|---:|
| Buffered download | 1 | Smithy | 4.892 | 171.979 ms | 202.250 ms | 3.2609 s | 47.16 MiB |
| Buffered download | 2 | Symfony | 4.220 | 191.438 ms | 299.807 ms | 3.9005 s | 97.38 MiB |
| Buffered download | 3 | Amp | 3.012 | 271.337 ms | 364.567 ms | 5.3509 s | 112.42 MiB |
| Buffered download | 4 | ReactPHP | 2.967 | 277.654 ms | 322.758 ms | 4.9281 s | 53.90 MiB |
| Buffered download | 5 | Guzzle | 2.777 | 302.516 ms | 339.250 ms | 5.4007 s | 44.49 MiB |
| Buffered upload | 1 | Smithy | 13.423 | 57.316 ms | 96.674 ms | 1.0449 s | 49.24 MiB |
| Buffered upload | 2 | Symfony | 9.902 | 79.375 ms | 157.319 ms | 0.9505 s | 45.16 MiB |
| Buffered upload | 3 | Guzzle | 7.285 | 111.591 ms | 138.593 ms | 1.6097 s | 49.21 MiB |
| Buffered upload | 4 | ReactPHP | 5.196 | 152.032 ms | 249.613 ms | 2.3766 s | 53.32 MiB |
| Buffered upload | 5 | Amp | 4.376 | 179.017 ms | 296.383 ms | 3.4397 s | 81.78 MiB |
| Streamed download | 1 | Symfony | 6.596 | 123.148 ms | 212.727 ms | 1.9860 s | 98.95 MiB |
| Streamed download | 2 | Smithy | 6.521 | 126.231 ms | 166.505 ms | 2.3384 s | 49.68 MiB |
| Streamed download | 3 | Guzzle | 6.505 | 127.261 ms | 173.846 ms | 1.8515 s | 43.88 MiB |
| Streamed download | 4 | ReactPHP | 5.050 | 161.698 ms | 202.536 ms | 2.0479 s | 51.31 MiB |
| Streamed download | 5 | Amp | 3.687 | 224.608 ms | 300.302 ms | 3.9461 s | 51.72 MiB |
| Streamed upload | 1 | Smithy | 19.044 | 42.954 ms | 58.264 ms | 0.5516 s | 50.67 MiB |
| Streamed upload | 2 | Symfony | 11.597 | 69.801 ms | 126.910 ms | 0.7008 s | 41.85 MiB |
| Streamed upload | 3 | Guzzle | 11.140 | 72.240 ms | 98.256 ms | 0.6680 s | 47.20 MiB |
| Streamed upload | 4 | ReactPHP | 9.362 | 81.977 ms | 150.239 ms | 0.7955 s | 54.08 MiB |
| Streamed upload | 5 | Amp | 6.014 | 135.956 ms | 206.251 ms | 2.1942 s | 63.93 MiB |

### Reverted Response Sink Experiments

Two x86 experiment series evaluated response sinks before the client adopted
adaptive buffering and streaming.

#### Automatic Disk Sink

The two MiB comparison covered native buffering, selection-only gates, a PHP
callback, an asynchronous disk sink, and the rollback. Native variants used
ten runs. Callback, disk-sink, and rollback variants used five.

| Variant | Runs | Gbps | p50 | p99 | CPU | RSS |
|---|---:|---:|---:|---:|---:|---:|
| Original control | 10 | 3.348 | 250.757 ms | 291.091 ms | 4.3263 s | 47.82 MiB |
| Native gate 1 | 10 | 3.394 | 246.778 ms | 283.470 ms | 4.2757 s | 50.14 MiB |
| Native gate 2 | 10 | 3.383 | 247.749 ms | 286.570 ms | 4.2836 s | 48.76 MiB |
| Callback | 5 | 3.340 | 251.337 ms | 287.235 ms | 4.3981 s | 45.94 MiB |
| Automatic disk sink | 5 | 1.466 | 561.770 ms | 845.547 ms | 7.6605 s | 69.20 MiB |
| Rollback | 5 | 3.406 | 247.609 ms | 287.610 ms | 4.2757 s | 49.15 MiB |

The automatic disk sink reduced throughput by 56.21 percent, increased CPU
by 77.07 percent, and added 21.38 MiB RSS relative to the original control.
The callback path produced no throughput gain. All variants completed without
validation failures, and the automatic sink was reverted.

#### Completed-Response Temporary Sink

A second series directed completed buffered responses into
`php://temp/maxmemory:524288` through `CURLOPT_FILE`. The native control used
`CURLOPT_RETURNTRANSFER`. The hybrid variant retained native buffering for
bodies below one MiB and selected the temporary sink for unknown bodies or
bodies at least one MiB. Every dataset used ten runs.

| Payload | Variant | Gbps | p50 | p99 | CPU | RSS |
|---:|---|---:|---:|---:|---:|---:|
| 64 KiB | `php://temp` | 3.043 | 7.391 ms | 22.781 ms | 0.1264 s | 47.65 MiB |
| 64 KiB | Native | 3.342 | 6.547 ms | 17.646 ms | 0.1133 s | 47.09 MiB |
| 64 KiB | Hybrid | 3.504 | 6.890 ms | 17.048 ms | 0.1025 s | 46.96 MiB |
| 256 KiB | `php://temp` | 4.550 | 21.080 ms | 43.675 ms | 0.3523 s | 52.75 MiB |
| 256 KiB | Native | 4.513 | 20.648 ms | 73.343 ms | 0.3103 s | 47.73 MiB |
| 256 KiB | Hybrid | 4.902 | 20.576 ms | 40.671 ms | 0.3202 s | 48.51 MiB |
| 512 KiB | `php://temp` | 3.972 | 50.928 ms | 87.644 ms | 0.8932 s | 51.83 MiB |
| 512 KiB | Native | 4.756 | 42.495 ms | 64.398 ms | 0.6436 s | 47.34 MiB |
| 512 KiB | Hybrid | 4.996 | 41.793 ms | 75.262 ms | 0.6489 s | 46.35 MiB |
| 1 MiB | `php://temp` | 4.654 | 92.982 ms | 137.536 ms | 1.4570 s | 45.45 MiB |
| 1 MiB | Native | 4.488 | 90.484 ms | 122.725 ms | 1.4616 s | 48.36 MiB |
| 1 MiB | Hybrid | 4.690 | 92.781 ms | 139.602 ms | 1.4444 s | 45.42 MiB |
| 2 MiB | `php://temp` | 4.311 | 205.155 ms | 283.594 ms | 3.2401 s | 44.79 MiB |
| 2 MiB | Hybrid | 4.288 | 209.857 ms | 276.059 ms | 3.2493 s | 44.72 MiB |

The hybrid variant led throughput at each matched 64 KiB through one MiB
payload point. It exceeded native throughput by 4.50 to 8.62 percent across
those payloads. The hybrid was removed because unknown and large responses
remained buffered instead of entering a bounded caller-pulled stream.
`php://temp` can also perform synchronous disk writes from
`curl_multi_exec()` after its memory limit. The retained `Auto` mode uses
native buffering for known responses through one MiB and a bounded
caller-pulled stream for unknown or larger responses.

### Replayable Upload Source Validation

The x86 comparison alternated eager and replayable sources over ten rounds.
Each round sent 1,000 validated two MiB uploads at concurrency 50. The eager
source opened the file first. The replayable source supplied a lazy opener.

| Source | Gbps | p50 | p99 | CPU | RSS | Wall |
|---|---:|---:|---:|---:|---:|---:|
| Eager | 18.803 | 43.556 ms | 59.502 ms | 0.5573 s | 50.47 MiB | 0.8924 s |
| Replayable | 18.983 | 43.147 ms | 57.826 ms | 0.5531 s | 50.63 MiB | 0.8839 s |

All 20,000 operations passed byte-count and SHA-256 validation. Replayable
throughput was 0.96 percent higher, p50 was 0.94 percent lower, and wall time
was 0.95 percent lower. PHP peak memory remained six MiB. The differences are
within run-to-run variance and demonstrate no measurable cost from lazy
resource opening or replayability bookkeeping.

### Adaptive Response Validation

The historical threshold comparison tested one and two MiB responses against
one and two MiB thresholds. The benchmark harness temporarily exposed forced
buffered and streamed controls to isolate the automatic selector. Those
controls are not part of the current public API. Each case used ten runs of
1,000 requests, concurrency 50, and 50 warmups.

| Payload and threshold | Mode | Gbps | p50 | p99 | CPU | RSS |
|---|---|---:|---:|---:|---:|---:|
| 1 MiB / 1 MiB | Auto, buffered | 6.341 | 63.701 ms | 93.206 ms | 1.1760 s | 46.81 MiB |
| 1 MiB / 1 MiB | Forced Buffered | 6.251 | 64.873 ms | 91.918 ms | 1.2173 s | 45.60 MiB |
| 2 MiB / 1 MiB | Auto, streamed | 6.281 | 131.506 ms | 169.248 ms | 2.3965 s | 49.84 MiB |
| 2 MiB / 1 MiB | Forced Streamed | 6.297 | 131.636 ms | 171.388 ms | 2.3878 s | 49.80 MiB |
| 2 MiB / 2 MiB | Auto, buffered | 3.791 | 226.277 ms | 246.499 ms | 4.1009 s | 45.79 MiB |
| 2 MiB / 2 MiB | Forced Buffered | 3.802 | 225.757 ms | 244.928 ms | 4.0928 s | 45.41 MiB |
| 2 MiB / 2 MiB | Forced Streamed | 6.482 | 127.436 ms | 168.223 ms | 2.3620 s | 49.77 MiB |

At two MiB, raising the threshold made Auto match forced Buffered. Forced
Streamed delivered 70.99 percent more throughput and used 42.40 percent less
CPU than Auto. PHP peak memory averaged 108.80 MiB for Auto, 108.40 MiB for
forced Buffered, and 10.00 MiB for forced Streamed. The retained default
therefore remains one MiB.

An earlier matched comparison measured the optimization progression:

| Mode | Throughput | CPU | p50 | p99 | RSS |
|---|---:|---:|---:|---:|---:|
| Buffered download | +1.56% | +1.93% | -1.61% | -4.24% | +0.86% |
| Streamed download | +4.39% | -4.52% | -4.73% | -9.15% | -7.61% |
| Streamed upload | +61.98% | -35.80% | -38.84% | -37.20% | +6.93% |

The control clients moved between -4.61 and +8.06 percent in throughput
across the download modes. Their streamed-upload throughput moved between
-2.17 and +3.18 percent. Smithy's upload change therefore exceeds the
observed host-wide movement. The download changes remain within the control
range.

The local transport microbenchmark isolates client scheduling, buffering,
callbacks, and body movement. DNS, TLS handshakes, network latency, packet
loss, HTTP/2 negotiation, and service processing require live endpoint or
network-shaped comparisons. Those comparisons must retain the same payload
validation and client configuration before extending these conclusions.

### HTTP/1.1 Connection Admission

`CurlClient` disables `CURLOPT_PIPEWAIT` only when
`HTTPVersionPolicy::EnforceHTTP_1_1` is selected. Automatic and HTTP/2
policies retain it so libcurl can wait for a connection that is still
negotiating to reveal whether it can multiplex, instead of immediately opening
another connection.

Warmed comparisons used 50 warmups before each 1,000-request run and showed
no Smithy-specific throughput change. Cold comparisons measured one
50-request wave at concurrency 50 over ten runs per client and mode.

| Cold Smithy mode | Throughput | CPU | p50 | p99 | RSS |
|---|---:|---:|---:|---:|---:|
| Buffered download | +12.96% | -1.27% | -27.94% | -14.62% | +2.33% |
| Streamed download | +19.61% | -4.90% | -28.35% | -22.23% | +0.23% |
| Streamed upload | +10.00% | -3.16% | -15.97% | -13.95% | +0.02% |

Control-client throughput changed by at most 3.60 percent between the cold
datasets. Smithy's gains exceed that movement in every mode. The result
supports disabling `PIPEWAIT` for enforced HTTP/1.1, where opening cold
connections promptly matters, while retaining it for policies that can use
HTTP/2 multiplexing.

### Easy-Handle Retention

The comparison used 1,000 two MiB transfers, concurrency 50, 50 warmups,
enforced HTTP/1.1, and ten runs per mode. The pooled variant retained 50 idle
handles. The no-idle variant closed each handle on release.

| Mode | Retention | Gbps | p50 | p99 | CPU | RSS | PHP peak |
|---|---|---:|---:|---:|---:|---:|---:|
| Buffered download | Pooled | 4.738 | 181.521 ms | 207.320 ms | 3.2765 s | 47.74 MiB | 110.60 MiB |
| Buffered download | None | 4.728 | 179.105 ms | 229.801 ms | 3.3070 s | 58.07 MiB | 1,521.13 MiB |
| Streamed download | Pooled | 6.475 | 127.309 ms | 167.654 ms | 2.3486 s | 49.82 MiB | 10.00 MiB |
| Streamed download | None | 7.087 | 114.013 ms | 175.273 ms | 2.1223 s | 60.31 MiB | 14.00 MiB |
| Buffered upload | Pooled | 13.285 | 58.415 ms | 102.074 ms | 1.0740 s | 48.83 MiB | 8.00 MiB |
| Buffered upload | None | 9.891 | 81.380 ms | 112.487 ms | 1.5568 s | 94.00 MiB | 12.00 MiB |
| Streamed upload | Pooled | 19.045 | 43.140 ms | 58.294 ms | 0.5601 s | 50.70 MiB | 6.00 MiB |
| Streamed upload | None | 17.938 | 44.708 ms | 61.080 ms | 0.6374 s | 132.18 MiB | 10.00 MiB |

Without retention, buffered-upload throughput fell 25.55 percent and CPU rose
44.95 percent. Streamed-upload throughput fell 5.81 percent and CPU rose
13.80 percent. Buffered-download throughput remained within 0.20 percent, but
p99 rose 10.84 percent and PHP peak memory rose from 110.60 MiB to 1,521.13
MiB. Streamed download improved throughput by 9.45 percent and reduced CPU by
9.64 percent, while p99 rose 4.54 percent and RSS rose 21.06 percent.

The cross-mode result supports retaining easy-handle reuse. It does not
compare per-route FIFO storage with a global or otherwise simplified
easy-handle pool, so the pool topology remains a separate design question.

### Connection Limit Selection

A local matrix offered 500 concurrent two MiB transfers while varying the
admission limit from 50 to 500. Limit 50 led the local fixture. Live S3 then
tested 50, 100, 250, and 450 at offered concurrency 450. A ten-run
confirmation compared 50 with 100.

| Direction | Limit | Gbps | p50 | p99 | Mean RSS | Peak RSS | PHP peak |
|---|---:|---:|---:|---:|---:|---:|---:|
| Download | 50 | 2.401 | 338.980 ms | 430.781 ms | 88.06 MiB | 91.90 MiB | 25.80 MiB |
| Download | 100 | 2.750 | 283.094 ms | 409.716 ms | 102.07 MiB | 105.96 MiB | 31.40 MiB |
| Upload | 50 | 1.531 | 430.781 ms | 611.050 ms | 192.51 MiB | 206.96 MiB | 144.00 MiB |
| Upload | 100 | 1.960 | 301.552 ms | 537.483 ms | 207.31 MiB | 219.27 MiB | 144.00 MiB |

Limit 100 won throughput and p50 in all 20 paired comparisons. It improved
download throughput by 14.55 percent and upload throughput by 28.04 percent.
Download p99 improved by 4.89 percent and upload p99 improved by 12.04
percent. Peak RSS increased by 14.06 MiB for downloads and 12.31 MiB for
uploads. All 40,000 operations completed without transfer, signing, or
cleanup failures. The endpoint negotiated HTTP/1.1 throughout.

The live-endpoint gains outweighed the measured memory cost, so the connection
manager defaults to 100 for both `maxConcurrency` and `maxIdlePerHost`.
`maxIdleTotal` remains 100. Memory-sensitive or low-latency workloads can
configure a lower admission and retention limit.

### DNS Ownership

The live S3 comparison alternated libcurl DNS and an injected Amp resolver
over ten runs per direction. Each case performed 1,000 validated 256 KiB
operations at concurrency 100. The endpoint negotiated HTTP/1.1.

| Direction | Amp throughput | Amp p50 | Amp p99 | Amp peak RSS |
|---|---:|---:|---:|---:|
| Download | -2.24% | +0.62% | +3.07% | +2.01 MiB |
| Upload | +5.04% | +3.62% | -1.32% | +4.53 MiB |

Values are median paired changes except peak RSS, which is the mean paired
difference. Amp won download throughput in two of ten pairs and upload
throughput in seven of ten. Amp changed median paired throughput by -2.24
percent for downloads and +5.04 percent for uploads. It increased p50 latency
and peak RSS in both directions.

The retained transport delegates hostname resolution, caching, address
selection, and connection-stage failover to libcurl. Removing the optional
resolver also removes address pinning and the separate DNS retry path from
the PHP transport.

### Adaptive Productive Polling

Two five-run comparisons tested adaptive polling against the preceding
low-water control. The table combines the ten Smithy runs.

| Mode | Throughput | CPU | p50 | p99 | RSS |
|---|---:|---:|---:|---:|---:|
| Buffered download | -0.59% | +0.68% | +0.95% | +0.54% | +5.02% |
| Streamed download | +0.36% | +0.07% | -1.20% | -0.98% | +1.26% |
| Streamed upload | +3.71% | +0.67% | -3.81% | -7.04% | +0.06% |

Streamed upload improved in both five-run datasets. The combined result
raised throughput by 3.71 percent and reduced p99 latency by 7.04 percent.
Download throughput and CPU stayed within 1 percent of the baseline. These
results support retaining adaptive polling as a request-streaming
optimization.

### Single-Chunk Response Reads

Two five-run comparisons tested returning one queued chunk per `read()`
against adaptive polling. The change removes cross-chunk concatenation and
can return a complete chunk without copying.

| Variant | Throughput | CPU | p50 | p99 | RSS |
|---|---:|---:|---:|---:|---:|
| Adaptive polling | 5.842 Gbps | 2.1543 s | 140.423 ms | 188.195 ms | 55.90 MiB |
| Single-chunk reads | 6.059 Gbps | 2.0668 s | 135.200 ms | 181.248 ms | 52.02 MiB |

The single-chunk datasets recorded 3.70 percent higher throughput, 4.06
percent lower CPU, and 3.69 percent lower p99 latency. Both independent runs
differed by 0.13 percent in Smithy throughput. Cross-client movement between
the earlier and later datasets means the exact gain is host-specific, but
the repeatability and removal of response copies support retaining the
behavior.

### Armed Response Notifications

Five runs per client tested arming the response space-available callback only
after backpressure.

| Variant | Throughput | CPU | p50 | p99 | RSS |
|---|---:|---:|---:|---:|---:|
| Single-chunk confirmation | 6.067 Gbps | 2.0522 s | 135.293 ms | 178.139 ms | 52.15 MiB |
| Armed notifications | 6.088 Gbps | 2.0333 s | 135.112 ms | 177.238 ms | 52.28 MiB |

Arming notifications increased throughput by 0.35 percent and reduced CPU by
0.92 percent relative to the immediately preceding confirmation run.
Throughput and CPU remained within 1 percent while the implementation removed
closure invocation, resume scheduling, and pending-transfer lookup from
ordinary streamed reads. The implementation therefore retains notification
arming.

### Response Queue Representation

Five streamed-download runs per client compared `SplQueue` with an indexed
PHP array using 1,000 two MiB responses, concurrency 50, and 50 warmups.

| Queue | Throughput | CPU | p50 | p99 | RSS |
|---|---:|---:|---:|---:|---:|
| `SplQueue` | 6.088 Gbps | 2.0333 s | 135.112 ms | 177.238 ms | 52.28 MiB |
| Indexed array | 5.960 Gbps | 2.1017 s | 136.586 ms | 201.832 ms | 52.43 MiB |

The indexed array recorded 2.10 percent lower Smithy throughput, 3.36 percent
higher CPU, 1.09 percent higher p50, and 13.88 percent higher p99. Throughput
also fell for Symfony, ReactPHP, and Amp by 8.86, 4.21, and 2.35 percent
between the datasets, so the exact throughput change cannot be isolated from
host-wide movement. The experiment recorded a regression in every measured
Smithy metric, and the implementation retained `SplQueue`.

## Configuration Matrices

The matrix work varied concurrency across 50, 150, 300, 450, and 550. Request
and response high water marks were tested at 64, 128, 256, 512, and 1,024
KiB. Upload, download, and duplex paths were measured independently because
their bottlenecks and memory costs differ.

The benchmark retained concurrency 450. Runs at 550 held 100 additional
requests in flight without a repeatable throughput increase. Client defaults
remain lower because a library default must fit hosts with less memory and
narrower network capacity.

The 64 KiB high water mark became the request and response default. Larger
queues did not provide a repeatable throughput gain across the matrix, and
their memory cost scales with active streams. At concurrency 450, each extra
64 KiB of queued data permits up to 28.1 MiB of additional data per direction.
The high water mark bounds queued data rather than the complete memory cost
of an exchange.

Streamed responses resume production after the queued body drains to half of
the configured high water mark and the retained libcurl chunk can fit. This
low-water hysteresis retains the same memory bound while reducing receive
pause and resume frequency.

Five 1,000-request runs compared 64, 128, and 256 KiB libcurl upload buffers
with two MiB resource uploads at concurrency 50.

| Upload buffer | Throughput | CPU | p50 | p99 | RSS |
|---:|---:|---:|---:|---:|---:|
| 64 KiB | 11.848 Gbps | 0.5344 s | 69.575 ms | 82.833 ms | 46.99 MiB |
| 128 KiB | 12.283 Gbps | 0.4624 s | 66.733 ms | 85.234 ms | 50.15 MiB |
| 256 KiB | 12.542 Gbps | 0.4301 s | 65.291 ms | 90.345 ms | 56.43 MiB |

The 128 KiB setting improved throughput by 3.67 percent and reduced CPU by
13.47 percent relative to 64 KiB. Its average p99 increase came from one run,
while the median run p99 remained within 0.2 percent of the 64 KiB result.
The 256 KiB setting added another 2.11 percent throughput but increased
memory and showed a broader tail-latency regression. These results support
128 KiB as the default and retain larger values as explicit tuning.

The low-water comparison used a 64 KiB response buffer, 16 KiB server chunks,
concurrency 450, and a 0.05 ms delay after server writes and consumer reads.
Smaller reads crossed the 32 KiB resume threshold incrementally.

| Consumer read | Throughput | p50 | p99 | Peak RSS |
|---:|---:|---:|---:|---:|
| 64 KiB | 4.245 Gbps | 1,821.8 ms | 2,042.2 ms | 157.37 MiB |
| 16 KiB | 3.870 Gbps | 2,007.1 ms | 2,048.8 ms | 141.96 MiB |
| 8 KiB | 3.389 Gbps | 2,256.1 ms | 2,651.9 ms | 137.77 MiB |

All nine runs completed 9,000 two MiB downloads without failures or resource
growth. The 16 KiB case kept p99 within 0.3 percent of the 64 KiB control.
The 8 KiB case performed more reads and timer operations, reducing throughput
and increasing tail latency.

One warmed run at concurrency 450 transferred 1,000 two MiB downloads at
4.813 Gbps. Its peak RSS was 161.89 MiB, file descriptors remained at nine,
and all 450 warmed connections returned to the idle pool. The corresponding
duplex run transferred two MiB in each direction for 1,000 exchanges at
5.136 Gbps. Peak RSS was 200.82 MiB, active connections returned to zero,
and file descriptors again remained at nine. These values are evidence for
that host and fixture configuration rather than portable throughput targets.

## Live HTTP/2 Validation

The live HTTP/2 run used PHP 8.5.9, curl 8.17.0, and
`https://nghttp2.org/httpbin`. It tested automatic negotiation and enforced
HTTP/2. Each configuration ran 20 buffered downloads, 20 streamed downloads,
and 20 streamed uploads at concurrency 10 with 32 KiB payloads.

| Policy | Completed | Failed | Negotiated HTTP/2 | Active after run | Idle before clear |
|---|---:|---:|---:|---:|---:|
| Automatic | 60 | 0 | 60 | 0 | 10 |
| Enforced HTTP/2 | 60 | 0 | 60 | 0 | 10 |

Both configurations returned to nine file descriptors after shutdown. This
run validates negotiation and the three body modes over a public HTTP/2
endpoint. Its low operation count does not measure sustained multiplexing or
fault recovery.

## Live S3 Validation

Live S3 tests cover large transfers, ranged reads, concurrency, cancellation,
asynchronous producers, and seekable resources.

A recording client verifies SigV4 authorization and
`x-amz-content-sha256`. Resource uploads assert the exact SHA-256.
Non-seekable HTTPS uploads assert `UNSIGNED-PAYLOAD`.

The x86 run used PHP 8.5.8, automatic HTTP negotiation, and 64 KiB request and
response buffers. S3 negotiated HTTP/1.1. It completed 52,082 operations with
zero unexpected failures, 10 expected cancellations, 10 health checks, and
1,060 range checks.

| Case | Operations | Transfer per operation | Concurrency | Throughput | p99 | Peak RSS |
|---|---:|---:|---:|---:|---:|---:|
| Large download | 4 | 64 MiB | 4 | 1.971 Gbps | 1,089 ms | 61.00 MiB |
| Large asynchronous upload | 4 | 64 MiB | 4 | 0.789 Gbps | 2,679 ms | 61.91 MiB |
| Large resource upload | 4 | 64 MiB | 4 | 0.711 Gbps | 2,885 ms | 61.80 MiB |
| Concurrent upload | 1,000 | 2 MiB | 450 | 5.072 Gbps | 1,753 ms | 222.74 MiB |
| Concurrent ranged read | 1,000 | 2 MiB range | 450 | 2.752 Gbps | 3,331 ms | 206.56 MiB |
| Mixed soak | 50,000 | 2 MiB | 450 | 5.546 Gbps | 1,909 ms | 244.64 MiB |

The resource upload recorded four exact payload hashes and zero unsigned
payloads. The concurrent asynchronous upload recorded 1,000 unsigned
payloads, as required for non-seekable S3 HTTPS bodies.

Three isolated concurrent-upload repeats completed 1,000 two MiB uploads
each at 4.941, 5.526, and 5.527 Gbps. The median was 5.526 Gbps, and the last
two results differed by 0.013 percent. All 3,000 uploads passed signing and
payload checks, retained 450 idle easy handles, and finished without file
descriptor growth.

The current full-sequence upload result of 5.072 Gbps is 8.9 percent below
the isolated median. This replaces the earlier full-sequence result of 3.783
Gbps, whose slowdown did not reproduce.

## Soak Testing

The first 10,000-operation S3 soak exposed an accounting defect. The recorder
observed all 5,000 GET responses, but the aggregate result omitted 427
downloads. The result retained 23 idle easy handles from a 450-handle pool,
which matched the 427 missing callback results.

Follow-up lifecycle testing reproduced missing `Async::map()` entries.
`RevoltEventLoop::run()` queued callbacks without creating one explicit fiber
per callback. Fast lifecycle operations could re-enter the shared execution
flow while other callbacks were suspended. The event loop now creates an
explicit fiber for each callback. `Async::map()` accumulates results inside
each worker before merging them in input order.

The replacement local mixed run completed 100,000 operations at concurrency
450 with zero failures. It
accounted for all 33,334 uploads, 33,333 downloads, and 33,333 duplex
exchanges. Uploaded and downloaded byte totals matched those action counts
exactly. Throughput was 5.504 Gbps over 406.413 seconds.

Each 10,000-operation mixed batch took between 38.106 and 41.702 seconds.
Batch-end RSS reached 156.21 MiB by the fourth batch and stayed within 0.07
MiB of that value through the tenth batch. Every batch finished with zero
active easy handles, 450 idle easy handles, and 462 open descriptors. The
process returned to nine descriptors after shutdown.

The lifecycle run completed 100,000 operations. It accounted for 44,444
expected failures and reported zero unexpected failures. Batch-end RSS
remained between 81.88 and 84.24 MiB. Every batch ended with zero active easy
handles, and the process returned to nine descriptors.

The replacement S3 soak completed 50,000 operations at concurrency 450 in
151.251 seconds. It recorded 25,000 uploads and 25,000 downloads with exact
52,428,800,000-byte totals in each direction. All 50,001 requests, including
the prepared fixture upload, carried SigV4 authorization and passed payload
hash policy checks. Every batch ended with zero active easy handles and 450
idle easy handles.

S3 batch duration stayed between 30.057 and 30.852 seconds. Batch-end RSS
rose from 216.15 MiB after the first batch to 220.77 MiB after the second,
then reached 221.64 MiB after the fifth. File descriptors stayed between 461
and 463 during the run and returned to nine afterward. These replacement
runs close the result-retention and long-soak validation work.

The long runs exercised 250,000 operations across local mixed traffic,
disruptive lifecycle behavior, and live S3.

| Workload | Operations | Concurrency | Elapsed | Throughput | p99 | Peak RSS | Failures |
|---|---:|---:|---:|---:|---:|---:|---:|
| Local mixed | 100,000 | 450 | 406.4 s | 5.504 Gbps | 2,943 ms | 184.83 MiB | 0 |
| Local lifecycle | 100,000 | 100 | 311.9 s | 1.793 Gbps | 906 ms | 92.89 MiB | 0 unexpected |
| S3 mixed | 50,000 | 450 | 151.3 s | 5.546 Gbps | 1,909 ms | 244.64 MiB | 0 |

Lifecycle throughput counts bytes from successful response paths. Its 44,444
modeled transfer failures are expected outcomes and are excluded from the
unexpected failure column.

Batch duration and resource counts remained bounded throughout each run.
The lifecycle pool closes connections after disruptive cases, so its idle
and descriptor counts vary by batch.

| Workload | Batch duration | Batch RSS first/final | Batch-end FDs | Idle connections | Active connections |
|---|---:|---:|---:|---:|---:|
| Local mixed | 38.11-41.70 s | 146.75 / 156.23 MiB | 462 | 450 | 0 |
| Local lifecycle | 30.77-32.00 s | 82.00 / 82.25 MiB | 42-61 | 41-56 | 0 |
| S3 mixed | 30.06-30.85 s | 216.15 / 221.64 MiB | 461-463 | 450 | 0 |

Long runs aggregate bounded latency and RSS samples after each batch.
Accounting checks compare operation, byte, cancellation, range, signing, and
connection totals.

## Profiling and Serialization Benchmarks

Profiling isolates request-construction costs before network latency.
Duplicate normalization of protocol-produced headers consumed 54 percent of
`restXml_CopyObjectRequest_M` samples and raised median serialization latency
from 5.541 to 13.458 microseconds. Direct normalized-header assignment
restored the baseline.

External headers require validation and normalization. Protocol-produced
headers retain their normalized representation through request construction.

### Message Representation Evaluation

Three x86 no-network comparisons evaluated whether an internal mutable
request model should replace immutable PSR-7 requests. Every comparison used
PHP 8.5.9 with OPcache and JIT enabled and alternated implementation order
over ten runs.

The synthetic comparison applied signing mutations plus URI, body, and
endpoint-header changes. Each case ran 100,000 operations after 10,000
warmups. The mutable prototype created one immutable request at the final
boundary.

| Scenario | Headers | Throughput | p50 | p99 | CPU | Peak memory |
|---|---:|---:|---:|---:|---:|---:|
| Signing | 5 | +21.49% | -17.85% | -17.53% | -17.69% | -22.24% |
| Signing | 15 | +18.64% | -15.89% | -14.90% | -15.71% | -21.53% |
| Signing | 30 | +18.49% | -15.80% | -14.51% | -15.60% | -22.86% |
| Full | 5 | +29.56% | -23.03% | -20.24% | -22.82% | -21.04% |
| Full | 15 | +27.57% | -21.81% | -23.47% | -21.61% | -20.83% |
| Full | 30 | +25.68% | -20.77% | -19.17% | -20.43% | -22.41% |

This comparison isolated request clones and header-array copies.

The production comparison used S3 `PutObject` REST XML serialization,
endpoint rules, checksums, SigV4, and transport conversion. Each case ran
20,000 operations after 2,000 warmups with a 1,024-byte body and zero, ten, or
thirty metadata headers. Final requests were identical.

| Metadata headers | Full throughput | p50 | p99 | CPU | Retained memory | Peak memory |
|---:|---:|---:|---:|---:|---:|---:|
| 0 | -2.33% | +2.42% | +2.01% | +2.38% | -3.95% | -9.83% |
| 10 | -1.46% | +1.29% | +1.00% | +1.48% | -4.49% | -11.86% |
| 30 | -0.77% | +0.61% | +2.05% | +0.77% | -9.27% | -9.47% |

Mutable throughput lost 28 of 30 paired cases. Retained memory fell by 3.95
to 9.27 percent, and peak memory fell by 9.47 to 11.86 percent.

The boundary comparison passed signed requests through the production PSR-7
converter 500,000 times after 50,000 warmups. Immutable controls passed
through unchanged.

| Metadata headers | Immutable p50 | Mutable conversion p50 | Added time | Added transient peak |
|---:|---:|---:|---:|---:|
| 0 | 252 ns | 799 ns | 548 ns | 160 bytes |
| 10 | 410 ns | 998 ns | 588 ns | 160 bytes |
| 30 | 736 ns | 1,388 ns | 652 ns | 160 bytes |

The conversion cost exceeded the complete pipeline's 219 to 401 nanosecond
p50 regression. Native mutable processing therefore saved an estimated 147
to 434 nanoseconds, or 0.89 to 1.21 percent of no-network pipeline time.

The retained design therefore uses immutable PSR-7 `Request` and `Response`
objects throughout the runtime. It keeps normalized-header tuples across
trusted stages and uses normalized construction and mutation APIs to avoid
repeated PSR-7 header normalization. Mutable request and response contracts,
implementations, and final conversion are not retained.

The post-revert comparison repeated the immutable S3 pipeline.

| Metadata headers | Full throughput | p50 | p99 | CPU | Retained memory | Peak memory |
|---:|---:|---:|---:|---:|---:|---:|
| 0 | +7.51% | -8.21% | -5.94% | -6.98% | -2.42% | +5.38% |
| 10 | +5.82% | -6.35% | -4.17% | -5.50% | -1.30% | +3.12% |
| 30 | +5.69% | -5.91% | -4.72% | -5.39% | -0.64% | +1.63% |

The retained-memory result improved in all thirty paired cases. Peak memory
increased by 576 bytes per request at each metadata size.

The post-revert HTTP comparison repeated all five Smithy modes.

| Mode | Throughput | p50 | p99 | CPU | RSS | Wall |
|---|---:|---:|---:|---:|---:|---:|
| Adaptive download | +2.82% | -2.49% | -0.52% | -2.32% | -0.65% | -2.73% |
| Buffered download | +2.46% | -3.04% | -1.62% | -2.25% | -1.26% | -2.42% |
| Buffered upload | +1.34% | -1.73% | -4.53% | -1.47% | +0.40% | -1.23% |
| Streamed download | +1.25% | -1.55% | -2.12% | -2.86% | -0.32% | -1.25% |
| Streamed upload | +2.44% | -2.63% | -2.48% | -3.67% | -0.01% | -2.38% |

All 50,000 transfers passed byte-count and SHA-256 validation. PHP peak
memory was unchanged for Adaptive download, buffered upload, streamed
download, and streamed upload. Buffered-download PHP peak memory fell from
110.4 to 110.0 MiB.

## Interpretation and Remaining Coverage

Passing the focused suites is required before stress work because stress
throughput cannot prove state-machine correctness. Local stress must then
complete with exact payload validation, zero unexpected failures, stable
file descriptors, and zero active connections after the run. Live S3 adds
signing, service protocol, TLS, and production network behavior.

The current evidence covers standard and streamed requests and responses,
bounded backpressure, cancellation, connection reuse, response trailers,
stream failures, lifecycle cleanup, and a low-volume live HTTP/2 path. Curl
request trailers remain rejected because PHP does not expose libcurl's
trailer callback. Modeled `aws-chunked` request checksum trailers are
deferred. The live HTTP/2 run does not replace a controlled fixture for
multiplexing, connection loss, and stream reset behavior.

Validation records retain configuration, environment metadata, latency
distributions, resource counters, and validation totals.
