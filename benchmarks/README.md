# Benchmarks

This directory contains repeatable performance benchmarks for smithy-php.
The suites measure static RPC v2 CBOR request serialization, response
deserialization, and generated-client pipeline execution without network I/O.
Serde cases use the same test case IDs and result schema as smithy-java.

## Requirements

- PHP 8.2 or later
- Composer dependencies installed with `composer install`
- GNU Make for the convenience targets below

The suite uses the slim, CBOR-only model at
`serde/model/rpcv2-cbor.json`. Generated PHP is written under
`build/benchmarks/` and is not committed.

## Usage

Generate the static benchmark shapes:

```shell
make benchmark-generate
```

Run all benchmarks:

```shell
make benchmark
```

Run only the serde benchmark:

```shell
make benchmark-serde
```

Run only the generated-client pipeline benchmark:

```shell
make benchmark-client
```

Run the benchmark and replace the current results section in this file:

```shell
make benchmark-update-results
```

Remove generated benchmark files and raw results:

```shell
make benchmark-clean
```

The Make targets enable CLI OPcache and the tracing JIT with a 128 MiB
buffer. Override `BENCHMARK_PHP` to use different runtime flags.

The scripts can also be invoked directly on systems without Make:

```shell
php benchmarks/serde/generate.php
php -d opcache.enable_cli=1 -d opcache.jit=tracing \
    -d opcache.jit_buffer_size=128M benchmarks/serde/run.php
php -d opcache.enable_cli=1 -d opcache.jit=tracing \
    -d opcache.jit_buffer_size=128M benchmarks/client/run.php
php benchmarks/serde/generate.php --clean
```

Use either runner's `--help` option for filtering and iteration controls.
Raw result files are written to `build/benchmarks/results/`. Set
`SMITHY_BENCHMARK_INSTANCE` to record a stable machine or instance label.

## Methodology

- Inputs, operation metadata, protocol objects, and response fixtures are
  prepared before timing.
- Client pipeline cases invoke a generated client with a reusable in-memory
  HTTP response. They include endpoint resolution, anonymous auth selection,
  serialization, deserialization, transport dispatch, and optional interceptor
  dispatch, but do not include signing, retries, socket I/O, or TLS.
- The `default` client case disables all plugins and measures the
  interceptor-free pipeline. The `interceptor` case enables only the
  pass-through benchmark interceptor.
- Runtime code generation is not enabled.
- Each case receives a smoke validation before measurement. Serialization
  checks request metadata and decodes the emitted CBOR back into the input
  shape. Deserialization checks the output type and compares values when
  the fixture supplies expected parameters. These checks do not replace the
  protocol conformance suite.
- Warmup calls execute before samples are collected.
- Latencies are recorded in nanoseconds, and throughput is normalized using
  process CPU time when `getrusage()` is available.
- Raw serde results use the shared `metadata` and `serde_benchmarks` schema
  used by smithy-java. PHP, OPcache, JIT, OS, processor, revision, and runner
  settings are captured with each run.
- Results are descriptive only; the suite does not enforce performance
  thresholds in CI.

## Current results

<!-- benchmark-results:start -->
### Serde

Recorded: 2026-11-01T22:25:28+00:00

- Revision: `155d9b7`
- PHP: `8.2.35 (cli)`
- OS: `Darwin 25.6.0 arm64`
- Instance: `Apple M1 Pro`
- OPcache: `enabled`
- JIT: `enabled`
- JIT setting: `tracing`
- JIT buffer: `128M`

