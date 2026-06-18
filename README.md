# WordPress Dev Skills

[![Validate](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml/badge.svg)](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

18 WordPress plugin development skills for AI coding agents — Claude Code, Gemini CLI, Cursor, Windsurf, Cline, Codex, GitHub Copilot, opencode, and more.

Skills activate automatically when their description matches your task. No slash commands needed.

## Skills

### GitHub & CI

| Skill | Activates when |
|-------|----------------|
| **wp-github-flow** | Shipping a contribution — debug a GitHub issue by number/URL, or turn uncommitted changes into scoped conventional commits, a branch, and a PR with assignee + labels. Handles hotfix, draft PR, stale rebase, revert. |
| **wp-ci-qa** | A PR has QA failures, a "Testing Failed" label, or QA comments. Reads feedback, traces root causes, applies scoped commits, updates labels, posts a re-test comment. |
| **wp-coding-standards** | Setting up PHPCS + WordPress Coding Standards, configuring `phpcs.xml.dist`, fixing sniff violations, or adding PHPCS to CI. |

### Plugin Development

| Skill | Activates when |
|-------|----------------|
| **wp-plugin-audit** | Auditing a WP plugin for inconsistencies — fans out parallel checks across security, i18n, readme, and code quality dimensions. |
| **wp-plugin-testing** | Setting up or writing tests — PHPUnit integration tests, Brain\Monkey unit tests, Codeception acceptance tests, redirect/exit harness, CI matrix. |
| **wp-plugin-release** | Bumping or releasing a version — keeps plugin header, constant, `Stable tag`, changelog, and `.pot` file coherent. |
| **wp-org-submission** | First-time WP.org directory submission, SVN deploy, fixing reviewer rejections, or setting up banner/icon/screenshot assets. |
| **wp-build-tools** | Setting up or debugging the JS/CSS build pipeline — `@wordpress/scripts`, webpack, Vite, `.asset.php` enqueuing, multiple entry points. |
| **wp-background-processing** | Implementing background jobs — Action Scheduler fan-out, `WP_Background_Process`, WP Cron, batch import with progress tracking. |

### Data & Infrastructure

| Skill | Activates when |
|-------|----------------|
| **wp-database** | Creating custom tables with `dbDelta`, writing versioned schema migrations, querying with `$wpdb` prepared statements, data migration. |
| **wp-multisite** | Making a plugin multisite-compatible — network activation, per-site vs network options, `switch_to_blog()` patterns, network admin pages. |
| **wp-i18n-workflow** | Managing translations — POT generation, PO/MO compilation, JS translations with `wp_set_script_translations`, translate.wordpress.org submission. |
| **wp-email-templates** | Adding or refactoring transactional emails — extract inline strings into reusable branded HTML templates sent via `wp_mail()`. |

### Static Analysis & Quality

| Skill | Activates when |
|-------|----------------|
| **wp-phpstan-stubs** | Scaffolding a new PHPStan stubs package for a third-party plugin/library. Full package structure, Packagist setup, GitHub Actions release workflow. |

### Commercial & Ecosystem

| Skill | Activates when |
|-------|----------------|
| **wp-woocommerce** | Building or extending a WooCommerce plugin — custom product types, payment gateways, shipping methods, HPOS compatibility, REST API extensions, block cart/checkout. |
| **wp-freemius** | Integrating the Freemius SDK — free/pro feature gating, license management, trials, pricing page, WP.org trialware compliance. |

### Browser Automation

| Skill | Activates when |
|-------|----------------|
| **wp-admin-browser** | Driving a WordPress admin panel via Chrome DevTools MCP — login, navigate menus, submit forms, CRUD through the UI, debug JS/network errors. |
| **wp-guided-tour** | Implementing a guided tour in a WP admin plugin using Driver.js — IIFE bundle setup, PHP tour configs, JS scope detection from URL + hash, completion tracking, selector testing. |

## Install

### Claude Code

```bash
claude plugin marketplace add mralaminahamed/wp-dev-skills
claude plugin install wp-dev-skills@wp-dev-skills
```

Or from a local clone:

```bash
claude plugin marketplace add ~/path/to/wp-dev-skills
claude plugin install wp-dev-skills@wp-dev-skills
```

### Gemini CLI

```bash
gemini extensions install https://github.com/mralaminahamed/wp-dev-skills
```

### Cursor / Windsurf / Cline / GitHub Copilot

Drop the rule file into your repo — pick the path for your agent:

```bash
# Cursor
mkdir -p .cursor/rules && curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/main/src/rules/wp-dev-skills.md > .cursor/rules/wp-dev-skills.mdc

# Windsurf
mkdir -p .windsurf/rules && curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/main/src/rules/wp-dev-skills.md > .windsurf/rules/wp-dev-skills.md

# Cline
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/main/src/rules/wp-dev-skills.md > .clinerules/wp-dev-skills.md

# GitHub Copilot
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/main/src/rules/wp-dev-skills.md > .github/copilot-instructions.md
```

### opencode / AGENTS.md-based agents (Codex, Devin, OpenHands, …)

```bash
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/main/AGENTS.md > AGENTS.md
```

### All other agents (Continue, Roo, Augment, Amp, Warp, …)

```bash
npx skills add mralaminahamed/wp-dev-skills -a <agent-slug>
```

Replace `<agent-slug>` with your agent id (e.g. `continue`, `roo`, `augment`, `amp`, `warp`). See `npx skills list` for all supported slugs.

## Layout

```
wp-dev-skills/
├── .claude-plugin/
│   ├── plugin.json               # Claude Code plugin manifest
│   └── marketplace.json          # Claude Code marketplace manifest
├── .codex/
│   ├── config.toml               # Codex CLI features
│   └── hooks.json                # Codex SessionStart hook
├── src/rules/
│   └── wp-dev-skills.md          # Rule file for Cursor/Windsurf/Cline/Copilot
├── AGENTS.md                     # Universal context (opencode, Codex, Devin, …)
├── GEMINI.md                     # Gemini CLI context (@-include syntax)
├── gemini-extension.json         # Gemini CLI extension manifest
├── package.json                  # npx skills compatibility
└── skills/
    └── <skill-name>/
        ├── SKILL.md              # required — frontmatter: name, description
        ├── references/           # supporting docs and code patterns
        ├── evals/
        │   └── evals.json        # eval scenarios for testing the skill
        └── scripts/              # optional helper scripts
```

## Develop

Each skill is a directory under [`skills/`](skills/) with a `SKILL.md`. Edit the file — the change is live on the next Claude Code session.

Add reference files under `references/` for patterns too long for the main skill doc. Reference them explicitly from `SKILL.md` so Claude knows to read them.

See [CONTRIBUTING.md](CONTRIBUTING.md) for skill authoring conventions and the validation rules CI enforces.

## Versioning

Version lives in multiple manifests — keep them in sync on release:

- `.claude-plugin/plugin.json` → `version`
- `.claude-plugin/marketplace.json` → `plugins[0].version`
- `gemini-extension.json` → `version`
- `package.json` → `version`

## License

[MIT](LICENSE) © Al Amin Ahamed
