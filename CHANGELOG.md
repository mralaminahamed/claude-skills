# Changelog

All notable changes to **wp-dev-skills** are documented here. This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

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
