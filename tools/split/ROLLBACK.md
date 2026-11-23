# Rollback runbook - undo a bad smithy-php release

Status: **DRAFT**. Short checklist to retract a release that shipped broken.
Order matters: stop new installs first, then clean up refs, then fix forward.

## 0. Decide: yank vs. patch

- Broken code, people already installing it -> **yank** (below), then fix forward.
- Minor issue, nobody depends on it yet -> just ship a higher tag; skip the yank.

## 1. Stop new installs (human-only, Packagist)

For EACH of the 9 packages on packagist.org:

- [ ] Open `packagist.org/packages/smithy/<pkg>`.
- [ ] Delete the bad version (e.g. `0.1.0`), OR abandon the package if
      the whole release is bad. **This is a human action** - there is no API
      token in this tooling and it is intentionally manual.

Note: deleting a Packagist version does not uninstall it for anyone who already
pinned it; it only prevents new resolution. That is why we ship a fixed higher
tag afterwards.

## 2. Delete the tag from the 9 split repos

Per split repo (scriptable; `$SPLIT_ORG` is the real org):

```
for slug in async core codec http auth protocol protocol-aws client codegen; do
  git push "git@github.com:${SPLIT_ORG}/smithy-php-${slug}.git" \
    :refs/tags/0.1.0
done
```

- [ ] Confirm each tag is gone: `git ls-remote <remote> refs/tags/0.1.0`
      returns nothing.

## 3. Delete the tag from the monorepo

```
git push origin :refs/tags/0.1.0   # delete remote tag
git tag -d 0.1.0                   # delete local tag
```

- [ ] Confirm: `git ls-remote origin refs/tags/0.1.0` is empty.

## 4. Revert the release commit if needed

If the release commit itself is bad (not just the tag):

```
git revert <release-sha>     # preferred: keeps history
git push origin main
```

Do NOT force-push `main` to erase the commit; revert instead.

## 5. Fix forward

- [ ] Fix the defect on `main`.
- [ ] Tag a NEW higher version (e.g. `0.1.1`). Never re-use the yanked
      tag string - re-using a deleted tag confuses Packagist and consumers.
- [ ] Let `split.yml` propagate it; verify one clean install.

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
holds now. Then re-run the release job.

## Verify (clean-room install probe)

```
TMP=$(mktemp -d)
composer -d "$TMP" require "smithy/client:<good-version>" --no-interaction
php -r 'require "'"$TMP"'/vendor/autoload.php"; echo "autoload OK\n";'
```

Green here = the split tag resolves from Packagist with PSR-4 working.
