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

The scripts can also be invoked directly on systems without Make:

```shell
php benchmarks/serde/generate.php
php benchmarks/serde/run.php
php benchmarks/client/run.php
php benchmarks/serde/run.php --update-readme
php benchmarks/client/run.php --update-readme
php benchmarks/serde/generate.php --clean
```

Use either runner's `--help` option for filtering and iteration controls.
Raw result files are written to `build/benchmarks/results/`. Set
`SMITHY_BENCHMARK_INSTANCE` to record a stable machine or instance label.

## Methodology

- Inputs, operation metadata, protocol objects, and response fixtures are
  prepared before timing.
- Client pipeline cases invoke a generated client with a reusable in-memory
  HTTP response. They include endpoint resolution, auth selection, signing,
  retries, serialization, deserialization, and optional middleware dispatch,
  but exclude socket and TLS work.
- Runtime code generation is not enabled.
- Each case is validated once before measurement.
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

Recorded: 2026-10-06T00:00:08+00:00

- Revision: `4f7a16e`
- PHP: `8.2.33 (cli)`
- OS: `Darwin 25.6.0 arm64`
- Instance: `Apple M1 Pro`
- OPcache: `enabled`
- JIT: `enabled`
- JIT setting: `tracing`
- JIT buffer: `256M`

| Direction | Case | n | Mean | p50 | p90 | p95 | p99 | Std dev | Ops/CPU s |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| serialize | `rpcv2Cbor_WideTypesRequest_S` | 5,000 | 8.478 µs | 8.083 µs | 8.500 µs | 8.833 µs | 17.250 µs | 3.706 µs | 118,226 |
| serialize | `rpcv2Cbor_WideTypesRequest_M` | 5,000 | 14.852 µs | 14.291 µs | 15.125 µs | 15.709 µs | 30.667 µs | 3.190 µs | 67,851 |
| serialize | `rpcv2Cbor_WideTypesRequest_L` | 5,000 | 28.985 µs | 27.583 µs | 29.250 µs | 32.708 µs | 58.375 µs | 5.781 µs | 35,094 |
| serialize | `rpcv2Cbor_PutItemRequest_Baseline` | 5,000 | 2.055 µs | 1.917 µs | 2.042 µs | 2.125 µs | 2.500 µs | 1.630 µs | 492,078 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_S` | 5,000 | 4.180 µs | 4.000 µs | 4.250 µs | 4.334 µs | 7.125 µs | 1.790 µs | 241,920 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_M` | 5,000 | 17.611 µs | 17.167 µs | 18.083 µs | 18.417 µs | 30.125 µs | 2.559 µs | 57,152 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_L` | 5,000 | 51.544 µs | 50.792 µs | 52.333 µs | 56.500 µs | 82.166 µs | 5.889 µs | 19,549 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_M` | 5,000 | 4.061 µs | 3.959 µs | 4.125 µs | 4.167 µs | 4.584 µs | 1.472 µs | 244,642 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_L` | 5,000 | 11.814 µs | 11.708 µs | 12.000 µs | 12.125 µs | 14.708 µs | 1.974 µs | 84,776 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_S` | 5,000 | 5.108 µs | 5.041 µs | 5.167 µs | 5.250 µs | 6.041 µs | 1.331 µs | 195,290 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_M` | 5,000 | 16.871 µs | 16.458 µs | 17.125 µs | 17.583 µs | 32.292 µs | 2.827 µs | 59,713 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_L` | 5,000 | 47.066 µs | 45.792 µs | 47.959 µs | 49.375 µs | 79.750 µs | 5.790 µs | 21,429 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_S` | 5,000 | 2.303 µs | 2.250 µs | 2.334 µs | 2.416 µs | 2.584 µs | 984 ns | 433,877 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_M` | 5,000 | 5.290 µs | 5.125 µs | 5.416 µs | 5.542 µs | 6.750 µs | 1.545 µs | 190,107 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_L` | 5,000 | 16.538 µs | 16.291 µs | 17.000 µs | 17.542 µs | 22.042 µs | 2.403 µs | 60,733 |
| deserialize | `rpcv2Cbor_WideTypesResponse_S` | 5,000 | 16.430 µs | 15.625 µs | 16.667 µs | 17.375 µs | 39.000 µs | 5.991 µs | 61,647 |
| deserialize | `rpcv2Cbor_WideTypesResponse_M` | 5,000 | 29.716 µs | 28.750 µs | 30.166 µs | 32.291 µs | 56.292 µs | 4.900 µs | 33,930 |
| deserialize | `rpcv2Cbor_WideTypesResponse_L` | 5,000 | 59.893 µs | 58.542 µs | 60.459 µs | 70.625 µs | 116.666 µs | 11.005 µs | 16,862 |
| deserialize | `rpcv2Cbor_GetItemOutput_Baseline` | 5,000 | 1.899 µs | 1.750 µs | 1.875 µs | 1.875 µs | 2.042 µs | 2.624 µs | 522,139 |
| deserialize | `rpcv2Cbor_GetItemOutput_S` | 5,000 | 5.180 µs | 5.042 µs | 5.167 µs | 5.209 µs | 5.500 µs | 2.251 µs | 193,050 |
| deserialize | `rpcv2Cbor_GetItemOutput_M` | 5,000 | 15.899 µs | 14.750 µs | 15.083 µs | 15.417 µs | 42.458 µs | 17.187 µs | 65,987 |
| deserialize | `rpcv2Cbor_GetItemOutput_L` | 5,000 | 89.429 µs | 86.459 µs | 91.625 µs | 96.542 µs | 151.792 µs | 13.349 µs | 11,284 |
| deserialize | `rpcv2Cbor_GetItemOutput_OutOfOrder` | 5,000 | 17.942 µs | 17.416 µs | 18.208 µs | 18.417 µs | 36.500 µs | 4.282 µs | 56,094 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_S` | 5,000 | 5.234 µs | 4.958 µs | 5.292 µs | 5.458 µs | 10.750 µs | 2.298 µs | 193,851 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_M` | 5,000 | 14.966 µs | 14.334 µs | 15.250 µs | 15.584 µs | 36.958 µs | 3.950 µs | 67,515 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_L` | 5,000 | 90.981 µs | 87.584 µs | 92.542 µs | 114.750 µs | 160.584 µs | 12.381 µs | 11,136 |
<!-- benchmark-results:end -->

