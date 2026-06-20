# WordPress Dev Skills

[![Validate](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml/badge.svg)](https://github.com/mralaminahamed/wp-dev-skills/actions/workflows/validate.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Cursor Directory](https://img.shields.io/badge/Cursor_Directory-Plugin-0073aa?logo=cursor)](https://cursor.directory/plugins/wp-dev-skills)

WordPress plugin development skills for AI coding agents — Claude Code, Gemini CLI, Cursor, Windsurf, Cline, Codex, GitHub Copilot, opencode, and more.

Skills activate automatically when their description matches your task. No slash commands needed.

---

<p align="center">
  <a href="#skill-map">Skill map</a> •
  <a href="#install">Install</a> •
  <a href="./INSTALL.md">Full install guide</a> •
  <a href="./CONTRIBUTING.md">Contributing</a>
</p>

---

## Skill map

All skills available when both plugins are installed, grouped by domain. Skills marked `†` come from the [wordpress-official-agent-skills](https://github.com/mralaminahamed/wordpress-official-agent-skills) dependency — they install automatically alongside this plugin on Claude Code.

### Contribution & CI

| Skill | Activates when |
|---|---|
| **wp-github-flow** | Shipping a contribution — debug a GitHub issue by URL/number, or turn uncommitted working-tree changes into scoped conventional commits, a branch, and a PR. |
| **wp-ci-qa** | PR has QA failures, "Testing Failed" label, or QA comments. Traces root causes, applies scoped commits, posts re-test comment. |
| **wp-project-triage** `†` | Deterministic inspection of any WordPress repository — plugin, theme, block theme, or core checkout. Produces a structured JSON report for downstream workflows. |
| **wordpress-router** `†` | Classify a WP repo and route to the correct skill (blocks, theme.json, REST API, WP-CLI, performance, testing, release). |

### Code Quality & Static Analysis

| Skill | Activates when |
|---|---|
| **wp-coding-standards** | Setting up PHPCS + WPCS, configuring `phpcs.xml.dist`, fixing sniff violations, or adding PHPCS to CI. |
| **wp-phpstan** `†` | Configuring, running, or fixing PHPStan in a WordPress project — `phpstan.neon` setup, baselines, WP-specific typing. |
| **wp-phpstan-stubs** | Scaffolding a PHPStan stubs package for a third-party plugin/library — full package structure, Packagist setup, GitHub Actions release workflow. |
| **wp-plugin-audit** | Auditing a plugin for inconsistencies — version drift, naming/prefix, docs↔code mismatch, escaping, nonces, capabilities. |
| **wp-plugin-testing** | Setting up or writing tests — PHPUnit integration tests, Brain\Monkey unit tests, redirect/exit harness, CI matrix. |

### Plugin Foundation

| Skill | Activates when |
|---|---|
| **wp-plugin-development** `†` | General plugin architecture — activation/deactivation/uninstall hooks, Settings API, admin UI, data storage, cron, security conventions. |
| **wp-database** | Custom tables with `dbDelta`, versioned schema migrations, `$wpdb` prepared statements, query optimisation, data migration. |
| **wp-background-processing** | Background jobs — Action Scheduler, `WP_Background_Process`, WP Cron, batch import with progress tracking. |
| **wp-multisite** | Making a plugin multisite-compatible — network activation, per-site vs network options, `switch_to_blog()`, network admin pages. |
| **wp-i18n-workflow** | Managing translations — POT generation, PO/MO compilation, JS translations with `wp_set_script_translations`, translate.wordpress.org. |
| **wp-email-templates** | Adding transactional emails — extract inline strings into reusable branded HTML templates sent via `wp_mail()`. |

### Blocks & Modern WordPress

| Skill | Activates when |
|---|---|
| **wp-block-development** `†` | Developing Gutenberg blocks — `block.json`, `register_block_type`, attributes, supports, dynamic rendering, deprecations, `@wordpress/scripts`/`@wordpress/create-block`. |
| **wp-block-themes** `†` | Developing block themes — `theme.json`, templates, template parts, patterns, style variations, Site Editor troubleshooting. |
| **wp-interactivity-api** `†` | Building Interactivity API features — `data-wp-*` directives, `@wordpress/interactivity` store/state/actions, `viewScriptModule` integration. |
| **wp-build-tools** | Setting up or debugging a JS/CSS build pipeline — `@wordpress/scripts`, webpack, Vite, `.asset.php` enqueuing, multiple entry points, dependency reuse. |
| **wpds** `†` | Building UI with the WordPress Design System — WPDS components, tokens, and patterns. |

### REST API & Abilities

| Skill | Activates when |
|---|---|
| **wp-rest-api** `†` | Building or debugging REST endpoints — `register_rest_route`, controller classes, schema/argument validation, `permission_callback`, `register_rest_field`, CPT exposure. |
| **wp-abilities-api** `†` | Working with the WordPress Abilities API — registering abilities, categories, meta, REST exposure, and permissions checks. |
| **wp-abilities-audit** `†` | Auditing a plugin's REST surface and proposing Abilities API registrations. |
| **wp-abilities-verify** `†` | Verifying Abilities API registrations — callback behaviour, permissions, schema, adversarial readonly-but-writes detection. |

### Commerce & Monetization

| Skill | Activates when |
|---|---|
| **wp-woocommerce** | Building or extending a WooCommerce plugin — custom product types, payment gateways, shipping methods, HPOS, REST API extensions, block cart/checkout. |
| **wp-freemius** | Integrating the Freemius SDK — free/pro feature gating, license management, trials, pricing page, WP.org trialware compliance. |

### Publishing & WP.org

| Skill | Activates when |
|---|---|
| **wp-plugin-release** | Bumping or releasing a version — keeps plugin header, constant, `Stable tag`, changelog, and `.pot` file coherent. |
| **wp-org-submission** | First-time WP.org directory submission, SVN deploy, fixing reviewer rejections (17 patterns), banner/icon/screenshot assets. |
| **wp-plugin-directory-guidelines** `†` | GPL compliance, license compatibility, upsell/freemium patterns, plugin naming, trademark rules, and all 18 WP.org directory guidelines. |

### Tooling & Environment

| Skill | Activates when |
|---|---|
| **wp-wpcli-and-ops** `†` | WP-CLI operations — search-replace, db export/import, plugin/theme/user management, cron, cache, multisite, scripting with `wp-cli.yml`. |
| **wp-performance** `†` | Investigating or improving backend performance — profiling, query optimisation, autoloaded options, object caching, HTTP API calls. |
| **wp-playground** `†` | WordPress Playground workflows — disposable WP instances in-browser or via `@wp-playground/cli`, blueprints, Xdebug, auto-mounting plugins/themes. |
| **blueprint** `†` | Creating, editing, or reviewing WordPress Playground blueprint JSON files and demo environment configuration. |

### Browser Automation & UI

| Skill | Activates when |
|---|---|
| **wp-admin-browser** | Driving a WordPress admin panel via Chrome DevTools MCP — login, navigate menus, submit forms, CRUD through the UI, JS state verification. |
| **wp-guided-tour** | Implementing a guided tour in a WP admin plugin using Driver.js — IIFE bundle setup, PHP tour configs, JS scope detection, completion tracking. |

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

Requires **[wordpress-official-agent-skills](https://github.com/mralaminahamed/wordpress-official-agent-skills)** — the official WordPress skill set (blocks, themes, REST API, Abilities API, WP-CLI, performance, PHPStan, Playground). Declared as a plugin dependency; Claude Code installs it automatically when both marketplaces are configured. Skills marked `†` in the skill map above come from this dependency.

## License

[MIT](LICENSE) © Al Amin Ahamed
