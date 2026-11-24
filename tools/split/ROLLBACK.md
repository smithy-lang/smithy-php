# Rollback runbook - undo a bad smithy-php release

Status: **DRAFT**. Short checklist to retract a release that shipped broken.
Order matters: clean up the tags first, then stop new installs, then fix forward.

## 0. Decide: yank vs. patch

- Broken code, people already installing it -> **yank** (below), then fix forward.
- Minor issue, nobody depends on it yet -> just ship a higher tag; skip the yank.

Set these once in your shell. Every command below uses them.

```
TAG=0.1.0                                   # the yanked tag
SPLIT_ORG=smithy-lang
MONOREPO_URL=git@github.com:smithy-lang/smithy-php.git
```

## 1. Delete the tag from the 9 split repos

Do this before step 2: while a tag exists on a mirror, Packagist can import
the deleted version again on its next update.

```
for slug in async core codec http auth protocol protocol-aws client codegen; do
  git push "git@github.com:${SPLIT_ORG}/smithy-php-${slug}.git" \
    ":refs/tags/${TAG}"
done
```

- [ ] Confirm each tag is gone: `git ls-remote <remote> refs/tags/${TAG}`
      returns nothing.

## 2. Stop new installs (human-only, Packagist)

For EACH of the 9 packages on packagist.org:

- [ ] Open `packagist.org/packages/smithy/<pkg>`.
- [ ] Delete the bad version (e.g. `0.1.0`), OR abandon the package if
      the whole release is bad. **This is a human action** - there is no API
      token in this tooling and it is intentionally manual.
- [ ] Check the version stays gone after the next Packagist update.

Note: deleting a Packagist version does not uninstall it for anyone who already
pinned it; it only prevents new resolution. That is why we ship a fixed higher
tag afterwards.

## 3. Delete the tag from the monorepo

`origin` is often a fork. Use the canonical URL (or a remote named `upstream`
that points at it):

```
git push "${MONOREPO_URL}" ":refs/tags/${TAG}"   # delete remote tag
git tag -d "${TAG}"                               # delete local tag
```

- [ ] Confirm: `git ls-remote "${MONOREPO_URL}" refs/tags/${TAG}` is empty.

## 4. Retire the tag, and revert the release commit if needed

Add the tag to `tools/split/RETIRED_TAGS`; `split.sh` refuses every tag listed
there. If the release commit itself is bad (not just the tag), revert it in
the same change:

```
echo "${TAG}" >> tools/split/RETIRED_TAGS
git revert <release-sha>     # preferred: keeps history
git add tools/split/RETIRED_TAGS
git commit -m "build: retire ${TAG}"
```

Land it through a PR. Do NOT force-push `main` to erase the commit; revert
instead.

## 5. Fix forward

- [ ] Fix the defect on `main`.
- [ ] Tag a NEW higher version (e.g. `0.1.1`). `split.sh` refuses a retired
      tag; re-using a deleted tag confuses Packagist and consumers.
- [ ] Let `split.yml` propagate it; verify one clean install.

## Mirror `main` after a rollback

Steps 1-4 delete tags only. Each mirror's `main` keeps the bad split until the
next release fast-forwards it, so `dev-main` on Packagist serves the bad code
in the meantime. GitHub's README-only "Initial commit" is not restored. Ship
the fix (step 5) promptly.

## First push and a stopped push

The first release replaces each mirror's README-only "Initial commit". The
lease is pinned to that commit's SHA, so the push only lands while `main` still
holds exactly that commit. The replaced commit holds only GitHub's generated
README, and the job log names its SHA ("replacing README-only initial commit
<sha>"). A bad first release is rolled back with steps 1-5 above, the same as
any other release.

When `split.sh` exits with code 4, the mirror's `main` or tag holds something
the split did not produce. Compare the mirror with the monorepo, decide which
commit is right, and move the mirror by hand with a lease pinned to the SHA it
holds now. With `<held-sha>` the mirror's current `main` and `<split-sha>` the
split commit the job printed, run this from a clone that has both commits:

```
git push --force-with-lease=main:<held-sha> \
  "git@github.com:${SPLIT_ORG}/smithy-php-<slug>.git" \
  <split-sha>:refs/heads/main
```

The push only succeeds while `main` still holds `<held-sha>`. Then retry the
release (below).

## Retry a release

**Actions -> Split packages -> Run workflow**, then enter the existing tag,
e.g. `0.1.0`. The tag must already exist in the monorepo. Mirrors that already
hold the split and tag are left alone; the rest are pushed.

By hand: check out the monorepo and run
`TAG=<tag> PUSH=1 bash tools/split/split.sh`. It splits the commit the tag
points at. A local run stops at the first failed mirror (the workflow runs all
9 independently). Fix the cause and run the same command again; a rerun is
safe.

## Verify (clean-room install probe)

```
TMP=$(mktemp -d)
composer -d "$TMP" require "smithy/client:<good-version>" --no-interaction
php -r 'require "'"$TMP"'/vendor/autoload.php"; echo "autoload OK\n";'
```

Green here = the split tag resolves from Packagist with PSR-4 working.
