# Benchmarks

This directory contains repeatable performance benchmarks for smithy-php.
The suites measure request serialization and response deserialization for
RPC v2 CBOR, RPC v2 JSON, AWS JSON 1.0, AWS JSON 1.1, AWS Query, EC2 Query,
REST JSON, and REST XML, plus RPC v2 CBOR generated-client pipeline execution
without network I/O. Serde cases use the shared Smithy Java
workload and result schema.

## Requirements

- PHP 8.2 or later
- Composer dependencies installed with `composer install`
- GNU Make for the convenience targets below

The serde suite uses one slim model per protocol under `serde/model/`
(`rpcv2-cbor.json`, `rpcv2-json.json`, `aws-json-1-0.json`,
`aws-json-1-1.json`, `aws-query.json`, `ec2-query.json`, `rest-json.json`,
and `rest-xml.json`). Each model carries the operations that smithy-java's
serde benchmarks run for that protocol, with the same case names, parameters,
and payloads. Generated PHP is written under `build/benchmarks/` and is not
committed.

| Protocol | Operations |
| --- | --- |
| RPC v2 CBOR | `WideTypes`, DynamoDB `PutItem` and `GetItem` |
| AWS JSON 1.0 | `WideTypes`, DynamoDB `PutItem` and `GetItem`, `Healthcheck` |
| AWS Query | `WideTypes`, CloudWatch `PutMetricData` and `GetMetricData` |
| REST JSON, REST XML | `WideTypes`, S3 `PutObject`, `GetObject` and `CopyObject` |

smithy-java defines no benchmark cases for RPC v2 JSON, AWS JSON 1.1, or EC2
Query. The RPC v2 JSON model mirrors the RPC v2 CBOR case list and parameters,
with protocol-specific JSON response bodies, and the AWS JSON 1.1 model
mirrors AWS JSON 1.0. EC2 Query has no map support, so its model contains only
the `WideTypes` cases with the map members removed. The AWS protocol models
carry the expected method, path, query, headers, and body for each request.
The JSON protocols encode arbitrary-precision response numbers as strings, as
the RPC v2 JSON fixtures do. The `awsQuery_GetMetricDataResponse_OutOfOrder`
fixture lists its expected `Values` and `Timestamps` in the order the response
body encodes them, which differs from the smithy-java `params`.

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

Run only one protocol:

