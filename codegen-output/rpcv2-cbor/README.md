# RPC v2 CBOR code generation output

This directory contains committed output from the Smithy PHP generator. It
uses the official Smithy RPC v2 CBOR protocol test model.

Do not edit files under `src` directly. Regenerate them from the repository
root:

```console
composer codegen-output
```

CI runs `composer codegen-output:check` to detect stale output.
