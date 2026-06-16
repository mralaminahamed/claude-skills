# claude-skills

WordPress plugin development and GitHub contribution [skills](https://docs.claude.com/en/docs/claude-code/skills) for [Claude Code](https://claude.com/claude-code), packaged as an installable plugin and marketplace.

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

## Install

Add the marketplace, then install the plugin:

```
/plugin marketplace add mralaminahamed/claude-skills
/plugin install claude-skills@claude-skills
```

Or install from a local clone:

```
/plugin marketplace add ~/Projects/claude-skills
/plugin install claude-skills@claude-skills
```

## Develop

Skills live in [`skills/`](skills), one directory per skill, each with a `SKILL.md`. Edit the `SKILL.md` (and any supporting `scripts/`, `references/`, `examples/`) and the change is picked up on the next Claude Code session.

## License

[MIT](LICENSE) © Al Amin Ahamed
