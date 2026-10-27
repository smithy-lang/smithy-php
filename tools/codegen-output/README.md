# Codegen output

This directory contains the inputs and utilities used to generate the
committed PHP snapshots under `codegen-output`.

Regenerate all committed PHP output from the repository root:

```console
composer codegen-output
```

Check that the output is current without modifying it:

```console
composer codegen-output:check
```

## Trimmed client

The committed model at `models/trimmed-client.json` contains three operations.
The output selects only `GetPet` to demonstrate that code generation retains
its reachable shapes, errors, and waiter while omitting artifacts used only by
`ListPets` and `DeletePet`.

## RPC v2 CBOR

The committed model at `models/rpcv2-cbor.json` is a projection of:

- Smithy CLI version `1.73.0`
- Maven artifact
  `software.amazon.smithy:smithy-protocol-tests:1.73.0`
- Service `smithy.protocoltests.rpcv2Cbor#RpcV2Protocol`

To update the committed model, run the projection:

```shell
smithy build \
    --config tools/codegen-output/smithy-build.json \
    --projection rpcv2-cbor \
    --output build/codegen-output/smithy
```

The projected JSON AST is written to:

```text
build/codegen-output/smithy/rpcv2-cbor/model/model.json
```

Review the model diff, then replace `models/rpcv2-cbor.json` and regenerate
the PHP output. The `build` directory contains transient files and is
excluded from Git.
