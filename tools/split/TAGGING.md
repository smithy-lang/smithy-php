# Tagging & release flow (smithy-php monorepo -> 8 split repos)

Status: **DRAFT**. Describes how one monorepo tag becomes one version across
all 8 published Composer packages.

## Target repos

The 8 publish targets are read-only **mirror** repos under the `smithy-lang`
org, created by an org admin before the first release (see `REPO-REQUEST.md`):

| Composer package | Split repo |
|------------------|------------|
| `smithy/async` | `smithy-lang/smithy-php-async` |
| `smithy/core` | `smithy-lang/smithy-php-core` |
| `smithy/codec` | `smithy-lang/smithy-php-codec` |
| `smithy/http` | `smithy-lang/smithy-php-http` |
| `smithy/protocol` | `smithy-lang/smithy-php-protocol` |
| `smithy/protocol-aws` | `smithy-lang/smithy-php-protocol-aws` |
| `smithy/client` | `smithy-lang/smithy-php-client` |
| `smithy/codegen` | `smithy-lang/smithy-php-codegen` |

The monorepo `smithy-lang/smithy-php` stays the single source of truth and keeps
all development; the split tooling force-pushes the computed branch and tag into
these mirrors. Nobody commits to a mirror directly.

## How the 8 packages share one version

Every package's `composer.json` requires its siblings with `"self.version"`:

```
"smithy/core": "self.version"
```

`self.version` means "the exact version of the package being installed". So
when all 8 packages are tagged `v0.1.0-preview`, each one requires its siblings
at `v0.1.0-preview` automatically. There is no per-package version to bump by
hand; the single monorepo tag is the one source of truth.

While developing in the monorepo, packages resolve to each other through the
root autoload (one tree, one version), so `self.version` is never evaluated
locally. It only matters once the packages are split and published.

## Release steps

1. **Pick the version.** One semver tag for the whole release, e.g.
   `v0.1.0-preview`. Pre-release suffixes (`-preview`, `-alpha.N`, `-rc.N`) are
   Packagist-visible but not installed by default constraints - good for a
   developer preview.

2. **Tag the monorepo** on the release commit:

   ```
   git tag -a v0.1.0-preview -m "smithy-php developer preview 0.1.0"
   git push origin v0.1.0-preview
   ```

3. **The tag push triggers `.github/workflows/split.yml`** (guarded to the
   canonical repo). For each package it:
   - computes the subtree split for `src/<Pkg>`,
   - force-pushes the split history to `main` of the per-package repo,
   - pushes the SAME tag (`v0.1.0-preview`) to that repo.

4. **Packagist publishes each tag.** Each split repo is registered on Packagist
   with a GitHub webhook (or auto-update), so the new tag appears as an
   installable version within a minute. `composer require smithy/core:^0.1`
   now resolves from Packagist with no path repositories.

5. **Verify** one clean install (see ROLLBACK.md "verify" for the exact probe):
   ```
   composer require smithy/client:v0.1.0-preview --dry-run
   ```

## Propagation summary

```
monorepo tag v0.1.0-preview
        |
        v  (split.yml, one matrix leg per package)
  8 split repos each get: main branch updated + tag v0.1.0-preview
        |
        v  (Packagist webhook per repo)
  8 Packagist packages each expose version v0.1.0-preview
```

## Who needs to act (humans)

- **Tag/release authority:** a maintainer with push access to the upstream
  monorepo creates and pushes the version tag.
- **Packagist:** registering the 8 packages and submitting/yanking versions is
  a human action on packagist.org (see ROLLBACK.md).
- **Split repos + deploy keys:** the 8 `smithy-lang/smithy-php-<pkg>` mirror
  repos, and one write-deploy-key secret per repo, must exist before the
  workflow is enabled.

## Future: trusted publishing

Composer [trusted publishing](https://blog.packagist.com/trusted-publishing/)
(currently "coming soon") will let Packagist pull each package straight from a
monorepo subdirectory over OIDC - no mirror repos, no subtree split, no
force-push, no per-repo deploy keys. When it lands it **replaces** the
subtree-split/push mechanism documented here. Until then, this subtree flow is
the interim path. Do not build for trusted publishing yet; just know it is the
intended destination.
