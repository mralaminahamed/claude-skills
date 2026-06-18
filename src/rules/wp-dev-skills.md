# wp-dev-skills — WordPress Plugin Development Skills

You have access to 18 WordPress plugin development skills. Invoke the relevant skill before responding to any task that matches.

## Skills

**wp-github-flow** — Use when shipping commits/branches/PRs or debugging a GitHub issue. Covers scoped conventional commits, branch naming, PR creation with labels, and issue-driven root-cause debugging.

**wp-woocommerce** — Use when building WooCommerce extensions: custom product types, payment gateways, shipping methods, REST API extensions, WC hooks, HPOS compatibility, cart/checkout blocks, orders, coupons, tax, webhooks.

**wp-plugin-testing** — Use when writing or setting up tests: PHPUnit integration tests (WP test suite), unit tests (Brain\Monkey / WP_Mock), acceptance tests (Codeception / wp-browser), factory fixtures, HTTP mocking, redirect/exit harness, multisite tests, GitHub Actions CI.

**wp-coding-standards** — Use when setting up PHPCS + WordPress Coding Standards (WPCS), configuring phpcs.xml.dist, fixing sniff violations, or adding PHPCS to CI. Not for PHPStan — use wp-phpstan-stubs.

**wp-ci-qa** — Use when a PR has a "Testing Failed" label or QA-reported failures. Covers reading QA feedback, root-cause tracing, scoped fixes, label updates, and posting a QA re-test comment.

**wp-phpstan-stubs** — Use when scaffolding a new PHPStan stubs package for a third-party plugin or library ("create stubs for X", "scaffold phpstan stubs"). Full standard directory layout, composer.json, bootstrap, finder, generate script, and release workflow.

**wp-build-tools** — Use when setting up the JS/CSS build pipeline: @wordpress/scripts, webpack config, Vite, multiple entry points, .asset.php enqueuing, Sass/PostCSS, dependency reuse from WooCommerce/EDD.

**wp-background-processing** — Use when implementing async jobs: Action Scheduler, WP_Background_Process (Delicious Brains), WP Cron, batch chunking, retry/error handling, progress tracking.

**wp-multisite** — Use when adapting a plugin for WordPress multisite: network activation, network admin pages, per-site vs network options, switch_to_blog(), super-admin capabilities, custom table prefix handling.

**wp-i18n-workflow** — Use when managing translations: POT generation (wp i18n make-pot), PO→MO compilation, JS JSON translations (wp_set_script_translations), translate.wordpress.org submission, language pack debugging.

**wp-database** — Use when working with custom database tables: dbDelta schema creation, versioned upgrade routines, $wpdb CRUD with prepared statements, query optimisation, data migrations between plugin versions.

**wp-email-templates** — Use when building transactional emails: branded HTML base shell, reusable content templates, wp_mail() integration, email client compatibility.

**wp-freemius** — Use when integrating the Freemius SDK: free/pro feature gating, license management, trial periods, pricing page, opt-in analytics, multisite licensing, SDK bootstrap.

**wp-plugin-audit** — Use when asked to audit a plugin for inconsistencies: version drift across sources, naming/prefix/i18n coherence, docs↔code mismatch, escaping/sanitisation, nonce/capability patterns.

**wp-plugin-release** — Use when bumping a plugin version: sync plugin header, PHP constant, readme.txt Stable tag, and changelog entries so all sources stay coherent.

**wp-org-submission** — Use when submitting to the WordPress.org directory (initial submission or re-submission), deploying via SVN, fixing reviewer rejections (covers 17 recurring rejection patterns), or setting up banner/icon/screenshot assets.

**wp-admin-browser** — Use when automating WordPress admin via Chrome DevTools MCP: login, menu navigation, form filling, user creation, data operations (create/update/delete) without touching the main admin account.

**wp-guided-tour** — Use when implementing a Driver.js guided tour in WordPress admin: IIFE bundle setup, PHP backend tour config, JS scope detection (URL + hash), completion tracking, selector testing against live DOM.
