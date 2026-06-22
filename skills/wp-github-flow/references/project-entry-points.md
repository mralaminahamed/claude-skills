# Project Entry Points & Conventions

> **Project-specific file.** This is a template — fill it in for your project and keep it in your project's `.claude/` directory (or commit it to the repo as `CLAUDE.md` content). `wp-github-flow` uses these conventions to correctly attribute issues, locate code, and scope commits.

## Repos

| Local plugin dir (`wp-content/plugins/`) | GitHub repo | What it is |
|---|---|---|
| `my-plugin/` | `my-org/my-plugin` | Free version. Issues tracked here. |
| `my-plugin-pro/` | `my-org/my-plugin-pro` | Pro version. Code + PRs may live here even for free-repo issues. |

Confirm: `git -C <plugin-dir> remote get-url origin`

## Commit Scopes

Scopes are project-defined. List valid scopes so the agent picks the right one:

```
scope-a, scope-b, scope-c, build, i18n
```

## Deprecated Patterns

List function/class/hook aliases that must never be introduced:

| ❌ Deprecated | ✅ Canonical |
|---|---|
| `old_prefix_fn()` | `new_prefix_fn()` |

## Key Entry-Point Files

| Symptom | File to look at |
|---|---|
| Feature X not loading | `path/to/loader.php` |
| Hook Y not firing | `path/to/hooks.php` |
