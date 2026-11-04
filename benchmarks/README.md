# Benchmarks

This directory contains repeatable performance benchmarks for smithy-php.
The suites measure RPC v2 CBOR and RPC v2 JSON request serialization and
response deserialization, plus RPC v2 CBOR generated-client pipeline
execution without network I/O. Serde cases use the shared Smithy Java
workload and result schema.

## Requirements

- PHP 8.2 or later
- Composer dependencies installed with `composer install`
- GNU Make for the convenience targets below

The serde suite uses the slim models at `serde/model/rpcv2-cbor.json` and
`serde/model/rpcv2-json.json`. Both contain the same small, medium, and large
workloads. Generated PHP is written under `build/benchmarks/` and is not
committed.

The RPC v2 JSON model mirrors the RPC v2 CBOR case list and parameters, with
protocol-specific JSON response bodies.

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

Run only one RPC v2 protocol:

```shell
php benchmarks/serde/run.php --protocol=rpcv2-cbor
php benchmarks/serde/run.php --protocol=rpcv2-json
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
  checks request metadata and decodes the emitted payload back into the input
  shape. Deserialization checks the output type and compares values when the
  fixture supplies expected parameters. These checks do not replace the
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

Recorded: 2026-11-04T15:27:32+00:00

- Revision: `71b9898`
- PHP: `8.2.35 (cli)`
- OS: `Darwin 25.6.0 arm64`
- Instance: `Apple M1 Pro`
- OPcache: `enabled`
- JIT: `enabled`
- JIT setting: `tracing`
- JIT buffer: `128M`

| Direction | Case | n | Mean | p50 | p90 | p95 | p99 | Std dev | Ops/CPU s |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| serialize | `rpcv2Cbor_WideTypesRequest_S` | 5,000 | 7.753 µs | 7.459 µs | 8.000 µs | 8.167 µs | 10.416 µs | 3.433 µs | 128,182 |
| serialize | `rpcv2Cbor_WideTypesRequest_M` | 5,000 | 13.911 µs | 13.417 µs | 14.208 µs | 14.833 µs | 29.875 µs | 3.871 µs | 72,236 |
| serialize | `rpcv2Cbor_WideTypesRequest_L` | 5,000 | 27.041 µs | 26.125 µs | 27.625 µs | 29.166 µs | 49.750 µs | 6.342 µs | 37,147 |
| serialize | `rpcv2Cbor_PutItemRequest_Baseline` | 5,000 | 1.656 µs | 1.625 µs | 1.709 µs | 1.750 µs | 1.834 µs | 718 ns | 592,698 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_S` | 5,000 | 3.750 µs | 3.709 µs | 3.834 µs | 3.917 µs | 4.083 µs | 692 ns | 264,816 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_M` | 5,000 | 16.906 µs | 16.709 µs | 17.416 µs | 17.625 µs | 21.375 µs | 2.023 µs | 59,154 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_L` | 5,000 | 49.451 µs | 48.458 µs | 50.500 µs | 51.333 µs | 77.916 µs | 8.511 µs | 20,350 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_M` | 5,000 | 3.715 µs | 3.666 µs | 3.792 µs | 3.875 µs | 4.042 µs | 1.454 µs | 265,477 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_L` | 5,000 | 11.925 µs | 11.417 µs | 11.750 µs | 11.917 µs | 24.084 µs | 7.173 µs | 85,025 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_S` | 5,000 | 4.803 µs | 4.709 µs | 4.916 µs | 4.959 µs | 5.209 µs | 1.704 µs | 207,693 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_M` | 5,000 | 16.828 µs | 16.333 µs | 17.041 µs | 17.250 µs | 30.375 µs | 4.000 µs | 59,731 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_L` | 5,000 | 47.461 µs | 46.250 µs | 48.250 µs | 49.542 µs | 84.459 µs | 8.440 µs | 21,185 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_S` | 5,000 | 2.022 µs | 2.000 µs | 2.083 µs | 2.125 µs | 2.208 µs | 914 ns | 488,043 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_M` | 5,000 | 4.973 µs | 4.875 µs | 5.042 µs | 5.125 µs | 5.416 µs | 1.939 µs | 201,418 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_L` | 5,000 | 16.825 µs | 16.500 µs | 17.667 µs | 18.125 µs | 22.000 µs | 3.550 µs | 59,595 |
| deserialize | `rpcv2Cbor_WideTypesResponse_S` | 5,000 | 11.133 µs | 10.333 µs | 11.042 µs | 11.250 µs | 20.792 µs | 9.513 µs | 90,105 |
| deserialize | `rpcv2Cbor_WideTypesResponse_M` | 5,000 | 19.253 µs | 18.500 µs | 19.292 µs | 19.541 µs | 29.750 µs | 15.202 µs | 52,753 |
| deserialize | `rpcv2Cbor_WideTypesResponse_L` | 5,000 | 74.877 µs | 35.792 µs | 42.417 µs | 92.291 µs | 461.584 µs | 485.914 µs | 24,640 |
| deserialize | `rpcv2Cbor_GetItemOutput_Baseline` | 5,000 | 2.053 µs | 1.875 µs | 1.959 µs | 2.000 µs | 2.125 µs | 3.385 µs | 482,672 |
| deserialize | `rpcv2Cbor_GetItemOutput_S` | 5,000 | 5.542 µs | 5.375 µs | 5.541 µs | 5.583 µs | 5.667 µs | 5.105 µs | 181,666 |
| deserialize | `rpcv2Cbor_GetItemOutput_M` | 5,000 | 15.847 µs | 15.625 µs | 15.959 µs | 16.125 µs | 20.417 µs | 3.343 µs | 63,110 |
| deserialize | `rpcv2Cbor_GetItemOutput_L` | 5,000 | 96.060 µs | 94.209 µs | 98.208 µs | 108.083 µs | 165.500 µs | 12.668 µs | 10,459 |
| deserialize | `rpcv2Cbor_GetItemOutput_OutOfOrder` | 5,000 | 18.481 µs | 17.833 µs | 19.000 µs | 19.250 µs | 29.791 µs | 4.640 µs | 54,263 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_S` | 5,000 | 5.366 µs | 5.209 µs | 5.334 µs | 5.417 µs | 6.000 µs | 2.216 µs | 186,178 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_M` | 5,000 | 15.611 µs | 15.167 µs | 16.041 µs | 16.333 µs | 20.125 µs | 3.808 µs | 64,247 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_L` | 5,000 | 96.850 µs | 96.084 µs | 99.834 µs | 107.292 µs | 144.000 µs | 9.940 µs | 10,368 |
| serialize | `rpcv2Json_WideTypesRequest_S` | 5,000 | 6.523 µs | 6.208 µs | 6.583 µs | 6.667 µs | 7.250 µs | 5.447 µs | 152,742 |
| serialize | `rpcv2Json_WideTypesRequest_M` | 5,000 | 11.047 µs | 10.667 µs | 11.250 µs | 11.375 µs | 13.833 µs | 4.480 µs | 90,942 |
| serialize | `rpcv2Json_WideTypesRequest_L` | 5,000 | 22.243 µs | 20.750 µs | 21.667 µs | 22.208 µs | 68.042 µs | 12.704 µs | 46,292 |
| serialize | `rpcv2Json_PutItemRequest_Baseline` | 5,000 | 2.162 µs | 2.083 µs | 2.167 µs | 2.167 µs | 2.291 µs | 2.385 µs | 456,830 |
| serialize | `rpcv2Json_PutItemRequest_ShallowMap_S` | 5,000 | 5.560 µs | 5.417 µs | 5.542 µs | 5.625 µs | 6.083 µs | 2.284 µs | 179,695 |
| serialize | `rpcv2Json_PutItemRequest_ShallowMap_M` | 5,000 | 27.699 µs | 27.166 µs | 28.209 µs | 28.500 µs | 39.667 µs | 4.885 µs | 36,252 |
| serialize | `rpcv2Json_PutItemRequest_ShallowMap_L` | 5,000 | 82.543 µs | 81.041 µs | 84.125 µs | 86.083 µs | 121.125 µs | 13.554 µs | 12,181 |
| serialize | `rpcv2Json_PutItemRequest_Nested_M` | 5,000 | 5.262 µs | 5.209 µs | 5.416 µs | 5.500 µs | 5.791 µs | 805 ns | 189,559 |
| serialize | `rpcv2Json_PutItemRequest_Nested_L` | 5,000 | 18.856 µs | 18.792 µs | 19.416 µs | 19.500 µs | 19.792 µs | 493 ns | 52,946 |
| serialize | `rpcv2Json_PutItemRequest_MixedItem_S` | 5,000 | 6.745 µs | 6.708 µs | 6.917 µs | 7.000 µs | 7.291 µs | 1.104 µs | 147,815 |
| serialize | `rpcv2Json_PutItemRequest_MixedItem_M` | 5,000 | 26.106 µs | 25.583 µs | 26.542 µs | 26.875 µs | 39.458 µs | 4.156 µs | 38,462 |
| serialize | `rpcv2Json_PutItemRequest_MixedItem_L` | 5,000 | 73.562 µs | 71.584 µs | 76.333 µs | 77.084 µs | 92.166 µs | 5.496 µs | 13,608 |
| serialize | `rpcv2Json_PutItemRequest_BinaryData_S` | 5,000 | 2.603 µs | 2.583 µs | 2.708 µs | 2.750 µs | 2.833 µs | 105 ns | 380,113 |
| serialize | `rpcv2Json_PutItemRequest_BinaryData_M` | 5,000 | 22.868 µs | 22.708 µs | 23.292 µs | 23.584 µs | 24.917 µs | 1.014 µs | 43,684 |
| serialize | `rpcv2Json_PutItemRequest_BinaryData_L` | 5,000 | 171.526 µs | 168.292 µs | 178.458 µs | 190.500 µs | 218.875 µs | 10.755 µs | 5,848 |
| deserialize | `rpcv2Json_WideTypesResponse_S` | 5,000 | 9.809 µs | 9.625 µs | 10.042 µs | 10.167 µs | 11.208 µs | 2.917 µs | 101,754 |
| deserialize | `rpcv2Json_WideTypesResponse_M` | 5,000 | 16.694 µs | 16.584 µs | 16.875 µs | 17.083 µs | 20.375 µs | 3.104 µs | 59,944 |
| deserialize | `rpcv2Json_WideTypesResponse_L` | 5,000 | 31.793 µs | 31.167 µs | 31.875 µs | 32.541 µs | 53.500 µs | 7.184 µs | 31,648 |
| deserialize | `rpcv2Json_GetItemOutput_Baseline` | 5,000 | 1.661 µs | 1.583 µs | 1.667 µs | 1.708 µs | 1.791 µs | 2.110 µs | 591,436 |
| deserialize | `rpcv2Json_GetItemOutput_S` | 5,000 | 5.546 µs | 5.417 µs | 5.542 µs | 5.625 µs | 5.875 µs | 2.050 µs | 179,934 |
| deserialize | `rpcv2Json_GetItemOutput_M` | 5,000 | 16.674 µs | 16.375 µs | 16.833 µs | 17.042 µs | 23.833 µs | 3.253 µs | 60,126 |
| deserialize | `rpcv2Json_GetItemOutput_L` | 5,000 | 106.111 µs | 105.166 µs | 109.083 µs | 120.125 µs | 169.250 µs | 11.116 µs | 9,455 |
| deserialize | `rpcv2Json_GetItemOutput_OutOfOrder` | 5,000 | 17.136 µs | 16.625 µs | 17.750 µs | 18.000 µs | 27.333 µs | 2.575 µs | 58,389 |
| deserialize | `rpcv2Json_GetItemOutputBinary_S` | 5,000 | 5.751 µs | 5.583 µs | 5.792 µs | 5.958 µs | 6.458 µs | 2.037 µs | 173,859 |
| deserialize | `rpcv2Json_GetItemOutputBinary_M` | 5,000 | 18.235 µs | 17.667 µs | 18.833 µs | 19.083 µs | 28.167 µs | 3.109 µs | 54,962 |
| deserialize | `rpcv2Json_GetItemOutputBinary_L` | 5,000 | 122.327 µs | 119.208 µs | 126.375 µs | 137.459 µs | 187.542 µs | 13.955 µs | 8,204 |
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