```shell
php benchmarks/serde/run.php --protocol=rpcv2-cbor
php benchmarks/serde/run.php --protocol=rpcv2-json
php benchmarks/serde/run.php --protocol=aws-json-1-0
php benchmarks/serde/run.php --protocol=aws-json-1-1
php benchmarks/serde/run.php --protocol=aws-query
php benchmarks/serde/run.php --protocol=ec2-query
php benchmarks/serde/run.php --protocol=rest-json
php benchmarks/serde/run.php --protocol=rest-xml
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
- The `bare` client case disables all plugins and measures the
  interceptor-free pipeline. The `builtin` case keeps the built-in plugins
  a default client installs. The `interceptor` case enables only the
  pass-through benchmark interceptor.
- Runtime code generation is not enabled.
- Each case receives a smoke validation before measurement. Serialization
  checks request metadata and decodes the emitted payload back into the input
  shape. AWS protocol serialization checks the method, path, query, headers,
  and body against the fixture instead, because HTTP bindings move members out
  of the body. Deserialization checks the output type and compares values when the
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

Recorded: 2026-11-15T18:51:03+00:00

- Revision: `83ab59f`
- PHP: `8.2.35 (cli)`
- OS: `Darwin 25.6.0 arm64`
- Instance: `Apple M1 Pro`
- OPcache: `enabled`
- JIT: `enabled`
- JIT setting: `tracing`
- JIT buffer: `256M`

| Direction | Case | n | Mean | p50 | p90 | p95 | p99 | Std dev | Ops/CPU s |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| serialize | `rpcv2Cbor_WideTypesRequest_S` | 5,000 | 7.805 µs | 7.583 µs | 7.917 µs | 8.042 µs | 8.542 µs | 2.819 µs | 127,626 |
| serialize | `rpcv2Cbor_WideTypesRequest_M` | 5,000 | 14.350 µs | 13.708 µs | 14.333 µs | 14.625 µs | 25.041 µs | 6.929 µs | 70,320 |
| serialize | `rpcv2Cbor_WideTypesRequest_L` | 5,000 | 26.894 µs | 26.125 µs | 27.125 µs | 28.333 µs | 43.375 µs | 5.215 µs | 37,280 |
| serialize | `rpcv2Cbor_PutItemRequest_Baseline` | 5,000 | 1.707 µs | 1.708 µs | 1.750 µs | 1.792 µs | 1.875 µs | 511 ns | 576,369 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_S` | 5,000 | 3.782 µs | 3.709 µs | 3.875 µs | 3.917 µs | 4.125 µs | 1.155 µs | 263,560 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_M` | 5,000 | 16.796 µs | 16.541 µs | 17.000 µs | 17.334 µs | 25.792 µs | 2.254 µs | 59,598 |
| serialize | `rpcv2Cbor_PutItemRequest_ShallowMap_L` | 5,000 | 48.815 µs | 47.875 µs | 49.875 µs | 51.541 µs | 73.334 µs | 4.988 µs | 20,562 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_M` | 5,000 | 3.810 µs | 3.625 µs | 3.791 µs | 3.875 µs | 10.125 µs | 2.421 µs | 261,588 |
| serialize | `rpcv2Cbor_PutItemRequest_Nested_L` | 5,000 | 11.495 µs | 11.250 µs | 11.667 µs | 11.917 µs | 15.667 µs | 2.544 µs | 87,213 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_S` | 5,000 | 4.718 µs | 4.625 µs | 4.792 µs | 4.875 µs | 5.208 µs | 1.351 µs | 211,149 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_M` | 5,000 | 16.464 µs | 16.167 µs | 16.625 µs | 16.959 µs | 25.208 µs | 2.532 µs | 60,843 |
| serialize | `rpcv2Cbor_PutItemRequest_MixedItem_L` | 5,000 | 46.961 µs | 45.792 µs | 48.250 µs | 50.417 µs | 71.125 µs | 5.721 µs | 21,363 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_S` | 5,000 | 2.021 µs | 2.000 µs | 2.125 µs | 2.125 µs | 2.250 µs | 434 ns | 487,757 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_M` | 5,000 | 5.027 µs | 4.917 µs | 5.166 µs | 5.291 µs | 5.625 µs | 1.229 µs | 198,381 |
| serialize | `rpcv2Cbor_PutItemRequest_BinaryData_L` | 5,000 | 18.584 µs | 18.000 µs | 19.542 µs | 20.250 µs | 31.958 µs | 5.595 µs | 54,185 |
| deserialize | `rpcv2Cbor_WideTypesResponse_S` | 5,000 | 10.039 µs | 9.458 µs | 9.959 µs | 10.208 µs | 15.500 µs | 6.914 µs | 99,677 |
| deserialize | `rpcv2Cbor_WideTypesResponse_M` | 5,000 | 17.487 µs | 17.000 µs | 17.750 µs | 18.167 µs | 24.958 µs | 4.103 µs | 57,345 |
| deserialize | `rpcv2Cbor_WideTypesResponse_L` | 5,000 | 34.199 µs | 33.250 µs | 34.833 µs | 35.709 µs | 59.041 µs | 5.757 µs | 29,334 |
| deserialize | `rpcv2Cbor_GetItemOutput_Baseline` | 5,000 | 1.041 µs | 1.000 µs | 1.083 µs | 1.083 µs | 1.125 µs | 1.445 µs | 935,454 |
| deserialize | `rpcv2Cbor_GetItemOutput_S` | 5,000 | 4.207 µs | 4.166 µs | 4.333 µs | 4.416 µs | 4.625 µs | 1.004 µs | 236,351 |
| deserialize | `rpcv2Cbor_GetItemOutput_M` | 5,000 | 13.848 µs | 13.542 µs | 14.208 µs | 14.416 µs | 18.000 µs | 2.716 µs | 72,269 |
| deserialize | `rpcv2Cbor_GetItemOutput_L` | 5,000 | 90.268 µs | 87.875 µs | 92.666 µs | 100.959 µs | 143.625 µs | 9.687 µs | 11,114 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_S` | 5,000 | 4.354 µs | 4.250 µs | 4.459 µs | 4.583 µs | 4.875 µs | 1.626 µs | 229,158 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_M` | 5,000 | 14.504 µs | 13.958 µs | 14.625 µs | 14.958 µs | 29.000 µs | 3.998 µs | 69,242 |
| deserialize | `rpcv2Cbor_GetItemOutputBinary_L` | 5,000 | 91.161 µs | 88.875 µs | 93.333 µs | 99.875 µs | 142.625 µs | 10.143 µs | 11,007 |
| deserialize | `rpcv2Cbor_GetItemOutput_OutOfOrder` | 5,000 | 16.523 µs | 16.042 µs | 16.875 µs | 17.167 µs | 28.709 µs | 3.897 µs | 60,722 |
| serialize | `rpcv2Json_WideTypesRequest_S` | 5,000 | 6.833 µs | 6.541 µs | 6.833 µs | 6.959 µs | 10.291 µs | 3.237 µs | 146,301 |
| serialize | `rpcv2Json_WideTypesRequest_M` | 5,000 | 11.136 µs | 10.875 µs | 11.250 µs | 11.500 µs | 15.208 µs | 2.481 µs | 89,944 |
| serialize | `rpcv2Json_WideTypesRequest_L` | 5,000 | 20.790 µs | 20.250 µs | 21.042 µs | 21.709 µs | 35.000 µs | 3.744 µs | 48,216 |
| serialize | `rpcv2Json_PutItemRequest_Baseline` | 5,000 | 2.052 µs | 1.959 µs | 2.083 µs | 2.084 µs | 2.250 µs | 1.658 µs | 482,532 |
| serialize | `rpcv2Json_PutItemRequest_ShallowMap_S` | 5,000 | 5.015 µs | 4.916 µs | 5.083 µs | 5.167 µs | 5.500 µs | 1.500 µs | 199,275 |
| serialize | `rpcv2Json_PutItemRequest_ShallowMap_M` | 5,000 | 25.242 µs | 24.667 µs | 25.667 µs | 26.250 µs | 41.417 µs | 3.707 µs | 39,748 |
| serialize | `rpcv2Json_PutItemRequest_ShallowMap_L` | 5,000 | 75.599 µs | 73.500 µs | 77.333 µs | 85.917 µs | 116.708 µs | 8.109 µs | 13,281 |
| serialize | `rpcv2Json_PutItemRequest_Nested_M` | 5,000 | 5.151 µs | 5.083 µs | 5.250 µs | 5.333 µs | 5.625 µs | 1.365 µs | 193,701 |
| serialize | `rpcv2Json_PutItemRequest_Nested_L` | 5,000 | 19.636 µs | 19.209 µs | 19.917 µs | 20.292 µs | 29.750 µs | 3.570 µs | 51,022 |
| serialize | `rpcv2Json_PutItemRequest_MixedItem_S` | 5,000 | 6.535 µs | 6.375 µs | 6.583 µs | 6.708 µs | 7.250 µs | 2.271 µs | 153,003 |
| serialize | `rpcv2Json_PutItemRequest_MixedItem_M` | 5,000 | 24.837 µs | 24.166 µs | 25.208 µs | 25.791 µs | 41.958 µs | 4.134 µs | 40,359 |
| serialize | `rpcv2Json_PutItemRequest_MixedItem_L` | 5,000 | 71.350 µs | 69.625 µs | 72.750 µs | 76.125 µs | 108.958 µs | 7.988 µs | 14,060 |
| serialize | `rpcv2Json_PutItemRequest_BinaryData_S` | 5,000 | 2.651 µs | 2.584 µs | 2.708 µs | 2.750 µs | 2.916 µs | 1.195 µs | 374,869 |
| serialize | `rpcv2Json_PutItemRequest_BinaryData_M` | 5,000 | 23.559 µs | 22.917 µs | 24.000 µs | 24.375 µs | 40.333 µs | 3.550 µs | 42,638 |
| serialize | `rpcv2Json_PutItemRequest_BinaryData_L` | 5,000 | 171.693 µs | 167.584 µs | 182.041 µs | 195.917 µs | 224.542 µs | 12.083 µs | 5,850 |
| deserialize | `rpcv2Json_WideTypesResponse_S` | 5,000 | 9.253 µs | 8.916 µs | 9.292 µs | 9.459 µs | 15.542 µs | 3.466 µs | 108,213 |
| deserialize | `rpcv2Json_WideTypesResponse_M` | 5,000 | 16.123 µs | 15.625 µs | 16.333 µs | 16.542 µs | 26.291 µs | 4.264 µs | 62,339 |
| deserialize | `rpcv2Json_WideTypesResponse_L` | 5,000 | 30.513 µs | 29.666 µs | 31.208 µs | 31.708 µs | 47.625 µs | 5.587 µs | 32,875 |
| deserialize | `rpcv2Json_GetItemOutput_Baseline` | 5,000 | 1.004 µs | 958 ns | 1.000 µs | 1.042 µs | 1.084 µs | 1.207 µs | 972,006 |
| deserialize | `rpcv2Json_GetItemOutput_S` | 5,000 | 4.809 µs | 4.708 µs | 4.916 µs | 5.000 µs | 5.333 µs | 1.473 µs | 207,460 |
| deserialize | `rpcv2Json_GetItemOutput_M` | 5,000 | 15.948 µs | 15.583 µs | 15.917 µs | 16.458 µs | 27.333 µs | 3.096 µs | 62,749 |
| deserialize | `rpcv2Json_GetItemOutput_L` | 5,000 | 105.829 µs | 102.834 µs | 109.167 µs | 118.542 µs | 166.000 µs | 10.688 µs | 9,481 |
| deserialize | `rpcv2Json_GetItemOutputBinary_S` | 5,000 | 5.389 µs | 5.250 µs | 5.458 µs | 5.542 µs | 5.834 µs | 1.781 µs | 185,543 |
| deserialize | `rpcv2Json_GetItemOutputBinary_M` | 5,000 | 18.298 µs | 17.875 µs | 18.666 µs | 18.958 µs | 28.834 µs | 3.062 µs | 54,758 |
| deserialize | `rpcv2Json_GetItemOutputBinary_L` | 5,000 | 130.886 µs | 121.834 µs | 131.458 µs | 146.125 µs | 232.500 µs | 97.669 µs | 7,909 |
| deserialize | `rpcv2Json_GetItemOutput_OutOfOrder` | 5,000 | 17.286 µs | 16.917 µs | 17.583 µs | 17.959 µs | 24.333 µs | 2.873 µs | 57,963 |
| serialize | `awsJson10_WideTypesRequest_S` | 5,000 | 7.194 µs | 6.917 µs | 7.250 µs | 7.416 µs | 9.750 µs | 3.122 µs | 138,900 |
| serialize | `awsJson10_WideTypesRequest_M` | 5,000 | 12.129 µs | 11.792 µs | 12.334 µs | 12.459 µs | 15.417 µs | 3.190 µs | 82,621 |
| serialize | `awsJson10_WideTypesRequest_L` | 5,000 | 22.876 µs | 22.208 µs | 23.084 µs | 23.292 µs | 34.583 µs | 5.795 µs | 43,876 |
| serialize | `awsJson10_GetItemInput_Baseline` | 5,000 | 2.029 µs | 1.959 µs | 2.042 µs | 2.084 µs | 2.166 µs | 1.900 µs | 484,872 |
| serialize | `awsJson10_PutItemRequest_Baseline` | 5,000 | 2.041 µs | 2.000 µs | 2.042 µs | 2.084 µs | 2.167 µs | 1.686 µs | 482,812 |
| serialize | `awsJson10_PutItemRequest_ShallowMap_S` | 5,000 | 5.379 µs | 5.041 µs | 5.125 µs | 5.167 µs | 5.417 µs | 14.750 µs | 194,500 |
| serialize | `awsJson10_PutItemRequest_ShallowMap_M` | 5,000 | 25.383 µs | 24.875 µs | 25.791 µs | 26.083 µs | 35.000 µs | 3.986 µs | 39,529 |
| serialize | `awsJson10_PutItemRequest_ShallowMap_L` | 5,000 | 76.370 µs | 73.958 µs | 77.500 µs | 79.958 µs | 132.750 µs | 10.046 µs | 13,157 |
| serialize | `awsJson10_PutItemRequest_Nested_M` | 5,000 | 5.149 µs | 5.042 µs | 5.250 µs | 5.333 µs | 5.500 µs | 1.766 µs | 194,099 |
| serialize | `awsJson10_PutItemRequest_Nested_L` | 5,000 | 19.656 µs | 19.084 µs | 20.000 µs | 20.208 µs | 26.833 µs | 4.177 µs | 51,102 |
| serialize | `awsJson10_PutItemRequest_MixedItem_S` | 5,000 | 6.515 µs | 6.333 µs | 6.625 µs | 6.709 µs | 7.583 µs | 2.100 µs | 153,704 |
| serialize | `awsJson10_PutItemRequest_MixedItem_M` | 5,000 | 24.914 µs | 24.166 µs | 25.167 µs | 25.459 µs | 44.417 µs | 4.653 µs | 40,285 |
| serialize | `awsJson10_PutItemRequest_MixedItem_L` | 5,000 | 73.158 µs | 70.167 µs | 73.500 µs | 77.000 µs | 131.292 µs | 17.222 µs | 13,789 |
| serialize | `awsJson10_PutItemRequest_BinaryData_S` | 5,000 | 2.630 µs | 2.542 µs | 2.667 µs | 2.709 µs | 2.916 µs | 1.258 µs | 379,334 |
| serialize | `awsJson10_PutItemRequest_BinaryData_M` | 5,000 | 23.906 µs | 23.083 µs | 24.000 µs | 24.291 µs | 41.250 µs | 9.355 µs | 42,249 |
| serialize | `awsJson10_PutItemRequest_BinaryData_L` | 5,000 | 170.784 µs | 167.583 µs | 177.459 µs | 193.375 µs | 228.417 µs | 12.306 µs | 5,885 |
| serialize | `awsJson10_HealthcheckRequest_Example` | 5,000 | 1.278 µs | 1.250 µs | 1.292 µs | 1.333 µs | 1.416 µs | 1.115 µs | 767,460 |
| deserialize | `awsJson10_WideTypesResponse_S` | 5,000 | 8.927 µs | 8.625 µs | 9.000 µs | 9.167 µs | 12.208 µs | 3.785 µs | 112,691 |
| deserialize | `awsJson10_WideTypesResponse_M` | 5,000 | 15.694 µs | 15.250 µs | 15.792 µs | 16.125 µs | 25.625 µs | 3.897 µs | 63,961 |
| deserialize | `awsJson10_WideTypesResponse_L` | 5,000 | 30.286 µs | 29.541 µs | 30.750 µs | 31.417 µs | 46.375 µs | 5.485 µs | 33,129 |
| deserialize | `awsJson10_GetItemOutput_Baseline` | 5,000 | 791 ns | 750 ns | 792 ns | 833 ns | 916 ns | 1.053 µs | 1,227,295 |
| deserialize | `awsJson10_GetItemOutput_S` | 5,000 | 4.761 µs | 4.667 µs | 4.833 µs | 4.917 µs | 5.208 µs | 1.465 µs | 209,529 |
| deserialize | `awsJson10_GetItemOutput_M` | 5,000 | 16.342 µs | 15.958 µs | 16.625 µs | 16.875 µs | 26.500 µs | 3.242 µs | 61,377 |
| deserialize | `awsJson10_GetItemOutput_L` | 5,000 | 111.037 µs | 108.000 µs | 114.833 µs | 124.292 µs | 173.084 µs | 11.262 µs | 9,039 |
| deserialize | `awsJson10_GetItemOutputBinary_S` | 5,000 | 5.255 µs | 5.167 µs | 5.292 µs | 5.375 µs | 5.750 µs | 1.324 µs | 189,681 |
| deserialize | `awsJson10_GetItemOutputBinary_M` | 5,000 | 18.504 µs | 18.083 µs | 18.542 µs | 19.125 µs | 31.125 µs | 3.338 µs | 54,139 |
| deserialize | `awsJson10_GetItemOutputBinary_L` | 5,000 | 130.236 µs | 126.584 µs | 136.417 µs | 146.000 µs | 198.250 µs | 12.503 µs | 7,707 |
| deserialize | `awsJson10_GetItemOutput_OutOfOrder` | 5,000 | 17.584 µs | 17.166 µs | 17.834 µs | 18.250 µs | 30.083 µs | 3.288 µs | 56,997 |
| deserialize | `awsJson10_HealthcheckResponse_Example` | 5,000 | 754 ns | 709 ns | 791 ns | 792 ns | 875 ns | 971 ns | 1,282,051 |
| serialize | `awsJson11_WideTypesRequest_S` | 5,000 | 7.060 µs | 6.875 µs | 7.125 µs | 7.250 µs | 7.666 µs | 2.701 µs | 141,315 |
| serialize | `awsJson11_WideTypesRequest_M` | 5,000 | 11.925 µs | 11.708 µs | 12.000 µs | 12.291 µs | 15.042 µs | 2.391 µs | 83,983 |
| serialize | `awsJson11_WideTypesRequest_L` | 5,000 | 22.378 µs | 21.875 µs | 22.625 µs | 23.250 µs | 37.542 µs | 3.706 µs | 44,784 |
| serialize | `awsJson11_GetItemInput_Baseline` | 5,000 | 1.998 µs | 1.917 µs | 2.042 µs | 2.083 µs | 2.250 µs | 1.458 µs | 493,535 |
| serialize | `awsJson11_PutItemRequest_Baseline` | 5,000 | 2.031 µs | 1.958 µs | 2.042 µs | 2.083 µs | 2.208 µs | 1.890 µs | 488,663 |
| serialize | `awsJson11_PutItemRequest_ShallowMap_S` | 5,000 | 4.885 µs | 4.792 µs | 4.958 µs | 5.042 µs | 5.333 µs | 1.164 µs | 204,466 |
| serialize | `awsJson11_PutItemRequest_ShallowMap_M` | 5,000 | 25.442 µs | 24.709 µs | 25.875 µs | 26.209 µs | 37.459 µs | 4.079 µs | 39,400 |
| serialize | `awsJson11_PutItemRequest_ShallowMap_L` | 5,000 | 75.411 µs | 73.625 µs | 76.834 µs | 82.291 µs | 119.750 µs | 7.859 µs | 13,312 |
| serialize | `awsJson11_PutItemRequest_Nested_M` | 5,000 | 5.110 µs | 4.959 µs | 5.208 µs | 5.333 µs | 5.667 µs | 1.392 µs | 195,351 |
| serialize | `awsJson11_PutItemRequest_Nested_L` | 5,000 | 19.344 µs | 19.000 µs | 19.417 µs | 20.000 µs | 29.833 µs | 3.151 µs | 51,797 |
| serialize | `awsJson11_PutItemRequest_MixedItem_S` | 5,000 | 6.463 µs | 6.375 µs | 6.500 µs | 6.667 µs | 7.083 µs | 1.481 µs | 154,426 |
| serialize | `awsJson11_PutItemRequest_MixedItem_M` | 5,000 | 24.517 µs | 24.000 µs | 24.709 µs | 25.250 µs | 37.542 µs | 3.989 µs | 40,904 |
| serialize | `awsJson11_PutItemRequest_MixedItem_L` | 5,000 | 72.273 µs | 70.125 µs | 73.167 µs | 76.500 µs | 123.167 µs | 10.638 µs | 13,905 |
| serialize | `awsJson11_PutItemRequest_BinaryData_S` | 5,000 | 2.647 µs | 2.584 µs | 2.708 µs | 2.750 µs | 2.833 µs | 968 ns | 374,728 |
| serialize | `awsJson11_PutItemRequest_BinaryData_M` | 5,000 | 25.565 µs | 23.791 µs | 24.333 µs | 25.500 µs | 60.792 µs | 36.537 µs | 41,084 |
| serialize | `awsJson11_PutItemRequest_BinaryData_L` | 5,000 | 170.654 µs | 167.458 µs | 177.167 µs | 193.333 µs | 227.584 µs | 12.031 µs | 5,889 |
| serialize | `awsJson11_HealthcheckRequest_Example` | 5,000 | 1.325 µs | 1.250 µs | 1.375 µs | 1.375 µs | 1.459 µs | 1.586 µs | 747,049 |
| deserialize | `awsJson11_WideTypesResponse_S` | 5,000 | 8.846 µs | 8.625 µs | 8.917 µs | 9.083 µs | 10.542 µs | 2.547 µs | 113,114 |
| deserialize | `awsJson11_WideTypesResponse_M` | 5,000 | 15.498 µs | 15.208 µs | 15.792 µs | 16.042 µs | 19.125 µs | 2.781 µs | 64,630 |
| deserialize | `awsJson11_WideTypesResponse_L` | 5,000 | 30.045 µs | 29.375 µs | 30.625 µs | 31.125 µs | 46.166 µs | 4.948 µs | 33,373 |
| deserialize | `awsJson11_GetItemOutput_Baseline` | 5,000 | 770 ns | 750 ns | 792 ns | 833 ns | 875 ns | 716 ns | 1,256,913 |
| deserialize | `awsJson11_GetItemOutput_S` | 5,000 | 4.735 µs | 4.625 µs | 4.833 µs | 4.917 µs | 5.208 µs | 1.846 µs | 211,238 |
| deserialize | `awsJson11_GetItemOutput_M` | 5,000 | 16.177 µs | 15.958 µs | 16.208 µs | 16.625 µs | 18.750 µs | 2.551 µs | 61,892 |
| deserialize | `awsJson11_GetItemOutput_L` | 5,000 | 111.049 µs | 108.166 µs | 114.292 µs | 123.916 µs | 172.959 µs | 11.532 µs | 9,038 |
| deserialize | `awsJson11_GetItemOutputBinary_S` | 5,000 | 5.197 µs | 5.125 µs | 5.250 µs | 5.375 µs | 5.625 µs | 1.036 µs | 191,439 |
| deserialize | `awsJson11_GetItemOutputBinary_M` | 5,000 | 18.466 µs | 18.000 µs | 18.458 µs | 19.000 µs | 31.917 µs | 3.703 µs | 54,273 |
| deserialize | `awsJson11_GetItemOutputBinary_L` | 5,000 | 136.554 µs | 126.875 µs | 139.667 µs | 158.208 µs | 269.958 µs | 78.729 µs | 7,553 |
| deserialize | `awsJson11_GetItemOutput_OutOfOrder` | 5,000 | 17.608 µs | 17.166 µs | 17.917 µs | 18.292 µs | 30.250 µs | 3.327 µs | 56,928 |
| deserialize | `awsJson11_HealthcheckResponse_Example` | 5,000 | 744 ns | 708 ns | 750 ns | 792 ns | 875 ns | 1.009 µs | 1,304,461 |
| serialize | `awsQuery_WideTypesRequest_S` | 5,000 | 7.174 µs | 6.917 µs | 7.250 µs | 7.417 µs | 10.166 µs | 2.879 µs | 139,521 |
| serialize | `awsQuery_WideTypesRequest_M` | 5,000 | 12.342 µs | 12.083 µs | 12.459 µs | 12.750 µs | 18.708 µs | 2.710 µs | 81,131 |
| serialize | `awsQuery_WideTypesRequest_L` | 5,000 | 23.372 µs | 22.875 µs | 23.625 µs | 24.625 µs | 36.792 µs | 3.594 µs | 42,871 |
| serialize | `awsQuery_GetMetricDataRequest_S` | 5,000 | 7.490 µs | 7.250 µs | 7.459 µs | 7.625 µs | 11.125 µs | 3.338 µs | 133,601 |
| serialize | `awsQuery_GetMetricDataRequest_M` | 5,000 | 31.168 µs | 30.375 µs | 31.750 µs | 32.666 µs | 53.416 µs | 5.007 µs | 32,216 |
| serialize | `awsQuery_GetMetricDataRequest_L` | 5,000 | 57.344 µs | 55.583 µs | 58.375 µs | 60.584 µs | 100.917 µs | 8.300 µs | 17,507 |
| serialize | `awsQuery_PutMetricDataRequest_Baseline` | 5,000 | 1.145 µs | 1.084 µs | 1.167 µs | 1.208 µs | 1.291 µs | 1.090 µs | 857,486 |
| serialize | `awsQuery_PutMetricDataRequest_S` | 5,000 | 4.035 µs | 3.958 µs | 4.125 µs | 4.167 µs | 4.375 µs | 1.823 µs | 246,500 |
| serialize | `awsQuery_PutMetricDataRequest_M` | 5,000 | 25.940 µs | 25.125 µs | 26.209 µs | 26.500 µs | 39.458 µs | 6.337 µs | 38,728 |
| serialize | `awsQuery_PutMetricDataRequest_L` | 5,000 | 208.877 µs | 201.500 µs | 222.666 µs | 245.333 µs | 306.875 µs | 20.769 µs | 4,813 |
| deserialize | `awsQuery_WideTypesResponse_S` | 5,000 | 20.170 µs | 19.375 µs | 20.250 µs | 20.667 µs | 38.250 µs | 6.068 µs | 49,804 |
| deserialize | `awsQuery_WideTypesResponse_M` | 5,000 | 34.025 µs | 32.916 µs | 34.542 µs | 36.041 µs | 61.583 µs | 6.417 µs | 29,503 |
| deserialize | `awsQuery_WideTypesResponse_L` | 5,000 | 63.637 µs | 61.833 µs | 64.959 µs | 70.750 µs | 105.334 µs | 8.459 µs | 15,770 |
| deserialize | `awsQuery_GetMetricDataResponse_S` | 5,000 | 16.684 µs | 16.250 µs | 16.750 µs | 17.250 µs | 29.708 µs | 3.430 µs | 60,063 |
| deserialize | `awsQuery_GetMetricDataResponse_M` | 5,000 | 51.827 µs | 50.500 µs | 52.917 µs | 55.458 µs | 84.667 µs | 7.045 µs | 19,374 |
| deserialize | `awsQuery_GetMetricDataResponse_L` | 5,000 | 522.445 µs | 508.834 µs | 564.875 µs | 596.208 µs | 659.833 µs | 33.578 µs | 1,922 |
| deserialize | `awsQuery_GetMetricDataResponse_OutOfOrder` | 5,000 | 58.691 µs | 56.792 µs | 59.917 µs | 62.792 µs | 100.375 µs | 8.441 µs | 17,113 |
| serialize | `ec2Query_WideTypesRequest_S` | 5,000 | 6.108 µs | 5.917 µs | 6.208 µs | 6.292 µs | 6.750 µs | 2.560 µs | 163,564 |
| serialize | `ec2Query_WideTypesRequest_M` | 5,000 | 10.622 µs | 10.333 µs | 10.791 µs | 10.958 µs | 13.208 µs | 3.068 µs | 94,334 |
| serialize | `ec2Query_WideTypesRequest_L` | 5,000 | 19.958 µs | 19.417 µs | 20.334 µs | 20.584 µs | 30.125 µs | 4.263 µs | 50,204 |
| deserialize | `ec2Query_WideTypesResponse_S` | 5,000 | 24.316 µs | 16.667 µs | 17.167 µs | 18.333 µs | 71.667 µs | 185.154 µs | 56,785 |
| deserialize | `ec2Query_WideTypesResponse_M` | 5,000 | 28.749 µs | 27.750 µs | 28.708 µs | 29.417 µs | 64.333 µs | 11.056 µs | 35,101 |
| deserialize | `ec2Query_WideTypesResponse_L` | 5,000 | 53.318 µs | 51.750 µs | 54.250 µs | 57.875 µs | 105.875 µs | 10.923 µs | 18,875 |
| serialize | `restJson_WideTypesRequest_S` | 5,000 | 9.637 µs | 9.208 µs | 9.583 µs | 9.875 µs | 18.542 µs | 4.473 µs | 103,937 |
| serialize | `restJson_WideTypesRequest_M` | 5,000 | 15.460 µs | 15.083 µs | 15.625 µs | 16.041 µs | 22.583 µs | 4.994 µs | 64,928 |
| serialize | `restJson_WideTypesRequest_L` | 5,000 | 29.293 µs | 27.791 µs | 28.416 µs | 29.375 µs | 78.375 µs | 17.299 µs | 34,814 |
| serialize | `restJson_CopyObjectRequest_Baseline` | 5,000 | 1.821 µs | 1.750 µs | 1.834 µs | 1.875 µs | 2.041 µs | 1.610 µs | 543,183 |
| serialize | `restJson_CopyObjectRequest_M` | 5,000 | 8.744 µs | 8.542 µs | 8.917 µs | 9.042 µs | 9.500 µs | 2.379 µs | 114,388 |
| serialize | `restJson_PutObject_S` | 5,000 | 2.028 µs | 2.000 µs | 2.083 µs | 2.084 µs | 2.208 µs | 994 ns | 486,808 |
| serialize | `restJson_PutObject_M` | 5,000 | 2.082 µs | 2.042 µs | 2.125 µs | 2.166 µs | 2.250 µs | 936 ns | 474,699 |
| serialize | `restJson_PutObject_L` | 5,000 | 2.060 µs | 2.042 µs | 2.084 µs | 2.125 µs | 2.209 µs | 811 ns | 479,432 |
| deserialize | `restJson_WideTypesResponse_S` | 5,000 | 9.248 µs | 8.917 µs | 9.292 µs | 9.458 µs | 13.709 µs | 3.110 µs | 108,324 |
| deserialize | `restJson_WideTypesResponse_M` | 5,000 | 16.078 µs | 15.667 µs | 16.250 µs | 16.416 µs | 19.709 µs | 3.987 µs | 62,383 |
| deserialize | `restJson_WideTypesResponse_L` | 5,000 | 31.444 µs | 30.416 µs | 31.417 µs | 31.958 µs | 64.000 µs | 6.873 µs | 31,980 |
| deserialize | `restJson_CopyObjectOutput_Baseline` | 5,000 | 1.260 µs | 1.250 µs | 1.292 µs | 1.333 µs | 1.375 µs | 881 ns | 776,277 |
| deserialize | `restJson_CopyObjectOutput_M` | 5,000 | 3.622 µs | 3.542 µs | 3.708 µs | 3.750 µs | 3.959 µs | 1.172 µs | 274,589 |
| deserialize | `restJson_CopyObjectOutput_OutOfOrder` | 5,000 | 5.207 µs | 5.000 µs | 5.167 µs | 5.209 µs | 5.541 µs | 6.539 µs | 195,879 |
| deserialize | `restJson_GetObject_S` | 5,000 | 2.088 µs | 2.041 µs | 2.125 µs | 2.166 µs | 2.250 µs | 1.416 µs | 474,068 |
| deserialize | `restJson_GetObject_M` | 5,000 | 2.077 µs | 2.042 µs | 2.166 µs | 2.167 µs | 2.250 µs | 576 ns | 475,376 |
| deserialize | `restJson_GetObject_L` | 5,000 | 2.079 µs | 2.042 µs | 2.125 µs | 2.167 µs | 2.250 µs | 973 ns | 476,872 |
| serialize | `restXml_WideTypesRequest_S` | 5,000 | 19.304 µs | 18.708 µs | 19.541 µs | 19.750 µs | 30.334 µs | 5.003 µs | 51,905 |
| serialize | `restXml_WideTypesRequest_M` | 5,000 | 33.847 µs | 32.750 µs | 34.333 µs | 34.834 µs | 65.041 µs | 7.333 µs | 29,682 |
| serialize | `restXml_WideTypesRequest_L` | 5,000 | 64.828 µs | 62.458 µs | 65.667 µs | 69.208 µs | 127.042 µs | 12.207 µs | 15,528 |
| serialize | `restXml_CopyObjectRequest_Baseline` | 5,000 | 1.837 µs | 1.791 µs | 1.875 µs | 1.916 µs | 2.041 µs | 1.447 µs | 537,461 |
| serialize | `restXml_CopyObjectRequest_M` | 5,000 | 8.720 µs | 8.500 µs | 8.833 µs | 8.958 µs | 10.042 µs | 2.461 µs | 115,006 |
| serialize | `restXml_PutObject_S` | 5,000 | 2.070 µs | 2.000 µs | 2.125 µs | 2.167 µs | 2.292 µs | 1.482 µs | 478,744 |
| serialize | `restXml_PutObject_M` | 5,000 | 2.050 µs | 2.000 µs | 2.084 µs | 2.166 µs | 2.292 µs | 640 ns | 481,371 |
| serialize | `restXml_PutObject_L` | 5,000 | 2.042 µs | 2.000 µs | 2.125 µs | 2.166 µs | 2.291 µs | 900 ns | 485,343 |
| deserialize | `restXml_WideTypesResponse_S` | 5,000 | 20.002 µs | 19.292 µs | 20.250 µs | 20.584 µs | 31.334 µs | 5.545 µs | 50,202 |
| deserialize | `restXml_WideTypesResponse_M` | 5,000 | 34.644 µs | 33.416 µs | 35.167 µs | 36.083 µs | 71.542 µs | 6.901 µs | 28,974 |
| deserialize | `restXml_WideTypesResponse_L` | 5,000 | 67.333 µs | 64.750 µs | 68.458 µs | 82.583 µs | 138.334 µs | 14.729 µs | 14,990 |
| deserialize | `restXml_CopyObjectOutput_Baseline` | 5,000 | 880 ns | 875 ns | 958 ns | 959 ns | 1.000 µs | 586 ns | 1,099,868 |
| deserialize | `restXml_CopyObjectOutput_M` | 5,000 | 8.262 µs | 8.041 µs | 8.417 µs | 8.584 µs | 12.084 µs | 2.221 µs | 121,286 |
| deserialize | `restXml_CopyObjectOutput_OutOfOrder` | 5,000 | 11.229 µs | 10.875 µs | 11.375 µs | 11.541 µs | 16.042 µs | 3.316 µs | 89,259 |
| deserialize | `restXml_GetObject_S` | 5,000 | 2.077 µs | 2.042 µs | 2.166 µs | 2.167 µs | 2.292 µs | 893 ns | 474,518 |
| deserialize | `restXml_GetObject_M` | 5,000 | 2.070 µs | 2.000 µs | 2.125 µs | 2.167 µs | 2.333 µs | 929 ns | 479,432 |
| deserialize | `restXml_GetObject_L` | 5,000 | 2.065 µs | 2.000 µs | 2.125 µs | 2.167 µs | 2.292 µs | 915 ns | 480,307 |
<!-- benchmark-results:end -->

