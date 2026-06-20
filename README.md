# WordPress Dev Skills

[![Validate](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml/badge.svg)](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Cursor Directory](https://img.shields.io/badge/Cursor_Directory-Plugin-0073aa?logo=cursor)](https://cursor.directory/plugins/wp-dev-skills)

Covers the complete WordPress plugin development lifecycle — build, test, audit, release, and ship to WP.org — for Claude Code, Gemini CLI, Cursor, Windsurf, Cline, Codex, GitHub Copilot, opencode, and more.

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

### Build

Set up tooling and implement plugin features.

| Skill | Activates when |
|---|---|
| **wp-build-tools** | Setting up or debugging the JS/CSS pipeline — `@wordpress/scripts`, webpack, Vite, `.asset.php` enqueuing, multiple entry points, dependency reuse. |
| **wp-coding-standards** | Setting up PHPCS + WPCS, configuring `phpcs.xml.dist`, fixing sniff violations, or adding PHPCS to CI. |
| **wp-phpstan-stubs** | Scaffolding a PHPStan stubs package for a third-party plugin/library — full package structure, Packagist setup, GitHub Actions release workflow. |
| **wp-database** | Custom tables with `dbDelta`, versioned schema migrations, `$wpdb` prepared statements, query optimisation, data migration. |
| **wp-background-processing** | Background jobs — Action Scheduler, `WP_Background_Process`, WP Cron, batch import with progress tracking. |
| **wp-multisite** | Making a plugin multisite-compatible — network activation, per-site vs network options, `switch_to_blog()`, network admin pages. |
| **wp-i18n-workflow** | Managing translations — POT generation, PO/MO compilation, JS translations with `wp_set_script_translations`, translate.wordpress.org. |
| **wp-email-templates** | Adding transactional emails — extract inline strings into reusable branded HTML templates sent via `wp_mail()`. |
| **wp-woocommerce** | Building or extending a WooCommerce plugin — custom product types, payment gateways, shipping methods, HPOS, REST API extensions, block cart/checkout. |
| **wp-freemius** | Integrating the Freemius SDK — free/pro feature gating, license management, trials, pricing page, WP.org trialware compliance. |
| **wp-admin-browser** | Driving a WordPress admin panel via Chrome DevTools MCP — login, navigate menus, submit forms, CRUD through the UI, JS state verification. |
| **wp-guided-tour** | Implementing a guided tour in a WP admin plugin using Driver.js — IIFE bundle setup, PHP tour configs, JS scope detection, completion tracking. |

### Test & Audit

Verify correctness, security, and consistency before shipping.

| Skill | Activates when |
|---|---|
| **wp-plugin-testing** | Setting up or writing tests — PHPUnit integration tests, Brain\Monkey unit tests, Codeception acceptance tests, redirect/exit harness, CI matrix. |
| **wp-plugin-audit** | Consistency and security sweep — version drift, naming/prefix, docs↔code mismatch, escaping, nonces, capabilities, file upload, secrets, dependency CVEs. Routes each finding to the correct fix skill. |

### Ship

Contribute, release, and publish to the WordPress ecosystem.

| Skill | Activates when |
|---|---|
| **wp-github-flow** | Shipping a contribution — debug a GitHub issue by URL/number, or turn uncommitted changes into scoped conventional commits, a branch, and a PR. |
| **wp-ci-qa** | PR has QA failures, "Testing Failed" label, or QA comments — trace root causes, apply scoped commits, post re-test comment. |
| **wp-plugin-release** | Bumping or releasing a version — keeps plugin header, constant, `Stable tag`, changelog, and `.pot` file coherent. |
| **wp-org-submission** | First-time WP.org directory submission, SVN deploy, fixing reviewer rejections (17 patterns), banner/icon/screenshot assets. |

---

## Install

### Cursor Directory

Browse and install directly from [cursor.directory](https://cursor.directory/plugins/wp-dev-skills):

```
https://cursor.directory/plugins/wp-dev-skills
```

### Claude Code

```bash
claude plugin marketplace add mralaminahamed/wordpress-official-agent-skills
claude plugin marketplace add mralaminahamed/wp-dev-skills
claude plugin install wp-dev-skills@wp-dev-skills
```

> `wordpress-official-agent-skills` is a declared dependency — adding its marketplace first lets Claude Code auto-install it alongside this plugin.

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

## Dependency

Pairs with **[wordpress-official-agent-skills](https://github.com/mralaminahamed/wordpress-official-agent-skills)** — official WordPress skills from the WordPress project (blocks, themes, REST API, WP-CLI, performance, PHPStan, Playground). Declared as a plugin dependency; Claude Code installs it automatically when both marketplaces are configured.

## License

[MIT](LICENSE) © Al Amin Ahamed
