# wp-dev-skills — WordPress Plugin Development Skills

You have access to 18 WordPress plugin development skills. When a task matches a skill, follow that skill's methodology.

## Skills

**wp-github-flow** — Shipping commits/branches/PRs, debugging GitHub issues, conventional commits, branch naming, PR labels.

**wp-woocommerce** — WooCommerce extensions: product types, payment gateways, shipping methods, REST API, HPOS, cart/checkout blocks, orders, coupons, tax, webhooks.

**wp-plugin-testing** — Plugin tests: PHPUnit (WP test suite), Brain\Monkey, WP_Mock, Codeception, redirect harness, HTTP mocking, multisite tests, GitHub Actions CI.

**wp-coding-standards** — PHPCS + WPCS setup, phpcs.xml.dist config, sniff violations, PHPCS in CI. (PHPStan → use wp-phpstan-stubs instead.)

**wp-ci-qa** — Fix PR QA failures: read QA feedback, trace root causes, scoped commits, label updates, QA re-test comment.

**wp-phpstan-stubs** — Scaffold a PHPStan stubs package for a third-party plugin/library: composer.json, bootstrap, finder, generate script, release workflow.

**wp-build-tools** — JS/CSS build pipeline: @wordpress/scripts, webpack, Vite, entry points, .asset.php enqueuing, Sass/PostCSS, dependency reuse.

**wp-background-processing** — Async jobs: Action Scheduler, WP_Background_Process, WP Cron, batch chunking, retry/error handling.

**wp-multisite** — Multisite/network: activation scope, network admin, per-site vs network options, switch_to_blog(), super-admin capabilities.

**wp-i18n-workflow** — Translation pipeline: POT generation, PO→MO, JS JSON, translate.wordpress.org, language packs, debugging.

**wp-database** — Custom tables: dbDelta schema, versioned migrations, $wpdb CRUD, prepared statements, query optimisation.

**wp-email-templates** — Transactional HTML emails: branded base shell, reusable content templates, wp_mail() integration.

**wp-freemius** — Freemius SDK: feature gating, license management, trial, pricing page, opt-in analytics, multisite licensing.

**wp-plugin-audit** — Plugin consistency audit: version drift, naming/prefix/i18n coherence, docs↔code, escaping/sanitisation, nonces/capabilities.

**wp-plugin-release** — Version bump: sync plugin header, PHP constant, readme.txt Stable tag, changelog.

**wp-org-submission** — WP.org submission, SVN deploy, rejection fixes (17 patterns), reviewer compliance, banner/icon/screenshots.

**wp-admin-browser** — WordPress admin automation via Chrome DevTools MCP: login, navigation, forms, user creation, data ops.

**wp-guided-tour** — Driver.js guided tours in WP admin: IIFE setup, PHP config, JS scope detection, completion tracking.

## Usage

Each skill lives at `skills/<skill-name>/SKILL.md` in this repo. Read the relevant SKILL.md before implementing tasks in that domain.
