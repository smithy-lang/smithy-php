# Repo creation request: smithy-php split mirrors

**To:** `smithy-lang` org admins / open-source request
**From:** smithy-php maintainers
**Ask:** create 8 empty public repositories (details below)

## Why

To publish smithy-php on public Packagist, each Composer package needs its own
Git repository (public Packagist does not support monorepo-subdirectory
publishing; that is Private-Packagist-only). We split the `smithy-lang/smithy-php`
monorepo into 8 per-package histories and publish each from its own mirror repo.

These 8 repos are **read-only mirrors**. The monorepo `smithy-lang/smithy-php`
remains the single source of truth and keeps all development. Release tooling
(`git subtree split` / splitsh-lite in CI) force-pushes the computed branch and
matching version tag into each mirror on every `v*` tag. Nobody commits to a
mirror directly.

## Please create

- **Org:** `smithy-lang`
- **Visibility:** public
- **Contents:** empty (no README/license/gitignore; the first split push
  populates them)

| Repo | Purpose |
|------|---------|
| `smithy-lang/smithy-php-async` | `smithy/async` package mirror |
| `smithy-lang/smithy-php-core` | `smithy/core` package mirror |
| `smithy-lang/smithy-php-codec` | `smithy/codec` package mirror |
| `smithy-lang/smithy-php-http` | `smithy/http` package mirror |
| `smithy-lang/smithy-php-protocol` | `smithy/protocol` package mirror |
| `smithy-lang/smithy-php-protocol-aws` | `smithy/protocol-aws` package mirror |
| `smithy-lang/smithy-php-client` | `smithy/client` package mirror |
| `smithy-lang/smithy-php-codegen` | `smithy/codegen` package mirror |

## Notes

- Please enable write access for the release workflow (a deploy key per repo, or
  an org machine account); the maintainers will add the public keys.
- Suggested repo description per mirror: "Read-only mirror of
  smithy-lang/smithy-php `<pkg>`. Do not open PRs here; develop in the monorepo."
- A future Composer **trusted publishing** flow is expected to remove the need
  for these mirrors; until it ships, these 8 repos are required.
