# Changelog

All notable changes to **wp-dev-skills** are documented here. This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.3.0]

### Changed

- Refreshed WordPress/PHP baselines to **July 2026** across the audit, release, and submission skills: current WP stable **7.0** (7.1 due Aug 2026; 6.9.x maintenance line), PHP floor **7.4** (WP 7.0 dropped 7.2/7.3), officially recommended **8.3+**, and **18** WP.org directory guidelines (page updated 2026-03-11).
- `wp-plugin-audit` — Dimension A now flags a `Tested up to` that is *ahead of* the latest released WordPress (not only a stale one), a `Requires PHP` below 7.4, and a `Requires Plugins` slug mismatch; added a dated "Version currency" block and bumped the canonical header example off EOL PHP 7.2 (→ 6.5 / 7.4).
- `wp-plugin-audit/references/readme-txt.md` — example versions bumped to WP 7.0 / PHP 7.4; documented the `Requires Plugins` dependency header and the ahead-or-behind currency rule.
- `wp-plugin-release` + `wp-org-submission` reference skeletons — `Tested up to` example bumped from 6.7 to 7.0.
- `wp-coding-standards` — noted current WPCS 3.1 (PHP 7.4+, PHP_CodeSniffer 3.9+).
- `wp-ci-qa` — CI matrix bumped to current era: WP `['6.9','7.0','latest']`, PHP `['7.4','8.2','8.3','8.4']`, and pinned actions `actions/checkout@v5` + `actions/cache@v5`.
- `wp-build-tools` — `@wordpress/scripts` example `^30` → `^32`; refreshed Node guidance (v32 needs Node LTS 20/22; GitHub Actions defaults to Node 24 from June 2026; `setup-node@v6`).

### Added

- `wp-plugin-audit` security coverage for the two highest-value WAF-invisible classes: **SSRF** (`wp_safe_remote_*` + host allowlist + `redirection => 0`, DNS-rebinding caveat) and **broken access control / IDOR / privilege escalation** (nonce ≠ authorization; `wp_ajax_nopriv_` privileged actions; ownership checks). Added `phar://` deserialization to Object Injection, renamed Path Traversal → **Path Traversal & LFI**, and added a Patchstack-2025 prevalence note (XSS 35% > CSRF 19% > LFI 13% > broken access control 11% > SQLi 7%; ~43% unauthenticated). Wired both new classes into the Dimension D checklist.

## [1.2.3]

### Added

- `wp-plugin-audit/references/security.md` — advanced security audit patterns: file upload validation, object injection (`unserialize`), SQL injection beyond `$wpdb->prepare()`, secrets storage, REST API auth hardening, open redirect, path traversal, `composer audit`.
- `wp-plugin-audit/references/skill-routing.md` — finding category → skill routing table covering all 18 skills + official dependency; severity-based fix order rules for Step 4.

### Changed

- `wp-plugin-audit` — expanded Dimension D with 8 explicit security checklist items; added Step 4 (route findings to fix skill) to the method; wired two new reference files; fixed `Not for:` to point to `wp-phpstan` (official) instead of `wp-phpstan-stubs`.
- `README.md` — rewritten around the full WP plugin development lifecycle (Build / Test & Audit / Ship) instead of a flat alphabetical skill list.

## [1.2.2]

### Fixed

- `validate_skills.py` — extended version-sync check to cover `gemini-extension.json` and `package.json` (previously only `plugin.json` ↔ `marketplace.json` were checked).
- 8 skills (`wp-admin-browser`, `wp-ci-qa`, `wp-email-templates`, `wp-github-flow`, `wp-guided-tour`, `wp-plugin-audit`, `wp-plugin-release`, `wp-phpstan-stubs`) — added `Not for:` suffix to frontmatter descriptions and `**Not for:**` body blocks; added missing `## When to use` sections in `wp-admin-browser` and `wp-ci-qa`; fixed `NOT for` → `Not for:` casing in `wp-phpstan-stubs`.
- `CONTRIBUTING.md` — clarified `version` and `compatibility` frontmatter fields are optional; updated CI section to list all 4 version-checked files.

### Added

- `wp-guided-tour/references/` — 3 new reference files: `scope-detection.md`, `php-tour-config.md`, `driver-js-lifecycle.md`; wired `## References` section in SKILL.md.

## [1.2.1]

### Added

- `wp-guided-tour` skill — Driver.js v1 IIFE setup, PHP backend tour configs, JS scope detection from URL query + hash routing, completion tracking via last-step `onNextClick` (not `onDestroyed`), selector rules (`:first-of-type` pitfall, Tailwind escaping), browser verification checklist, and post-impl POT regeneration reminder.

### Changed

- `wp-admin-browser` — added "JS State Verification" section: checking WP globals/scripts loaded, SPA React render timing (2 s wait pattern), batch CSS selector testing, localStorage manipulation for feature testing, maintenance mode detection, and session expiry re-login snippet.
- All 18 skills — added `Model note` block at the top of each skill body indicating the appropriate model tier (`haiku` / `sonnet` / `opus`) for that skill's tasks. `wp-plugin-audit` dimension agents now explicitly dispatch with `model: haiku`.

## [1.1.0]

### Added

- `wp-org-submission` skill — WordPress.org directory submission (review checklist, readme.txt rules) and SVN deploy (trunk/tags/assets, Stable-tag mechanics) with a `svn-deploy.sh` helper.
- CI workflow (`validate.yml`) and `validate_skills.py` — checks JSON manifests, version sync, and SKILL.md frontmatter on every push/PR.
- Repo docs: README skill table, CONTRIBUTING, issue/PR templates.

### Changed

- Renamed plugin and repository from `claude-skills` to `wp-dev-skills`.

## [1.0.0]

### Added

- Initial release with 8 skills: `wp-ci-qa`, `wp-github-flow`, `wp-phpstan-stubs`, `wp-admin-browser`, `wp-email-templates`, `wp-phpunit-redirect`, `wp-plugin-audit`, `wp-plugin-release`.
- Plugin manifest + standalone marketplace manifest.
