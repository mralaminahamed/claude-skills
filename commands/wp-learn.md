---
description: Extract learnings from the current session and update wp-dev-skills skill files — captures corrections, new rules, edge cases, and Common Mistakes entries discovered during real usage
argument-hint: "[preview | apply | skill:<name>]"
---

# WP Dev Skills — Learn from Session

Scan the current conversation for corrections, new rules, and edge cases, then propose and apply updates to the relevant skill files in `skills/*/SKILL.md`.

Action: $ARGUMENTS (defaults to `apply`)

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

If a learning doesn't map to any existing skill, note it as `unassigned` — do not create a new skill.

### 2. Preview (always show first)

Display a table of all learnings found:

```
| # | Skill | Section | Change | Rule Summary |
|---|-------|---------|--------|--------------|
| 1 | wp-github-flow | Common Mistakes | add-mistake | Never add `bug` label — QA assigns it during triage |
```

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

### 5. Report

After all edits, show:
```
Updated N skill file(s):
- skills/wp-github-flow/SKILL.md — added 1 mistake entry
- skills/wp-ci-qa/SKILL.md — refined QA label ownership rule
```

Then show the commit command to run (do NOT auto-commit):
```bash
git add skills/
git commit -m "docs(skills): capture session learnings — <brief summary>"
```

---

## Examples

**Session contained:** user said "never add the bug label when opening a PR — QA assigns it"
→ Skill: `wp-github-flow` | Section: Common Mistakes | Rule: "Never add `bug` label on a dev PR — QA assigns during testing"

**Session contained:** user corrected a PHPCS rule applied to the wrong file scope
→ Skill: `wp-coding-standards` | Section: Common Mistakes | Rule added to table

**Session contained:** only small talk and UI questions
→ "No actionable learnings found in this session."
