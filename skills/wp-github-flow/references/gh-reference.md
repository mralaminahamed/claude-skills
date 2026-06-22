# GitHub CLI (`gh`) Reference

Quick reference for issue + PR work.

> **Always resolve canonical repo(s) first** — see SKILL.md §1.
> The issue repo and the code/PR repo can differ. Set:
> ```bash
> ISSUE_REPO=<owner/repo where the issue lives>
> CODE_REPO=$(gh repo view --json nameWithOwner -q .nameWithOwner)   # where you PR
> ```
> Issue commands use `--repo "$ISSUE_REPO"`; branch/push/PR use `CODE_REPO`. (When they're the same, both vars hold the same value.)

---

## Remote Verification

```bash
# Compare git remote vs actual GitHub repo
git remote get-url origin
gh repo view --json nameWithOwner -q .nameWithOwner

# If they differ — update origin
git remote set-url origin $(gh repo view --json cloneUrl -q .cloneUrl)
```

---

## Issues

```bash
gh issue view <number> --repo "$ISSUE_REPO" --comments        # read the thread too
gh issue list --repo "$ISSUE_REPO" --state open --limit 20
gh issue edit <number> --repo "$ISSUE_REPO" --add-label "<verified-label>"
gh issue edit <number> --repo "$ISSUE_REPO" --add-assignee <username>
gh issue comment <number> --repo "$ISSUE_REPO" --body "..."
gh issue close <number> --repo "$ISSUE_REPO"

# Parent / tracking issue → enumerate sub-issues, then fetch each
gh issue view <number> --repo "$ISSUE_REPO"                   # lists sub-issues
```

---

## Pull Requests

```bash
# 0) Push the branch first — gh pr create fails otherwise
git push -u origin <branch>

# 1) Discover labels — an unknown --label ABORTS the whole create
gh label list --repo "$CODE_REPO"

# 2) Create — --repo is CODE_REPO; only labels that exist; cross-repo Closes if needed
gh pr create \
  --repo "$CODE_REPO" \
  --base <target-branch> \
  --assignee @me \
  --label "<verified-qa-label>" \
  --label "<verified-area-label>" \
  --title "fix(scope): summary" \
  --body "$(cat <<'EOF'
<body — use "Closes ISSUE_OWNER/ISSUE_REPO#N" when issue repo ≠ code repo>
EOF
)"

gh pr view <number> --repo "$CODE_REPO"
gh pr checks <number> --repo "$CODE_REPO"
gh pr edit <number> --repo "$CODE_REPO" --title "new title" --add-label "<verified>"
gh pr comment <number> --repo "$CODE_REPO" --body "..."
gh pr list --repo "$CODE_REPO" --head <branch>
gh pr merge <number> --repo "$CODE_REPO" --squash
```

---

## Branch Naming

Pattern: `<prefix>/<issue-number>-<short-description>`. Common prefixes: `feature`, `bugfix`, `hotfix`, `ci`, `release`, `refactor`, `docs`, `chore`. **Confirm the repo's allowed prefixes** — some CI jobs reject `fix/` and require `bugfix/`.

```bash
# ✅
bugfix/247-recently-viewed-editor-preview
feature/add-coupon-bulk-apply
# ❌
fix/247-...   # some repos reject 'fix' — use 'bugfix'
```

---

## Labels — discover, never hardcode

Label names vary per repo and a wrong `--label` aborts `gh pr create`. **Always `gh label list --repo "$CODE_REPO"` first**, then map intent → actual name.

| Intent | Seen as (varies!) |
|-------|------------|
| Ready for QA | `needs-testing` · `needs testing` · `ready-for-review` |
| Something broken | `bug` |
| Frontend / backend area | `frontend` · `backend` (may not exist in all repos) |
| QA outcome | `testing done` / `testing failed` / `testing ongoing` |
| Priority | `urgent` · `high-priority` |

> Label names are not standardized across WP repos. Always `gh label list` first — a single wrong `--label` aborts the entire `gh pr create` command.

---

## PR Template Sections

Many repos enforce a PR-body template via CI (ShopFlow's `validate-template` job checks all of these; other repos may not). Filling them is good practice regardless:

| Section | Requirement |
|---------|-------------|
| `## 📝 Summary` | >15 chars of user content |
| `## 🛠️ Related Issues` | `Closes #NNN` or `Fixes #NNN` |
| `## 📦 Type of Change` | At least one `- [x]` checked |
| `## 🔍 Changes Made` | >15 chars of user content |
| `## 🧪 Test Instructions` | >15 chars of user content |
| `## ✅ Checklist` | At least one `- [x]` checked |

---

## Workflow Failures — Common Causes

(ShopFlow-style CI; adapt to the target repo's checks.)


| Check | Common failure | Fix |
|-------|---------------|-----|
| Validate Branch Name | Wrong prefix (`fix/` instead of `bugfix/`) | Rename branch, recreate PR |
| Validate PR Template | Missing/empty required sections | Fill all 6 sections per table above |
| Validate PR Title | Not Conventional Commits format | `fix(scope): summary` |
| Validate Commit Messages | Non-conventional commit in PR | `git rebase -i` to reword |
