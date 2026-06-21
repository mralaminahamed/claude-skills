---
description: Extract learnings from the current session and update wp-dev-skills skill files — captures corrections, new rules, edge cases, and Common Mistakes entries discovered during real usage. Only globally applicable WP developer standards accepted — project-specific rules are redirected. Use `suggest` to see WP dev topics not yet covered by any skill.
argument-hint: "[preview | apply | suggest | skill:<name>]"
---

# WP Dev Skills — Learn from Session

Scan the current conversation for corrections, new rules, and edge cases, then open a PR against `mralaminahamed/wp-dev-skills` for each skill updated. **All changes go through PR — no direct commits, owner or not.**

Action: $ARGUMENTS (defaults to `apply`)

| Mode | Behaviour |
|------|-----------|
| `apply` | Scan → filter global-only → show table → confirm → branch-per-skill → PR per skill → assign owner |
| `preview` | Show what would be proposed (filtered table + candidates), no branches/PRs |
| `suggest` | Skip session scan — show only global WP dev topic gaps not covered by any skill |
| `skill:<name>` | Scope scan and PRs to one skill only; still show candidates |

---

## Acceptance criteria — global standards only

**This plugin ships rules that apply to ALL WordPress developers across ALL projects.**

Before proposing any learning, apply this filter:

| Test | Accept | Reject |
|------|--------|--------|
| Would a WP developer on a completely different project benefit from this rule? | ✅ Accept | ❌ Reject |
| Is the rule about a WP core API, standard tool, or universal workflow? | ✅ Accept | ❌ Reject |
| Does it reference a specific team, repo, project name, or internal tool? | ❌ Reject | — |
| Is it a QA process specific to one org's workflow? | ❌ Reject | — |
| Is it a code style preference that doesn't reflect WP/WPCS standards? | ❌ Reject | — |
| Does it assume a specific plugin architecture that isn't universal? | ❌ Reject | — |

**When a learning is rejected (project-specific):** do NOT silently drop it. Show it in a **Redirected** section with a suggestion:

```
## Redirected (project-specific — not accepted into global plugin)

| Learning | Reason rejected | Suggestion |
|----------|----------------|------------|
| "Never add bug label — QA assigns it" | Internal QA process, not universal WP standard | Add to your project's CLAUDE.md or a project-scoped plugin |
```

Suggestions to offer:
- Add to project's `CLAUDE.md` as a team rule
- Create a private project-scoped plugin for org-specific skills
- Use existing global skills — link the closest match

---

## What counts as a global learning

**Accept:**
- WP core API usage patterns (`wp_remote_get`, `wp_schedule_event`, nonces, capabilities)
- WPCS/PHPCS/PHPStan standards universal across WP plugins
- GitHub flow mechanics that apply to any WP plugin repo (branching, labels that are WP-ecosystem-standard)
- Security patterns required for all WP plugins (sanitize, escape, nonce verify)
- WP.org submission rules
- Patterns documented in WP Handbook or WP Coding Standards

**Reject:**
- Team-internal label assignments ("QA assigns `bug`") — org-specific, not WP standard
- Project-specific scopes, naming conventions, or architecture choices
- Rules tied to specific tools/services not universally used (e.g. "always use Freemius" is org preference, not universal)
- Internal sprint/QA processes

---

## Steps

### 1. Scan conversation

Read the full conversation history. For each candidate learning extract:
- **Signal** — exact quote or paraphrase
- **Rule** — codified "always / never / when X do Y" statement
- **Global?** — apply acceptance criteria above
- **Skill** — which `skills/<name>/SKILL.md` (existing skills below), or `unassigned`
- **Section** — where to add/update
- **Change type** — `add-mistake`, `update-rule`, `add-section`, `refine-guidance`

**Known skills:**
`wp-github-flow`, `wp-ci-qa`, `wp-coding-standards`, `wp-plugin-testing`, `wp-plugin-audit`,
`wp-plugin-release`, `wp-org-submission`, `wp-woocommerce`, `wp-build-tools`, `wp-database`,
`wp-freemius`, `wp-i18n-workflow`, `wp-background-processing`, `wp-multisite`,
`wp-email-templates`, `wp-phpstan-stubs`, `wp-admin-browser`, `wp-guided-tour`

Unassigned global learnings → new skill candidates (Step 2b).

### 2. Preview

#### 2a. Accepted updates (global standards only)

```
| # | Skill | Section | Change | Rule Summary |
|---|-------|---------|--------|--------------|
| 1 | wp-coding-standards | Common Mistakes | add-mistake | Always escape output with esc_html() — never echo raw |
```

#### 2b. Redirected (project-specific — not accepted)

```
| # | Learning | Reason | Suggestion |
|---|----------|--------|------------|
| 1 | "QA assigns bug label" | Org-specific QA process, not WP standard | Add to project CLAUDE.md |
```

#### 2c. New global skill candidates

Compare unassigned accepted learnings + known WP dev gaps against existing skills:

| Topic | Why it's a skill candidate |
|-------|---------------------------|
| REST API authentication (JWT / Application Passwords / nonce) | No skill covers WP REST auth patterns |
| Block editor data layer (`@wordpress/data`, selectors, dispatchers) | wp-block-development covers markup only |
| Gutenberg block transforms & variations | Not covered |
| Custom post type + taxonomy registration | Core WP pattern, no skill |
| Settings API / options pages (non-Freemius) | Vanilla options not covered |
| WordPress Cron (`wp_schedule_event`) | wp-background-processing covers AS only |
| Transients + object cache patterns | No caching skill |
| Plugin uninstall / deactivation cleanup | No data teardown skill |
| WP CLI custom command development | Ops covered, building commands not |
| User roles and capabilities | No skill |
| Custom REST API endpoints | Official skill covers consuming, not building |
| Plugin self-hosted update mechanism | Non-Freemius updates not covered |
| Ajax handlers (`admin-ajax.php` + REST fallback) | No AJAX authoring skill |
| Nonce security patterns | No skill |
| Script/style enqueueing best practices | No asset pipeline skill |

