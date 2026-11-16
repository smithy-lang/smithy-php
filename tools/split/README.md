# Monorepo split + tagging tooling (DRAFT)

Splits the smithy-php monorepo into 8 standalone, read-only Composer packages so
`composer require smithy/<pkg>` works from Packagist, all sharing one tag.

| File | Purpose |
|------|---------|
| `split.sh` | Split each `src/<Pkg>` into a standalone history and (optionally) push to a per-package repo. splitsh-lite primary, `git subtree split` fallback. |
| `packages.map` | The 8 package rows: src dir -> Composer name -> split repo slug. |
| `.github/workflows/split.yml` | Runs the split on a `v*` tag push, guarded to the canonical repo. |
| `TAGGING.md` | How one monorepo tag becomes one version across all 8 packages. |
| `REPO-REQUEST.md` | Copy-paste request for org admins to create the 8 mirror repos. |
| `ROLLBACK.md` | Checklist to retract a bad release. |

## Quick start (dry run, nothing pushed)

```
MONOREPO=/path/to/monorepo bash tools/split/split.sh
```

The org defaults to `smithy-lang`; override with `SPLIT_ORG=my-org` for a
scratch rehearsal. Set `PUSH=1` only in CI / a real release.

## Target repos

The 8 split targets are `smithy-lang/smithy-php-<pkg>` (async, core, codec,
http, protocol, protocol-aws, client, codegen). They are **empty, public,
read-only mirror repos** that an org admin must create before the first real
release - see `REPO-REQUEST.md` for the ready-to-send request, and `TAGGING.md`
for Packagist registration and deploy keys.

## Future: trusted publishing

Composer [trusted publishing](https://blog.packagist.com/trusted-publishing/)
is coming and will let Packagist pull a package straight from a monorepo
subdirectory via OIDC, removing the need for the 8 mirror repos and the
subtree-split/force-push mechanism entirely. This subtree approach is the
**interim** path until trusted publishing is generally available; do not build
for it yet.
