# Tagging & release flow (smithy-php monorepo -> 9 split repos)

Status: **DRAFT**. Describes how one monorepo tag becomes one version across
all 9 published Composer packages.

## Target repos

The 9 publish targets are read-only **mirror** repos under the `smithy-lang`
org, created by an org admin before the first release:

| Composer package | Split repo |
|------------------|------------|
| `smithy/async` | `smithy-lang/smithy-php-async` |
| `smithy/core` | `smithy-lang/smithy-php-core` |
| `smithy/codec` | `smithy-lang/smithy-php-codec` |
| `smithy/http` | `smithy-lang/smithy-php-http` |
| `smithy/auth` | `smithy-lang/smithy-php-auth` |
| `smithy/protocol` | `smithy-lang/smithy-php-protocol` |
| `smithy/protocol-aws` | `smithy-lang/smithy-php-protocol-aws` |
| `smithy/client` | `smithy-lang/smithy-php-client` |
| `smithy/codegen` | `smithy-lang/smithy-php-codegen` |

The monorepo `smithy-lang/smithy-php` stays the single source of truth and keeps
all development; the split tooling pushes the computed branch and tag into
these mirrors as fast-forwards (see "How a push works" in README.md). Nobody
commits to a mirror directly.

## How the 9 packages share one version

Every package's `composer.json` requires its siblings with `"self.version"`:

```
"smithy/core": "self.version"
```

`self.version` means "the exact version of the package being installed". So
when all 9 packages are tagged `0.1.0`, each one requires its siblings
at `0.1.0` automatically. There is no per-package version to bump by
hand; the single monorepo tag is the one source of truth.

While developing in the monorepo, packages resolve to each other through the
root autoload (one tree, one version), so `self.version` is never evaluated
locally. It only matters once the packages are split and published.

## Release steps

1. **Pick the version.** One semver tag for the whole release, e.g.
   `0.1.0`. Composer pre-release suffixes (`-alpha.N`, `-beta.N`, `-RC.N`) are
   Packagist-visible but not installed by default constraints. Use only those
   suffixes: Composer reads the stability from them.

2. **Tag the monorepo** on the release commit:

   ```
   git tag -a 0.1.0 -m "smithy-php developer preview 0.1.0"
   git push origin 0.1.0
   ```

3. **The tag push triggers `.github/workflows/split.yml`** (guarded to the
   canonical repo). For each package it:
   - computes the subtree split for `src/<Pkg>`,
   - pushes the split history to `main` of the per-package repo (a
     fast-forward; the first release replaces the README-only initial commit
     with a lease pinned to its SHA),
   - pushes the SAME tag (`0.1.0`) to that repo.

4. **Packagist publishes each tag.** Each split repo is registered on Packagist
   with a GitHub webhook (or auto-update), so the new tag appears as an
   installable version within a minute. `composer require smithy/core:^0.1`
   now resolves from Packagist with no path repositories.
   Register only the 9 mirror packages. Do not register the monorepo
   `smithy/smithy-php`; it ships tests and tooling.

5. **Verify** one clean install (see ROLLBACK.md "verify" for the exact probe):
   ```
   composer require smithy/client:0.1.0 --dry-run
   ```

## Propagation summary

```
monorepo tag 0.1.0
        |
        v  (split.yml, one matrix leg per package)
  9 split repos each get: main branch updated + tag 0.1.0
        |
        v  (Packagist webhook per repo)
  9 Packagist packages each expose version 0.1.0
```

## Who needs to act (humans)

- **Tag/release authority:** a maintainer with push access to the upstream
  monorepo creates and pushes the version tag.
- **Packagist:** registering the 9 packages and submitting/yanking versions is
  a human action on packagist.org (see ROLLBACK.md).
- **Split repos + deploy keys:** the 9 `smithy-lang/smithy-php-<pkg>` mirror
  repos, and one write-deploy-key secret per repo, must exist before the
  workflow is enabled.

## Future: trusted publishing

Composer [trusted publishing](https://blog.packagist.com/trusted-publishing/)
(currently "coming soon") will let Packagist pull each package straight from a
monorepo subdirectory over OIDC - no mirror repos, no subtree split, no
per-repo deploy keys. When it lands it **replaces** the
subtree-split/push mechanism documented here. Until then, this subtree flow is
the interim path. Do not build for trusted publishing yet; just know it is the
intended destination.
