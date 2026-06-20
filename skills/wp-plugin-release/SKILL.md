---
name: wp-plugin-release
description: Use when bumping or releasing a WordPress plugin version, syncing version numbers, or updating readme.txt / changelog. Keeps every version source coherent so Stable tag, header, and constant never drift. Not for: WP.org SVN deploy or submitting a new plugin — use `wp-org-submission` for that.
---

# WordPress Plugin Release / Version Sync

> **Model note:** Mechanical file edits — works well on `haiku`. No reasoning across ambiguous code; all sources are explicit (header, constant, readme.txt, changelog).

Bump a WP plugin version coherently. Prevents the classic drift where the plugin header says one version, the `readme.txt` `Stable tag` another, and the `.pot` a third.

## When to use

- "Release X.Y.Z", "bump the version", "update the changelog / readme.txt".
- After substantial work has landed under an unreleased version.

**Not for:** WP.org SVN deploy (trunk/tags/assets push) — use `wp-org-submission`. First-time plugin submission to the WP.org directory — use `wp-org-submission`.

## First: determine the real current state

```bash
git tag                       # any release tags?
gh release list               # any published releases?
grep -n "Version:" *.php       # plugin header
grep -n "_VERSION'" *.php       # version constant
grep -n "Stable tag" readme.txt
```

If header/constant/Stable-tag disagree, that drift IS the problem — pick the target version and sync all of them. Choose the bump by semver: new backward-compatible features → minor; fixes only → patch; breaking → major. Internal-only refactors (dir rename) don't force a major.

## Sources to update (all, in lockstep)

1. **Plugin header** `* Version: X.Y.Z` (main plugin file).
2. **Version constant** `define( 'PLUGIN_VERSION', 'X.Y.Z' )`.
3. **`readme.txt` `Stable tag: X.Y.Z`** — and `Tested up to` / `Requires PHP` if they changed.
4. **`readme.txt` Changelog** — add a `= X.Y.Z =` block listing what shipped (security, features, fixes), grouped.
5. **`readme.txt` Upgrade Notice** — add `= X.Y.Z =` one-liner (why upgrade).
6. **`.pot`** — regenerate so `Project-Id-Version` matches and new strings are captured:
   ```bash
   composer makepot           # or: wp i18n make-pot . languages/<slug>.pot --exclude=...
   ```

## Do NOT bump

- **Schema / DB version** (e.g. `LicenseModel::$db_version`) — independent of plugin version. Only bump when the table actually changed, since it gates data migrations.
- Historical changelog entries or point-in-time docs.

## Verify

```bash
composer lint && composer analyze && composer test
grep -rn "X\.Y\.Z\|<old version>" --include=*.php --include=readme.txt .   # confirm sync, spot stragglers
```

Then commit (`docs:`/`chore:` for a pure version+readme bump), and ship via the repo's contribution flow (branch → PR → merge; never squash if the repo says so).

## References

- `references/readme-txt-skeleton.txt` — full WP.org `readme.txt` skeleton (all sections) with the release-sync checklist of every version source baked in as a trailing comment.
