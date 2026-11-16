# Rollback runbook - undo a bad smithy-php release

Status: **DRAFT**. Short checklist to retract a release that shipped broken.
Order matters: stop new installs first, then clean up refs, then fix forward.

## 0. Decide: yank vs. patch

- Broken code, people already installing it -> **yank** (below), then fix forward.
- Minor issue, nobody depends on it yet -> just ship a higher tag; skip the yank.

## 1. Stop new installs (human-only, Packagist)

For EACH of the 8 packages on packagist.org:

- [ ] Open `packagist.org/packages/smithy/<pkg>`.
- [ ] Delete the bad version (e.g. `v0.1.0-preview`), OR abandon the package if
      the whole release is bad. **This is a human action** - there is no API
      token in this tooling and it is intentionally manual.

Note: deleting a Packagist version does not uninstall it for anyone who already
pinned it; it only prevents new resolution. That is why we ship a fixed higher
tag afterwards.

## 2. Delete the tag from the 8 split repos

Per split repo (scriptable; `$SPLIT_ORG` is the real org):

```
for slug in async core codec http protocol protocol-aws client codegen; do
  git push "git@github.com:${SPLIT_ORG}/smithy-php-${slug}.git" \
    :refs/tags/v0.1.0-preview
done
```

- [ ] Confirm each tag is gone: `git ls-remote --tags <remote> | grep v0.1.0-preview`
      returns nothing.

## 3. Delete the tag from the monorepo

```
git push origin :refs/tags/v0.1.0-preview   # delete remote tag
git tag -d v0.1.0-preview                    # delete local tag
```

- [ ] Confirm: `git ls-remote --tags origin | grep v0.1.0-preview` is empty.

## 4. Revert the release commit if needed

If the release commit itself is bad (not just the tag):

```
git revert <release-sha>     # preferred: keeps history
git push origin main
```

Do NOT force-push `main` to erase the commit; revert instead.

## 5. Fix forward

- [ ] Fix the defect on `main`.
- [ ] Tag a NEW higher version (e.g. `v0.1.1-preview`). Never re-use the yanked
      tag string - re-using a deleted tag confuses Packagist and consumers.
- [ ] Let `split.yml` propagate it; verify one clean install.

## Verify (clean-room install probe)

```
TMP=$(mktemp -d)
composer -d "$TMP" require "smithy/client:<good-version>" --no-interaction
php -r 'require "'"$TMP"'/vendor/autoload.php"; echo "autoload OK\n";'
```

Green here = the split tag resolves from Packagist with PSR-4 working.
