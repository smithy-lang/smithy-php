# Trimmed client code generation output

This directory contains committed output from the Smithy PHP generator for a
client trimmed to the `GetPet` operation.

The source model also contains `ListPets` and `DeletePet`. Their operation,
input, output, paginator, and exclusive error artifacts are intentionally
absent from `src`.

Do not edit files under `src` directly. Regenerate them from the repository
root:

```console
composer codegen-output
```

CI runs `composer codegen-output:check` to detect stale output.
