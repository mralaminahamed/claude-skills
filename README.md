# claude-skills

[![Validate](https://github.com/mralaminahamed/claude-skills/actions/workflows/validate.yml/badge.svg)](https://github.com/mralaminahamed/claude-skills/actions/workflows/validate.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

WordPress plugin development and GitHub contribution [skills](https://docs.claude.com/en/docs/claude-code/skills) for [Claude Code](https://claude.com/claude-code) — shipped as an installable plugin **and** a standalone marketplace.

## Skills

| Skill | Use when |
|-------|----------|
| **fix-pr-qa-failures** | A PR has QA-reported failures, a "Testing Failed" label, or QA comments saying features are broken. Read feedback, trace root causes, apply scoped commits, update labels, post a re-test comment. |
| **github-contribution-flow** | Shipping a contribution through GitHub — debug an issue by URL/number, or turn uncommitted changes into scoped conventional commits, a branch, and a PR with assignee + labels. |
| **phpstan-stubs-scaffold** | Creating a new PHPStan stubs package. Scaffolds the full standard structure. |
| **wp-admin-browser** | Driving a WordPress admin panel via Chrome DevTools MCP — login, create users, navigate menus, submit forms, CRUD through the browser. |
| **wp-email-templates** | Adding or refactoring transactional emails in a WP plugin — extract inline strings into reusable branded HTML templates sent via `wp_mail()`. |
| **wp-phpunit-redirect-harness** | WP PHPUnit tests hit `wp_safe_redirect()`/`wp_redirect()` + `exit`, or the suite stops early with no summary. Installs a throwing-filter harness so redirect+exit paths become assertable. |
| **wp-plugin-audit** | Auditing a WP plugin for inconsistencies — fans out parallel checks across dimensions and verifies every finding before reporting. |
| **wp-plugin-release** | Bumping/releasing a WP plugin version — keeps Stable tag, header, constant, and readme/changelog coherent. |
| **wp-org-plugin-submission** | Submitting a plugin to the WordPress.org directory for the first time, or deploying a new version via SVN — review checklist, readme.txt rules, trunk/tags/assets, Stable tag mechanics. |

## Install

Add the marketplace, then install the plugin:

```
/plugin marketplace add mralaminahamed/claude-skills
/plugin install claude-skills@claude-skills
```

Or from a local clone:

```
/plugin marketplace add ~/Projects/claude-plugins/claude-skills-repo
/plugin install claude-skills@claude-skills
```

Skills activate automatically when their description matches what you're doing. Claude Code picks them up on the next session.

## Layout

```
claude-skills/
├── .claude-plugin/
│   ├── plugin.json         # plugin manifest
│   └── marketplace.json    # standalone marketplace manifest
└── skills/
    └── <skill-name>/
        ├── SKILL.md         # required — frontmatter: name, description
        ├── references/      # optional supporting docs
        └── scripts/         # optional helper scripts
```

## Develop

Each skill is a directory under [`skills/`](skills) with a `SKILL.md`. Edit the `SKILL.md` (and any `references/` or `scripts/`) — the change is live on the next Claude Code session.

See [CONTRIBUTING.md](CONTRIBUTING.md) for skill authoring conventions and the validation rules CI enforces.

## Versioning

The plugin version lives in two manifests — keep them in sync on release:

- `.claude-plugin/plugin.json` → `version`
- `.claude-plugin/marketplace.json` → `plugins[0].version`

## License

[MIT](LICENSE) © Al Amin Ahamed
