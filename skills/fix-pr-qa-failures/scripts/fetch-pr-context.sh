#!/usr/bin/env bash
#
# fetch-pr-context.sh — Step 1 of fix-pr-qa-failures in one command.
#
# Prints PR metadata, current labels, and the full QA comment thread
# (newest last) so you can read the LATEST QA feedback at a glance.
#
# Usage:
#   ./fetch-pr-context.sh <pr-number> [owner/repo]
#   ./fetch-pr-context.sh 362 codexpertio/wc-affiliate
#
# If owner/repo is omitted, gh infers it from the current repo.

set -euo pipefail

PR="${1:?Usage: fetch-pr-context.sh <pr-number> [owner/repo]}"
REPO_FLAG=()
[ "${2:-}" ] && REPO_FLAG=(--repo "$2")

command -v gh >/dev/null  || { echo "gh CLI not found" >&2; exit 1; }
command -v jq >/dev/null  || { echo "jq not found" >&2; exit 1; }

echo "============================================================"
echo " PR #$PR — metadata"
echo "============================================================"
gh pr view "$PR" "${REPO_FLAG[@]}" \
  --json title,author,state,baseRefName,headRefName,additions,deletions,changedFiles,labels \
  | jq -r '
    "Title:    \(.title)",
    "Author:   \(.author.login)",
    "State:    \(.state)",
    "Branch:   \(.headRefName) -> \(.baseRefName)",
    "Diff:     +\(.additions) -\(.deletions) across \(.changedFiles) files",
    "Labels:   \([.labels[].name] | join(", "))"
  '

echo
echo "============================================================"
echo " QA comment thread (oldest first — READ THE LAST ONE)"
echo "============================================================"
gh pr view "$PR" "${REPO_FLAG[@]}" --json comments \
  | jq -r '.comments[]
      | "\n--- @\(.author.login)  (\(.createdAt)) ---\n\(.body)"'

echo
echo "============================================================"
echo " Reviews"
echo "============================================================"
gh pr view "$PR" "${REPO_FLAG[@]}" --json reviews \
  | jq -r 'if (.reviews | length) == 0 then "(none)"
           else .reviews[] | "\n--- @\(.author.login)  [\(.state)] ---\n\(.body)"
           end'

echo
echo "Next: checkout the branch, then read references/root-cause-patterns.md"
