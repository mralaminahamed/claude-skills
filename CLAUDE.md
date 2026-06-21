# CLAUDE.md — wp-dev-skills

Maintainer instructions for Claude Code working in this repo.

---

## Project overview

WordPress plugin development skills for AI coding agents. Ships as a Claude Code plugin, Gemini CLI extension, agent rule files for Cursor/Windsurf/Cline/Copilot, and via `npx skills add` for 70+ agents.

Skills live in `skills/*/SKILL.md`. Each is self-contained and auto-discovered by all supported agents — no manifest update required when adding a skill.

---

## What lives where

```
wp-dev-skills/
├── README.md                         # Lean landing page — skill table + quick install
├── INSTALL.md                        # Full per-agent install matrix
├── CONTRIBUTING.md                   # Skill authoring conventions + CI rules
├── CLAUDE.md                         # This file
├── AGENTS.md                         # Universal context (opencode, Codex, Devin, …)
├── GEMINI.md                         # Gemini CLI context (plain markdown)
├── CHANGELOG.md                      # Release history
│
├── .claude-plugin/
│   ├── plugin.json                   # Claude Code plugin manifest
│   └── marketplace.json              # Claude Code marketplace manifest
│
├── .codex/
│   ├── config.toml                   # Codex features flag
│   └── hooks.json                    # Codex SessionStart hook
│
├── gemini-extension.json             # Gemini CLI extension manifest
├── package.json                      # npm metadata for npx skills
│
├── src/rules/
│   └── wp-dev-skills.md              # Rule file (single source for Cursor/Windsurf/Cline/Copilot)
│
├── .github/
│   ├── copilot-instructions.md       # GitHub Copilot rule file
│   ├── workflows/validate.yml        # CI: validates skill structure
│   ├── scripts/validate_skills.py    # Validation script
│   └── ISSUE_TEMPLATE/               # Bug + skill request templates
│
└── skills/
    └── <skill-name>/
        ├── SKILL.md                  # Required — frontmatter: name, description
        ├── references/               # Deep-dive patterns and configs
        ├── evals/evals.json          # Eval scenarios
        └── scripts/                  # Optional helper scripts
```

---

## Adding a skill

1. Create `skills/<skill-name>/SKILL.md` — kebab-case, no underscores.
2. Add YAML frontmatter with `name` and `description`. Description leads with `Use when…` and ends with `Not for:` cases.
3. Add `evals/evals.json` with at least 3 scenarios.
4. Add `references/` files for patterns too long for SKILL.md — reference every file explicitly in a `## References` section.
5. Add a row to the skill table in `README.md`.
6. Add the skill to `AGENTS.md` and `GEMINI.md` tables.
7. Add the skill description to `src/rules/wp-dev-skills.md` and `.github/copilot-instructions.md`.
8. Run `python3 .github/scripts/validate_skills.py` to confirm CI passes.

**Do NOT hardcode skill counts** anywhere. Numbers drift — the repo is the source of truth.

---

## Editing skills

Edit `skills/<name>/SKILL.md` directly. Change is live on next agent session — no build step.

References in `references/` are only read by agents that explicitly know about them (listed in SKILL.md `## References` section). Keep them referenced.

---

## Manifests to keep in sync on release

All four version fields must match when releasing:

- `.claude-plugin/plugin.json` → `version`
- `.claude-plugin/marketplace.json` → `plugins[0].version`
- `gemini-extension.json` → `version`
- `package.json` → `version`

Tag format: `vX.Y.Z`. Update `CHANGELOG.md` before tagging.

---

## CI

`.github/workflows/validate.yml` runs on every push and PR. Checks:

- Both `.claude-plugin/*.json` manifests are valid JSON
- `plugin.json` and `marketplace.json` versions match
- Every `skills/*/` directory has a `SKILL.md` with `name` + `description` frontmatter

Run locally: `python3 .github/scripts/validate_skills.py`

---

## Relationship to wordpress-official-agent-skills

`wp-dev-skills` is a **complement** to `wordpress-official-agent-skills` — never a replacement or duplicate.

### Hard rules

- **Never duplicate** a skill already covered by `wordpress-official-agent-skills`. If the official plugin covers REST API, block development, or WP-CLI ops, do not create a parallel skill here.
- **Never copy** content from official skills into this plugin's SKILL.md files — not even paraphrased.
- **Reference, don't replicate.** If a skill here depends on concepts from an official skill, link to it in a `## References` or `> See also` note. Let the official skill do its job.

### How they fit together

| `wordpress-official-agent-skills` | `wp-dev-skills` |
|-----------------------------------|-----------------|
| WP core APIs, block development, REST API, WP-CLI ops, Playground, WPDS | GitHub contribution flow, CI/QA triage, PHPUnit/PHPCS/PHPStan, build tools, release versioning, WP.org submission, Freemius SDK, plugin audit |
| Official WP.org patterns and standards | Developer lifecycle tooling and workflow |
| What WP *is* | How to *ship* WP plugins professionally |

### When adding a new skill — check official first

Before creating any skill, grep the official plugin:

```bash
find ~/.claude/plugins/cache/wordpress-official-agent-skills -name "SKILL.md" | xargs grep -l "<topic>"
```

If a match exists: **do not create the skill here**. Instead, reference the official skill in any related wp-dev-skills content and use it to enhance efficiency, scalability, and quality in the plugin/theme development lifecycle.

If the official skill covers the topic partially — this plugin may fill the **gap** (e.g. official skill covers REST API *consumption*; this plugin could cover REST endpoint *authoring* patterns for plugin devs). Document the boundary clearly in both skills' `description` and `Not for:` lines.

---

## What NOT to do

- Don't hardcode skill counts (`18 skills`, `N skills`) — use "WordPress plugin development skills" instead.
- Don't add build steps — skills are plain markdown, no compilation.
- Don't put real plugin/project names in skill content — use generic mock names.
- Don't reference files Claude won't know about — every `references/` file must be listed in `SKILL.md`.
- Don't use absolute paths in scripts — use `${CLAUDE_PLUGIN_ROOT}`.
- Don't duplicate or replicate `wordpress-official-agent-skills` content — see "Relationship to wordpress-official-agent-skills" above.
