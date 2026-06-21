---
description: Extract learnings from the current session and update wp-dev-skills skill files — captures corrections, new rules, edge cases, and Common Mistakes entries discovered during real usage. Use `suggest` to see WP dev topics not yet covered by any skill.
argument-hint: "[preview | apply | suggest | skill:<name>]"
---

# WP Dev Skills — Learn from Session

Scan the current conversation for corrections, new rules, and edge cases, then propose and apply updates to the relevant skill files in `skills/*/SKILL.md`.

Action: $ARGUMENTS (defaults to `apply`)

| Mode | Behaviour |
|------|-----------|
| `apply` | Scan session → show updates table + new skill candidates → confirm → apply edits → ship (owner: direct commit; contributor: fork + branch-per-skill + PR) |
| `preview` | Show updates table + new skill candidates, no file edits |
| `suggest` | Skip session scan, only show new skill candidates from gap analysis |
| `skill:<name>` | Limit session scan and updates to one skill; still show candidates |

---

## What counts as a learning

Look for any of these signals in the current conversation:

- **Explicit corrections** — user said "never do X", "don't add Y", "stop doing Z"
- **New rules** — behavior the user confirmed should always/never happen going forward
- **Edge cases** — situations where the skill's existing guidance was incomplete or ambiguous
- **Common Mistakes** — things that went wrong or nearly went wrong that aren't already listed
- **Label / workflow rules** — QA vs dev responsibilities, process boundaries, triage ownership
- **Scope clarifications** — what a skill covers vs does not cover

Skip: ephemeral task details, in-progress work state, preferences specific to one project.

---

## Steps

### 1. Scan conversation

Read the full conversation history. For each learning found, extract:
- **Signal**: exact quote or paraphrase that triggered the rule
- **Rule**: the codified "always / never / when X do Y" statement
- **Skill**: which `skills/<name>/SKILL.md` this applies to (match to existing skills below)
- **Section**: where in the skill to add/update (`## Common Mistakes`, existing rule text, new sub-section)
- **Change type**: `add-mistake`, `update-rule`, `add-section`, `refine-guidance`

**Known skills** (map learnings to one of these):
`wp-github-flow`, `wp-ci-qa`, `wp-coding-standards`, `wp-plugin-testing`, `wp-plugin-audit`,
`wp-plugin-release`, `wp-org-submission`, `wp-woocommerce`, `wp-build-tools`, `wp-database`,
`wp-freemius`, `wp-i18n-workflow`, `wp-background-processing`, `wp-multisite`,
`wp-email-templates`, `wp-phpstan-stubs`, `wp-admin-browser`, `wp-guided-tour`

If a learning doesn't map to any existing skill, collect it as a **new skill candidate** (see Step 2b).

### 2. Preview (always show first)

#### 2a. Existing skill updates

Display a table of all learnings found:

```
| # | Skill | Section | Change | Rule Summary |
|---|-------|---------|--------|--------------|
| 1 | wp-github-flow | Common Mistakes | add-mistake | Never add `bug` label — QA assigns it during triage |
```

#### 2b. New skill candidates (from session + known WP dev gaps)

If action is `suggest` — skip the existing-skill update flow entirely and run this section only.

Otherwise always show this section after the updates table, even on `apply`.

**Step 1 — collect from session:** any learnings marked `unassigned` in step 1 become candidates.

**Step 2 — scan known WP dev topic gaps:** compare existing skills against the full WP plugin development surface. Flag topics with no existing skill:

| Topic | Why it's a skill candidate |
|-------|---------------------------|
| REST API authentication (JWT / Application Passwords / nonce) | No current skill covers auth patterns |
| Block editor data layer (`@wordpress/data`, selectors, dispatchers) | wp-block-development covers markup, not data |
| Gutenberg block transforms & variations | Not covered |
| Custom post type + taxonomy registration patterns | Core WP, no skill |
| Settings API / options pages (non-Freemius) | Freemius skill exists but not vanilla options |
| WordPress Cron (`wp_schedule_event`) vs Action Scheduler | wp-background-processing covers AS, not WP-Cron specifics |
| Transients, object cache, and persistent caching patterns | No cache skill |
| Plugin uninstall / deactivation cleanup | No skill covers data teardown |
| WP CLI custom command development | wp-wpcli-and-ops from official skills covers ops, not building commands |
| User roles and capabilities | No skill |
| WordPress REST API endpoints (custom) | No skill (official skill covers consuming, not building) |
| Plugin update mechanism (self-hosted, non-Freemius) | Not covered |
| Ajax handlers (admin-ajax.php + REST fallback) | No skill |
| Nonce security patterns | No skill |
| Assets enqueueing best practices | No skill |

Show all candidates as a table:

```
## New Skill Candidates

| # | Suggested Skill Name | WP Dev Topic | Source | Why Needed |
|---|----------------------|--------------|--------|------------|
| 1 | wp-rest-endpoints    | Custom REST API endpoint development | session | User hit auth gap not covered by existing skills |
| 2 | wp-capabilities      | User roles & capabilities            | gap-analysis | No existing skill covers this WP core feature |
```