| Direction | Case | n | Mean | p50 | p90 | p95 | p99 | Std dev | Ops/CPU s |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| serialize | `rpcv2Cbor_WideTypesRequest_S` | 5,000 | 7.719 µs | 7.625 µs | 7.875 µs | 7.959 µs | 8.292 µs | 2.721 µs | 128,284 |
| serialize | `rpcv2Cbor_WideTypesRequest_M` | 5,000 | 13.523 µs | 13.291 µs | 13.916 µs | 14.250 µs | 15.875 µs | 1.822 µs | 73,920 |
| serialize | `rpcv2Cbor_WideTypesRequest_L` | 5,000 | 26.076 µs | 25.792 µs | 26.541 µs | 27.167 µs | 30.583 µs | 2.028 µs | 38,351 |
| serialize | `rpcv2Cbor_PutItemRequest_Baseline` | 5,000 | 1.633 µs | 1.625 µs | 1.667 µs | 1.708 µs | 1.750 µs | 719 ns | 604,668 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_S` | 5,000 | 3.818 µs | 3.709 µs | 3.916 µs | 3.959 µs | 4.250 µs | 1.979 µs | 260,838 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_M` | 5,000 | 16.619 µs | 16.417 µs | 17.083 µs | 17.459 µs | 18.250 µs | 1.623 µs | 60,140 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_L` | 5,000 | 48.507 µs | 47.917 µs | 50.416 µs | 51.000 µs | 53.375 µs | 3.171 µs | 20,626 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_M` | 5,000 | 3.621 µs | 3.458 µs | 3.667 µs | 3.750 µs | 4.000 µs | 4.215 µs | 279,376 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_L` | 5,000 | 10.816 µs | 10.750 µs | 11.291 µs | 11.375 µs | 11.709 µs | 1.152 µs | 92,283 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_S` | 5,000 | 4.736 µs | 4.666 µs | 4.792 µs | 4.875 µs | 5.084 µs | 1.586 µs | 210,084 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_M` | 5,000 | 16.751 µs | 16.500 µs | 17.000 µs | 17.209 µs | 18.459 µs | 2.848 µs | 59,680 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_L` | 5,000 | 45.619 µs | 45.417 µs | 46.958 µs | 47.542 µs | 61.875 µs | 4.856 µs | 21,957 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_S` | 5,000 | 2.168 µs | 2.000 µs | 2.083 µs | 2.125 µs | 2.167 µs | 6.810 µs | 482,346 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_M` | 5,000 | 4.936 µs | 4.875 µs | 5.042 µs | 5.125 µs | 5.334 µs | 1.575 µs | 202,216 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_L` | 5,000 | 16.029 µs | 15.708 µs | 16.917 µs | 17.292 µs | 21.917 µs | 2.705 µs | 62,509 |
| deserialize | `rpcv2Cbor_WideTypesResponse_S` | 5,000 | 11.811 µs | 10.917 µs | 11.625 µs | 11.875 µs | 18.042 µs | 10.180 µs | 84,704 |
| deserialize | `rpcv2Cbor_WideTypesResponse_M` | 5,000 | 18.748 µs | 18.541 µs | 19.083 µs | 19.458 µs | 21.125 µs | 2.398 µs | 53,302 |
| deserialize | `rpcv2Cbor_WideTypesResponse_L` | 5,000 | 35.388 µs | 34.542 µs | 36.750 µs | 37.292 µs | 52.667 µs | 4.727 µs | 28,331 |
| deserialize | `rpcv2Cbor_GetItemOutput_Baseline` | 5,000 | 1.924 µs | 1.792 µs | 1.916 µs | 1.958 µs | 2.167 µs | 2.350 µs | 514,774 |
| deserialize | `rpcv2Cbor_GetItemOutput_S` | 5,000 | 5.288 µs | 5.083 µs | 5.375 µs | 5.459 µs | 7.000 µs | 2.713 µs | 189,977 |
| deserialize | `rpcv2Cbor_GetItemOutput_M` | 5,000 | 15.205 µs | 14.750 µs | 15.625 µs | 15.875 µs | 23.292 µs | 3.626 µs | 65,973 |
| deserialize | `rpcv2Cbor_GetItemOutput_L` | 5,000 | 95.886 µs | 90.750 µs | 96.250 µs | 99.084 µs | 140.000 µs | 103.230 µs | 10,751 |
| deserialize | `rpcv2Cbor_GetItemOutput_OutOfOrder` | 5,000 | 18.191 µs | 17.750 µs | 18.834 µs | 19.084 µs | 24.541 µs | 2.455 µs | 55,066 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_S` | 5,000 | 5.429 µs | 5.292 µs | 5.583 µs | 5.667 µs | 5.958 µs | 1.941 µs | 184,257 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_M` | 5,000 | 15.478 µs | 15.167 µs | 16.083 µs | 16.292 µs | 17.875 µs | 2.260 µs | 64,602 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_L` | 5,000 | 92.752 µs | 91.375 µs | 96.625 µs | 97.333 µs | 100.209 µs | 3.580 µs | 10,782 |
<!-- benchmark-results:end -->

### Generated client pipeline

`default` uses the interceptor-free generated-client fast path. `interceptor`
installs only a pass-through plugin and interceptor to measure dispatch.

<!-- client-benchmark-results:start -->
Recorded: 2026-11-01T22:25:30+00:00

- Revision: `155d9b7`
- PHP: `8.2.35 (cli)`
- OS: `Darwin 25.6.0 arm64`
- Instance: `Apple M1 Pro`
- OPcache: `enabled`
- JIT: `enabled`
- JIT setting: `tracing`
- JIT buffer: `128M`

| Case | n | Mean | p50 | p90 | p95 | p99 | Std dev | Ops/CPU s |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| `rpcv2Cbor_ClientPipeline_default_S` | 5,000 | 24.924 µs | 24.375 µs | 25.125 µs | 25.792 µs | 44.250 µs | 7.737 µs | 40,156 |
| `rpcv2Cbor_ClientPipeline_default_M` | 5,000 | 38.371 µs | 36.792 µs | 39.458 µs | 42.333 µs | 75.875 µs | 8.272 µs | 26,153 |
| `rpcv2Cbor_ClientPipeline_default_L` | 5,000 | 66.652 µs | 64.292 µs | 69.416 µs | 72.792 µs | 107.167 µs | 8.638 µs | 15,033 |
| `rpcv2Cbor_ClientPipeline_interceptor_S` | 5,000 | 28.228 µs | 27.167 µs | 28.000 µs | 29.875 µs | 60.791 µs | 10.307 µs | 35,573 |
| `rpcv2Cbor_ClientPipeline_interceptor_M` | 5,000 | 43.180 µs | 41.125 µs | 44.250 µs | 50.500 µs | 91.292 µs | 11.315 µs | 23,277 |
| `rpcv2Cbor_ClientPipeline_interceptor_L` | 5,000 | 73.380 µs | 70.375 µs | 76.334 µs | 86.750 µs | 165.583 µs | 17.711 µs | 13,704 |
<!-- client-benchmark-results:end -->