Show all candidates:

```
## New Global Skill Candidates

| # | Suggested Skill Name | WP Dev Topic | Source | Why Needed |
|---|----------------------|--------------|--------|------------|
| 1 | wp-security | Nonce, capability, sanitize/escape patterns | gap-analysis | Security required for all WP plugins |
| 2 | wp-ajax | admin-ajax.php + REST fallback authoring | gap-analysis | No skill covers AJAX endpoint building |
```

Source: `session` (from this chat) or `gap-analysis` (known uncovered topic). Do NOT fabricate.

If action is `preview` — stop here.
If action is `suggest` — show only 2c, skip 2a and 2b.
If no accepted learnings found — say so explicitly. Do not fabricate.

### 3. Confirm

Ask: "Open PRs for N accepted update(s) across N skill(s)?"

List which skills will get PRs. Wait for confirmation. User may deselect skills.

### 4. Apply edits locally (per skill, in isolation)

For each accepted learning, apply the minimal change to `skills/<skill>/SKILL.md` in a clean branch:

**`add-mistake`** — append row to `## Common Mistakes`:
```markdown
| <mistake> | <fix / rule> |
```

**`update-rule`** — refine existing sentence in-place.

**`add-section`** — new `###` sub-section before Common Mistakes.

**`refine-guidance`** — targeted edit to an existing instruction block.

Edit rules:
- Minimal diff — no surrounding text rewrites
- Imperative, terse — match existing skill tone
- Bold rule keyword (`**Never**`, `**Always**`)
- Common Mistakes row goes at bottom of table

### 5. Branch → commit → push → PR (all contributors, including owner)

**Detect identity:**
```bash
GH_USER=$(gh api user -q .login)
OWNER="mralaminahamed"
UPSTREAM="mralaminahamed/wp-dev-skills"
```

**If contributor (`$GH_USER != $OWNER`) — fork first:**
```bash
gh repo fork "$UPSTREAM" --clone=false
git remote get-url fork 2>/dev/null || \
  git remote add fork "https://github.com/$GH_USER/wp-dev-skills.git"
PUSH_REMOTE="fork"
HEAD_PREFIX="${GH_USER}:"
```

**If owner (`$GH_USER == $OWNER`):**
```bash
PUSH_REMOTE="origin"
HEAD_PREFIX=""
```

**For each skill with accepted changes — one branch + one PR:**

```bash
git fetch origin trunk
DATE=$(date +%Y-%m-%d)
SKILL=<skill-name>
BRANCH="docs/${SKILL}-session-learning-${DATE}"

git checkout -b "$BRANCH" origin/trunk

# Edit already applied in Step 4
git add "skills/${SKILL}/SKILL.md"
git commit -m "docs(${SKILL}): <imperative rule summary>"

git push "$PUSH_REMOTE" "$BRANCH"

gh pr create \
  --repo "$UPSTREAM" \
  --base "trunk" \
  --head "${HEAD_PREFIX}${BRANCH}" \
  --assignee "$OWNER" \
  --reviewer "$OWNER" \
  --template ".github/PULL_REQUEST_TEMPLATE/skill-learning.md" \
  --title "docs(${SKILL}): <imperative rule summary>" \
  --body "$(cat <<'EOF'
## Session Learning — Skill Update

## Summary
<one-line: what global WP standard was captured>

## Skills Updated

| Skill | File | Change Type | Rule Added |
|-------|------|-------------|------------|
| <skill> | skills/<skill>/SKILL.md | <change-type> | <rule> |

## Global Standard Justification
<!-- Why does this rule apply to ALL WP developers, not just this project? -->
<e.g. "WPCS requires output escaping universally — this catches a pattern not in existing Common Mistakes">

## Session Context
<where this was discovered — PR number, task, issue>

## Checklist
- [x] Rule passes global acceptance criteria (not project-specific)
- [x] Tone matches existing skill (imperative, terse)
- [x] Rule keyword bolded (**Never** / **Always**)
- [x] Surrounding text unchanged (minimal diff)
- [x] Common Mistakes row at bottom of table
- [ ] `python3 .github/scripts/validate_skills.py` passes
EOF
)"

git checkout trunk
```

Repeat per skill. Each PR is independent — one skill per PR.

### 6. Report

```
Opened N PR(s) on mralaminahamed/wp-dev-skills:
  PR #<n> — docs(wp-coding-standards): always escape output with esc_html()
  PR #<n> — docs(wp-plugin-testing): use Brain\Monkey for WP function mocks

Redirected N learning(s) — not accepted (project-specific):
  "QA assigns bug label" → add to your project's CLAUDE.md instead
```

---

## Examples

**Global — accepted:**
User: "always use `esc_html()` not `echo` directly on user-facing output"
→ Skill: `wp-coding-standards` | add-mistake | **Global:** WPCS requires this for all WP plugins

**Project-specific — redirected:**
User: "never add the bug label — QA assigns it"
→ **Rejected:** Org QA process, not a WP-universal standard
→ Suggestion: Add to project `CLAUDE.md` as team rule

**Nothing found:**
→ "No actionable global learnings found in this session."