**Source** values: `session` (came from this chat), `gap-analysis` (known WP topic with no skill).

Do NOT fabricate candidates. Only include topics where there is genuinely no existing skill.

If action is `preview` — stop here. Otherwise continue.

If action is `skill:<name>` — only process learnings for that skill.

If **no learnings found**, say so explicitly: "No actionable learnings found in this session." Do not fabricate entries.

### 3. Confirm before applying

Ask: "Apply these N updates to skill files?"

Wait for confirmation. If user says no or wants to skip specific entries, respect that.

### 4. Apply updates

For each confirmed learning, read the skill file and apply the minimal change:

**`add-mistake`** — append a new row to the `## Common Mistakes` table:
```markdown
| <mistake> | <fix / rule> |
```

**`update-rule`** — find the existing sentence/paragraph and refine it in-place.

**`add-section`** — append a new `###` sub-section before `## Common Mistakes` or `## Quick Reference`.

**`refine-guidance`** — targeted edit to an existing instruction block.

Rules for edits:
- Minimal change — don't rewrite surrounding text
- Match existing tone (imperative, terse, no filler)
- Bold the rule keyword (`**Never**`, `**Always**`) for scannability
- If adding to Common Mistakes, put new row at bottom of that table

### 5. Ship — owner vs contributor path

**Detect identity first:**

```bash
GH_USER=$(gh api user -q .login)
OWNER="mralaminahamed"
```

---

#### 5a. Owner path (`$GH_USER == mralaminahamed`)

Apply edits directly to the working tree. Show commit command — do NOT auto-commit:

```bash
git add skills/
git commit -m "docs(skills): capture session learnings — <brief summary>"
git push origin trunk
```

---

#### 5b. Contributor path (`$GH_USER != mralaminahamed`)

One branch + one PR **per skill** that has changes. Each is independent and reviewable alone.

**i. Fork the upstream repo (if not already forked):**

```bash
gh repo fork mralaminahamed/wp-dev-skills --clone=false
# Sets up <GH_USER>/wp-dev-skills on GitHub
```

**ii. Ensure fork remote exists locally:**

```bash
git remote get-url fork 2>/dev/null || \
  git remote add fork "https://github.com/$GH_USER/wp-dev-skills.git"
```

**iii. For each skill with changes — branch → commit → push → PR:**

```bash
# Fetch fresh base
git fetch origin trunk

DATE=$(date +%Y-%m-%d)
SKILL=<skill-name>                              # e.g. wp-github-flow
BRANCH="docs/${SKILL}-session-learning-${DATE}"

git checkout -b "$BRANCH" origin/trunk

# Apply the edit to skills/<skill>/SKILL.md (already done in Step 4)
git add "skills/${SKILL}/SKILL.md"
git commit -m "docs(${SKILL}): <imperative rule summary>"

git push fork "$BRANCH"

gh pr create \
  --repo "mralaminahamed/wp-dev-skills" \
  --base "trunk" \
  --head "${GH_USER}:${BRANCH}" \
  --assignee "mralaminahamed" \
  --reviewer "mralaminahamed" \
  --template ".github/PULL_REQUEST_TEMPLATE/skill-learning.md" \
  --title "docs(${SKILL}): <imperative rule summary>" \
  --body "$(cat <<'EOF'
## Session Learning — Skill Update

## Summary
<one-line description of the rule discovered>

## Skills Updated

| Skill | File | Change Type | Rule Added |
|-------|------|-------------|------------|
| <skill> | skills/<skill>/SKILL.md | <change-type> | <rule summary> |

## Session Context
<where this was discovered — e.g. "Discovered while working on PR #N — QA label ownership">

## Change Details
<what changed and why it was non-obvious enough to codify>

## Checklist
- [x] Rule is minimal — not project-specific, applies broadly
- [x] Tone matches existing skill (imperative, terse, no filler)
- [x] Rule keyword bolded (`**Never**` / `**Always**`)
- [x] Surrounding text unchanged (minimal diff)
- [x] Common Mistakes row added at bottom of table (not middle)
- [ ] `python3 .github/scripts/validate_skills.py` passes
EOF
)"

# Return to trunk before next skill's branch
git checkout trunk
```

Repeat iii for each skill with changes. Each PR is independent — one skill per PR.

**iv. Report all PRs opened:**

```
Opened N PR(s) on mralaminahamed/wp-dev-skills:
- PR #<n> — docs(wp-github-flow): never add bug label on dev PR
- PR #<n> — docs(wp-ci-qa): QA owns bug label assignment
```

---

## Examples

**Session contained:** user said "never add the bug label when opening a PR — QA assigns it"
→ Skill: `wp-github-flow` | Section: Common Mistakes | Rule: "Never add `bug` label on a dev PR — QA assigns during testing"

**Session contained:** user corrected a PHPCS rule applied to the wrong file scope
→ Skill: `wp-coding-standards` | Section: Common Mistakes | Rule added to table

**Session contained:** only small talk and UI questions
→ "No actionable learnings found in this session."
