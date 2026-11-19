#!/usr/bin/env bash
#
# split.sh - Split the smithy-php monorepo into per-package histories and push
#            each to its own read-only repository.
#
# Primary tool:  splitsh-lite (fast C reimplementation of `git subtree split`,
#                the de-facto standard used by Symfony/Laravel). Auto-detected.
# Fallback:      `git subtree split` (ships with git, portable, slower). Used
#                automatically when splitsh-lite is not on PATH.
#
# This script NEVER pushes unless PUSH=1 is set in the environment. By default
# it performs the split locally and prints what it WOULD push. The target org
# defaults to "smithy-lang" (the canonical home of the 9 split repos) but can
# be overridden via SPLIT_ORG, e.g. for a scratch-repo rehearsal.
#
# Usage:
#   ./split.sh                                  # local split, org=smithy-lang
#   SPLIT_ORG=my-org ./split.sh                 # local split against another org
#   TAG=v0.1.0 ./split.sh                       # local split for a specific tag
#   PUSH=1 ./split.sh                           # actually push (CI / release)
#
# Environment:
#   SPLIT_ORG   (optional) GitHub org/user that owns the per-package repos.
#               Default: smithy-lang.
#   TAG         (optional) Tag to propagate to each split repo. Defaults to the
#               tag pointing at HEAD, if any.
#   BRANCH      (optional) Branch to push the split to. Default: main.
#   PUSH        (optional) Set to 1 to actually push. Default: 0 (dry run).
#   REMOTE_BASE (optional) Override the remote URL template. Default:
#               git@github.com:${SPLIT_ORG}/<slug>.git
#               Used by the rehearsal to point at scratch bare repos.
#   MONOREPO    (optional) Path to the monorepo git checkout to split from.
#               Default: the repo containing this script.
#   WORK_DIR    (optional) Scratch dir for per-package working clones.
#               Default: a mktemp dir, removed on exit.
#   SPLIT_METHOD(optional) Force 'splitsh' or 'subtree'. Default: auto-detect.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAP_FILE="${MAP_FILE:-${SCRIPT_DIR}/packages.map}"

# --- resolve the monorepo checkout -----------------------------------------
MONOREPO="${MONOREPO:-$(git -C "${SCRIPT_DIR}" rev-parse --show-toplevel)}"
cd "${MONOREPO}"

BRANCH="${BRANCH:-main}"
PUSH="${PUSH:-0}"
SPLIT_ORG="${SPLIT_ORG:-smithy-lang}"
REMOTE_BASE="${REMOTE_BASE:-git@github.com:__ORG__/__SLUG__.git}"

if [[ -z "${SPLIT_ORG}" && "${REMOTE_BASE}" == *"__ORG__"* ]]; then
  echo "ERROR: SPLIT_ORG resolved empty and REMOTE_BASE needs it." >&2
  echo "       Set SPLIT_ORG=<github-org> or unset it to use the default." >&2
  exit 2
fi

# --- resolve the tag to propagate -------------------------------------------
TAG="${TAG:-$(git describe --tags --exact-match 2>/dev/null || true)}"
if [[ -z "${TAG}" ]]; then
  echo "NOTICE: no tag at HEAD and TAG not set; splitting branches only, no tag push."
fi

# --- choose the split method ------------------------------------------------
SPLIT_METHOD="${SPLIT_METHOD:-auto}"
if [[ "${SPLIT_METHOD}" == "auto" ]]; then
  if command -v splitsh-lite >/dev/null 2>&1; then
    SPLIT_METHOD="splitsh"
  else
    SPLIT_METHOD="subtree"
  fi
fi
echo "=== split method: ${SPLIT_METHOD} (splitsh-lite primary, git subtree fallback) ==="
if [[ "${SPLIT_METHOD}" == "subtree" ]]; then
  echo "    NOTE: splitsh-lite not in use; git subtree split preserves history but is slower."
fi

