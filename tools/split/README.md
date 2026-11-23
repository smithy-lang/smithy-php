# Monorepo split + tagging tooling (DRAFT)

Splits the smithy-php monorepo into 9 standalone, read-only Composer packages so
`composer require smithy/<pkg>` works from Packagist, all sharing one tag.

| File | Purpose |
|------|---------|
| `split.sh` | Split each `src/<Pkg>` into a standalone history and (optionally) push to a per-package repo. splitsh-lite primary, `git subtree split` fallback. |
| `packages.map` | The 9 package rows: src dir -> Composer name -> split repo slug. |
| `.github/workflows/split.yml` | Runs the split when a semver tag such as `0.1.0` is pushed, or by hand for an existing tag; guarded to the canonical repo. |
| `TAGGING.md` | How one monorepo tag becomes one version across all 9 packages. |
| `ROLLBACK.md` | Checklist to retract a bad release. |

## Quick start (dry run, nothing pushed)

```
MONOREPO=/path/to/monorepo bash tools/split/split.sh
```

The org defaults to `smithy-lang`; override with `SPLIT_ORG=my-org` for a
scratch rehearsal. Set `PUSH=1` only in CI / a real release.

## Target repos

The 9 split targets are `smithy-lang/smithy-php-<pkg>` (async, core, codec,
http, auth, protocol, protocol-aws, client, codegen). They are read-only mirror
repos with a `main` branch. Each one starts with a single README-only
"Initial commit" from GitHub. See `TAGGING.md` for Packagist registration and
deploy keys.

## How a push works

With `PUSH=1`, `split.sh` reads the mirror's `main` before it pushes:

| Remote `main` | Action |
|---------------|--------|
| missing | plain push |
| equal to the split | nothing to do |
| ancestor of the split | plain push (fast-forward) |
| one parentless commit holding only `README.md` | `--force-with-lease=main:<that sha>` |
| anything else | stop with exit code 4 |

The lease row covers the first release only: it replaces GitHub's
"Initial commit" and nothing else. The split is deterministic, so every later
release is a fast-forward. Tags follow the same idea: a missing tag is pushed,
a matching tag is left alone, and a tag at another commit stops the run.

## Retry a release

When a split job fails partway (for example a missing deploy key), fix the
cause and run it again for the same tag: **Actions -> Split packages -> Run
workflow**, then enter the existing tag, e.g. `0.1.0`. The tag must already
exist in the monorepo. Mirrors that already hold the split and tag are left
alone; the rest are pushed.

## Future: trusted publishing

Composer [trusted publishing](https://blog.packagist.com/trusted-publishing/)
is coming and will let Packagist pull a package straight from a monorepo
subdirectory via OIDC, removing the need for the 9 mirror repos and the
subtree-split/push mechanism entirely. This subtree approach is the
**interim** path until trusted publishing is generally available; do not build
for it yet.