### Generated client pipeline

`default` uses the normal generated-client fast path. `interceptor` installs
a pass-through plugin and interceptor to measure middleware dispatch.

<!-- client-benchmark-results:start -->
Recorded: 2026-10-06T00:00:10+00:00

- Revision: `4f7a16e`
- PHP: `8.2.33 (cli)`
- OS: `Darwin 25.6.0 arm64`
- Instance: `Apple M1 Pro`
- OPcache: `enabled`
- JIT: `enabled`
- JIT setting: `tracing`
- JIT buffer: `256M`

| Case | n | Mean | p50 | p90 | p95 | p99 | Std dev | Ops/CPU s |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| `rpcv2Cbor_ClientPipeline_default_S` | 5,000 | 30.319 µs | 29.500 µs | 30.625 µs | 31.458 µs | 55.500 µs | 5.304 µs | 33,113 |
| `rpcv2Cbor_ClientPipeline_default_M` | 5,000 | 50.812 µs | 47.792 µs | 51.959 µs | 70.791 µs | 103.791 µs | 10.421 µs | 20,019 |
| `rpcv2Cbor_ClientPipeline_default_L` | 5,000 | 98.361 µs | 92.917 µs | 105.500 µs | 137.542 µs | 206.583 µs | 23.618 µs | 10,299 |
| `rpcv2Cbor_ClientPipeline_interceptor_S` | 5,000 | 34.951 µs | 33.042 µs | 35.666 µs | 45.125 µs | 91.584 µs | 11.456 µs | 28,946 |
| `rpcv2Cbor_ClientPipeline_interceptor_M` | 5,000 | 52.780 µs | 49.792 µs | 53.833 µs | 64.834 µs | 116.292 µs | 12.207 µs | 19,158 |
| `rpcv2Cbor_ClientPipeline_interceptor_L` | 5,000 | 96.037 µs | 88.000 µs | 96.291 µs | 129.041 µs | 198.542 µs | 51.819 µs | 10,732 |
<!-- client-benchmark-results:end -->
