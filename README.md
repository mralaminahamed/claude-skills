# WordPress Dev Skills

[![Validate](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml/badge.svg)](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

WordPress plugin development skills for AI coding agents — Claude Code, Gemini CLI, Cursor, Windsurf, Cline, Codex, GitHub Copilot, opencode, and more.

Skills activate automatically when their description matches your task. No slash commands needed.

---

<p align="center">
  <a href="#skills">Skills</a> •
  <a href="#install">Install</a> •
  <a href="./INSTALL.md">Full install guide</a> •
  <a href="./CONTRIBUTING.md">Contributing</a>
</p>

---

## Skills

| Skill | Activates when |
|---|---|
| **wp-github-flow** | Shipping a contribution — debug a GitHub issue by URL/number, or turn uncommitted changes into scoped conventional commits, a branch, and a PR. |
| **wp-ci-qa** | PR has QA failures, "Testing Failed" label, or QA comments. Reads feedback, traces root causes, applies scoped commits, posts re-test comment. |
| **wp-coding-standards** | Setting up PHPCS + WordPress Coding Standards, configuring `phpcs.xml.dist`, fixing sniff violations, or adding PHPCS to CI. |
| **wp-plugin-audit** | Auditing a WP plugin for inconsistencies — version drift, naming/prefix, docs↔code mismatch, escaping/sanitisation, nonces/capabilities. |
| **wp-plugin-testing** | Setting up or writing tests — PHPUnit integration tests, Brain\Monkey unit tests, Codeception acceptance tests, redirect/exit harness, CI matrix. |
| **wp-plugin-release** | Bumping or releasing a version — keeps plugin header, constant, `Stable tag`, changelog, and `.pot` file coherent. |
| **wp-org-submission** | First-time WP.org directory submission, SVN deploy, fixing reviewer rejections (17 patterns), or setting up banner/icon/screenshot assets. |
| **wp-build-tools** | Setting up or debugging the JS/CSS build pipeline — `@wordpress/scripts`, webpack, Vite, `.asset.php` enqueuing, multiple entry points. |
| **wp-background-processing** | Implementing background jobs — Action Scheduler, `WP_Background_Process`, WP Cron, batch import with progress tracking. |
| **wp-database** | Custom tables with `dbDelta`, versioned schema migrations, `$wpdb` prepared statements, query optimisation, data migration. |
| **wp-multisite** | Making a plugin multisite-compatible — network activation, per-site vs network options, `switch_to_blog()`, network admin pages. |
| **wp-i18n-workflow** | Managing translations — POT generation, PO/MO compilation, JS translations with `wp_set_script_translations`, translate.wordpress.org. |
| **wp-email-templates** | Adding transactional emails — extract inline strings into reusable branded HTML templates sent via `wp_mail()`. |
| **wp-phpstan-stubs** | Scaffolding a PHPStan stubs package for a third-party plugin/library. Full package structure, Packagist setup, GitHub Actions release workflow. |
| **wp-woocommerce** | Building or extending a WooCommerce plugin — custom product types, payment gateways, shipping methods, HPOS, REST API extensions, block cart/checkout. |
| **wp-freemius** | Integrating the Freemius SDK — free/pro feature gating, license management, trials, pricing page, WP.org trialware compliance. |
| **wp-admin-browser** | Driving a WordPress admin panel via Chrome DevTools MCP — login, navigate menus, submit forms, CRUD through the UI. |
| **wp-guided-tour** | Implementing a guided tour in a WP admin plugin using Driver.js — IIFE bundle setup, PHP tour configs, JS scope detection, completion tracking. |

## Install

### Claude Code

```bash
claude plugin marketplace add mralaminahamed/wp-dev-skills
claude plugin install wp-dev-skills@wp-dev-skills
```

### Gemini CLI

```bash
gemini extensions install https://github.com/mralaminahamed/wp-dev-skills
```

### Cursor / Windsurf / Cline / GitHub Copilot

```bash
# Cursor
mkdir -p .cursor/rules && curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md > .cursor/rules/wp-dev-skills.mdc

# Windsurf
mkdir -p .windsurf/rules && curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md > .windsurf/rules/wp-dev-skills.md

# Cline
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md > .clinerules/wp-dev-skills.md

# GitHub Copilot
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md > .github/copilot-instructions.md
```

### opencode / AGENTS.md-based agents

```bash
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/AGENTS.md > AGENTS.md
```

### All other agents (Continue, Roo, Augment, Amp, Warp, …)

```bash
npx skills add mralaminahamed/wp-dev-skills -a <agent-slug>
```

Full per-agent install matrix and options → [**INSTALL.md**](./INSTALL.md).

## Links

- [INSTALL.md](./INSTALL.md) — full install matrix, all agents, per-agent detail
- [CONTRIBUTING.md](./CONTRIBUTING.md) — how to add or improve a skill
- [CHANGELOG.md](./CHANGELOG.md) — release history
- [Issues](https://github.com/mralaminahamed/wp-dev-skills/issues) — bug, feature request, skill idea

## License

[MIT](LICENSE) © Al Amin Ahamed