# --- scratch work dir for per-package clones --------------------------------
CLEANUP_WORK=0
if [[ -z "${WORK_DIR:-}" ]]; then
  WORK_DIR="$(mktemp -d "${KIROCREW_SCRATCH:-/tmp}/smithy-split.XXXXXX")"
  CLEANUP_WORK=1
fi
mkdir -p "${WORK_DIR}"
cleanup() { [[ "${CLEANUP_WORK}" == "1" ]] && rm -rf "${WORK_DIR}"; }
trap cleanup EXIT

echo "=== monorepo:   ${MONOREPO}"
echo "=== split org:  ${SPLIT_ORG}"
echo "=== branch:     ${BRANCH}"
echo "=== tag:        ${TAG:-<none>}"
echo "=== push:       ${PUSH} (0 = dry run, nothing is pushed)"
echo "=== work dir:   ${WORK_DIR}"
echo

# --- produce one split per package ------------------------------------------
split_count=0
skipped_count=0

while read -r subdir pkg slug || [[ -n "${subdir}" ]]; do
  # skip comments and blank lines
  [[ -z "${subdir}" || "${subdir}" == \#* ]] && continue

  prefix="src/${subdir}"
  if [[ ! -d "${prefix}" ]]; then
    echo "SKIP  ${pkg}: ${prefix} not present in working tree (graceful degrade)."
    skipped_count=$((skipped_count + 1))
    continue
  fi
  if [[ ! -f "${prefix}/composer.json" ]]; then
    echo "SKIP  ${pkg}: ${prefix}/composer.json missing; not a package dir."
    skipped_count=$((skipped_count + 1))
    continue
  fi

  echo "---- splitting ${pkg}  (${prefix} -> ${slug}) ----"

  # Compute the split commit SHA for this prefix.
  if [[ "${SPLIT_METHOD}" == "splitsh" ]]; then
    sha="$(splitsh-lite --prefix="${prefix}/" --quiet)"
  else
    # git subtree split: --branch is avoided so the main repo gets no extra refs.
    sha="$(git subtree split --prefix="${prefix}")"
  fi
  echo "      split commit: ${sha}"

  # Build a standalone clone that contains ONLY the split history.
  pkg_clone="${WORK_DIR}/${slug}"
  rm -rf "${pkg_clone}"
  git init -q "${pkg_clone}"
  git -C "${pkg_clone}" fetch -q "${MONOREPO}" "${sha}"
  git -C "${pkg_clone}" branch -q -f "${BRANCH}" "${sha}"
  git -C "${pkg_clone}" checkout -q "${BRANCH}"

  # Sanity: composer.json must be at the ROOT of the split tree.
  if [[ ! -f "${pkg_clone}/composer.json" ]]; then
    echo "ERROR: ${pkg}: composer.json not at split root; aborting." >&2
    exit 3
  fi

  # Resolve the destination remote for this package.
  remote="${REMOTE_BASE/__ORG__/${SPLIT_ORG}}"
  remote="${remote/__SLUG__/${slug}}"
  git -C "${pkg_clone}" remote add origin "${remote}"

  # Tag the split commit so every package shares ONE version string.
  if [[ -n "${TAG}" ]]; then
    git -C "${pkg_clone}" tag -f "${TAG}" "${BRANCH}" >/dev/null
  fi

  if [[ "${PUSH}" == "1" ]]; then
    echo "      PUSH  ${remote}  (${BRANCH}${TAG:+ + ${TAG}})"
    git -C "${pkg_clone}" push --force origin "${BRANCH}"
    [[ -n "${TAG}" ]] && git -C "${pkg_clone}" push --force origin "refs/tags/${TAG}"
  else
    echo "      DRY RUN: would push ${BRANCH}${TAG:+ + tag ${TAG}} to ${remote}"
  fi

  split_count=$((split_count + 1))
  echo
done < "${MAP_FILE}"

echo "=== done: ${split_count} package(s) split, ${skipped_count} skipped ==="