### Generated client pipeline

`bare` disables all plugins. `builtin` keeps the built-in plugins a default
client installs. `interceptor` installs only a pass-through plugin and
interceptor to measure dispatch.

<!-- client-benchmark-results:start -->
Recorded: 2026-11-13T23:45:49+00:00

- Revision: `35f5eee`
- PHP: `8.2.35 (cli)`
- OS: `Darwin 25.6.0 arm64`
- Instance: `Apple M1 Pro`
- OPcache: `enabled`
- JIT: `enabled`
- JIT setting: `tracing`
- JIT buffer: `128M`

| Case | n | Mean | p50 | p90 | p95 | p99 | Std dev | Ops/CPU s |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| `rpcv2Cbor_ClientPipeline_bare_S` | 5,000 | 30.076 µs | 24.125 µs | 27.208 µs | 35.208 µs | 165.542 µs | 51.127 µs | 36,403 |
| `rpcv2Cbor_ClientPipeline_bare_M` | 5,000 | 43.466 µs | 38.084 µs | 42.041 µs | 55.667 µs | 175.583 µs | 30.651 µs | 23,890 |
| `rpcv2Cbor_ClientPipeline_bare_L` | 5,000 | 91.134 µs | 68.250 µs | 116.667 µs | 220.042 µs | 496.791 µs | 103.752 µs | 12,277 |
| `rpcv2Cbor_ClientPipeline_builtin_S` | 5,000 | 27.846 µs | 24.000 µs | 26.333 µs | 37.041 µs | 126.958 µs | 22.420 µs | 37,616 |
| `rpcv2Cbor_ClientPipeline_builtin_M` | 5,000 | 43.617 µs | 38.041 µs | 42.292 µs | 66.333 µs | 171.334 µs | 27.385 µs | 23,954 |
| `rpcv2Cbor_ClientPipeline_builtin_L` | 5,000 | 77.729 µs | 68.333 µs | 81.458 µs | 118.459 µs | 269.291 µs | 40.822 µs | 13,359 |
| `rpcv2Cbor_ClientPipeline_interceptor_S` | 5,000 | 45.340 µs | 27.000 µs | 62.541 µs | 120.459 µs | 392.584 µs | 85.577 µs | 28,877 |
| `rpcv2Cbor_ClientPipeline_interceptor_M` | 5,000 | 53.164 µs | 40.333 µs | 51.458 µs | 103.875 µs | 316.584 µs | 72.591 µs | 21,019 |
| `rpcv2Cbor_ClientPipeline_interceptor_L` | 5,000 | 102.598 µs | 71.542 µs | 117.250 µs | 235.250 µs | 613.833 µs | 173.196 µs | 11,640 |
<!-- client-benchmark-results:end -->
