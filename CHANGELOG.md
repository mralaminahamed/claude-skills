# Changelog

All notable changes to **wp-dev-skills** are documented here. This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `wp-guided-tour` skill — Driver.js v1 IIFE setup, PHP backend tour configs, JS scope detection from URL query + hash routing, completion tracking via last-step `onNextClick` (not `onDestroyed`), selector rules (`:first-of-type` pitfall, Tailwind escaping), browser verification checklist, and post-impl POT regeneration reminder.

### Changed

- `wp-admin-browser` — added "JS State Verification" section: checking WP globals/scripts loaded, SPA React render timing (2 s wait pattern), batch CSS selector testing, localStorage manipulation for feature testing, maintenance mode detection, and session expiry re-login snippet.

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
