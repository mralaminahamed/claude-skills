---
name: wp-plugin-audit
description: "Use when asked to audit a WordPress plugin for inconsistencies, run a consistency/quality sweep, or \"find inconsistencies\" across code and docs. Fans out parallel checks across dimensions and verifies every finding before reporting. Not for: PHPStan type analysis — use `wp-phpstan` (official); WP.org pre-submission review — use `wp-org-submission`."
---

# WordPress Plugin Consistency Audit

Read-only audit that surfaces inconsistencies across a WP plugin's code, config, and docs. Optimised for **recall with low false-positive rate**: every candidate is verified against the actual code before it reaches the report.

## When to use

- "Audit the plugin", "find inconsistencies", "consistency/quality sweep".
- Before a release, or after a large refactor, to catch drift.

**Not for:** PHPStan type checking or baseline generation — use `wp-phpstan` (official). WP.org pre-submission review (17 rejection patterns) — use `wp-org-submission` which has that checklist.

## Method

### 1. Fan out — 4 independent dimensions (parallel agents)

Dispatch one read-only agent per dimension (`Explore` type, `model: haiku`), in a single message so they run concurrently. Each returns findings with `file:line`, the inconsistent value, and the expected/canonical form.

> **Model note:** Dimension agents are pure grep/read with no synthesis — `haiku` is faster and cheaper. The main thread (whatever model the user has active) does all verification and reporting.

- **A — Version & metadata.** Cross-reference every version/metadata source: plugin header (`Version`, `Requires at least`, `Requires PHP`, `Tested up to`, `Text Domain`), the version constant, `readme.txt` (`Stable tag` + Changelog + Upgrade Notice), `composer.json`, the `.pot` `Project-Id-Version`, and the schema/DB version. Flag every mismatch; note fields that are *intentionally* independent (schema `$db_version` ≠ plugin version) so they aren't flagged.

  Also audit the **main plugin file header format** against the canonical PHPDoc DocBlock style (preferred over plain block comment):
  ```php
  /**
   * Plugin Name
   *
   * @package           PluginPackage
   * @author            Your Name
   * @copyright         2024 Your Name or Company Name
   * @license           GPL-2.0-or-later
   *
   * @wordpress-plugin
   * Plugin Name:       Plugin Name
   * Plugin URI:        https://example.com/plugin-name
   * Description:       Description of the plugin.
   * Version:           1.0.0
   * Requires at least: 5.2
   * Requires PHP:      7.2
   * Author:            Your Name
   * Author URI:        https://example.com
   * Text Domain:       plugin-slug
   * License:           GPL v2 or later
   * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
   * Update URI:        https://example.com/my-plugin/
   * Requires Plugins:  my-plugin, yet-another-plugin
   */
  ```
  Flag: missing `@wordpress-plugin` marker (distinguishes WP header from plain PHPDoc); missing `@package`/`@author`/`@copyright`/`@license` PHPDoc fields; `Description` over 140 characters; `License` slug not matching `License URI`; missing `Text Domain` when plugin has translated strings (requires Dimension B `__()` detection to confirm strings exist); using a plain `/* */` block instead of `/** */` PHPDoc block.
- **B — Naming / prefix / i18n.** Canonical prefix (e.g. `myplugin_` / `_myplugin_`) — flag legacy prefixes outside the migration file. Text-domain consistency on every `__()`/`_e()`/`esc_html__()`/`_n()`; missing translator comments on `sprintf`/`printf` with placeholders. `@package` tag variants. Option/transient/hook/REST/cookie/nonce/AJAX/asset-handle/CSS-class prefix uniformity.
- **C — Docs ↔ code.** Path references (renamed dirs), function/class/option/table names referenced in docs that no longer match code, documented commands that don't exist (`composer test:unit` etc.), test counts, architecture trees vs real files, behavior claims that contradict code. Distinguish *historical* docs (point-in-time, leave) from *current* docs (must match).
- **D — Code conventions & security.** DB-write style (ORM vs `$wpdb` per the repo's CLAUDE.md), docblock style, `@since` tags, return-type consistency, leftover renamed-dir refs, duplicated logic, escaping/sanitization uniformity. Tag each: bug-risk / convention / cosmetic.

  Also check advanced security patterns (see `references/security.md`):
  - **File uploads** — `move_uploaded_file()` used directly; MIME type read from `$_FILES['type']` (user-supplied); files stored outside `wp-content/uploads/`.
  - **Object injection** — `unserialize()` on any value that originated from user input or a user-writable option.
  - **SQL injection beyond `prepare()`** — dynamic `ORDER BY`, column name, or table name constructed from user input without whitelisting.
  - **Secrets exposure** — API keys hardcoded in source files or stored raw in `wp_options` when user-editable.
  - **Open redirect** — `wp_redirect()` on `$_GET['redirect_to']` without `wp_validate_redirect()`.
  - **Path traversal** — filesystem paths constructed from user input without `realpath()` + base-prefix check.
  - **REST auth gaps** — endpoints that write data with `permission_callback: '__return_true'` and no HMAC/nonce verification.
  - **Dependency CVEs** — note if `composer audit` has not been run or if `composer.lock` is missing from the repo.

Scale dimensions to the plugin; add domain-specific ones (e.g. WooCommerce hook security, multisite option isolation) when relevant.

### 2. Verify EVERY candidate before reporting

Do not trust agent output verbatim — agents over-report. For each finding, `grep`/`Read` the exact line and confirm it is real. Kill false positives. Real example caught this way: an `action_links` `sprintf('<a href="%s">%s</a>', …)` flagged for a "missing translator comment" is **not** translatable (pure HTML markup) → drop it.

### 3. Report (findings only — do NOT fix unless asked)

- Group by severity: 🔴 functional → 🟠 (security/i18n) → 🟡 stale/naming → ⚪ cosmetic.
- Each finding: `file:line`, what's wrong, what's correct.
- Include a **"Checked, NOT bugs"** section listing intentional patterns (so they don't get "fixed" by mistake): e.g. webhook with no capability check (HMAC-protected), ORM `update()` that routes to `$wpdb` internally, intentional legacy prefixes in the migration file.
- End with a suggested fix priority. Then ask whether to fix all or a subset.

### 4. Route findings to fix skills

After the user confirms what to fix, consult `references/skill-routing.md` and invoke the correct skill per finding category. Do not attempt to fix all categories inline — route each to its owner:

- Version drift → `wp-plugin-release`
- Escaping / PHPCS violations → `wp-coding-standards`
- Nonce / capability gaps → `wp-plugin-development` (official)
- Advanced security gaps (file upload, unserialize, secrets, REST auth, redirect, path traversal) → `references/security.md` patterns
- i18n violations → `wp-i18n-workflow`
- PHPStan / type errors → `wp-phpstan` (official); stubs scaffolding → `wp-phpstan-stubs`
- WP.org compliance → `wp-org-submission` + `wp-plugin-directory-guidelines` (official)
- DB convention → `wp-database`
- Missing tests → `wp-plugin-testing`
- Multisite gaps → `wp-multisite`
- Background job issues → `wp-background-processing`
- Docs ↔ code mismatch → `wp-github-flow` (scoped PR)
- Performance issues → `wp-performance` (official)
- Build / asset drift → `wp-build-tools`

Full routing table with severity rules: `references/skill-routing.md`.

## Notes

- Audit is read-only. Run on the current working tree (mention if it includes unmerged changes).
- When fixing afterward: many files span multiple finding-categories — a single cohesive "fix audit findings" commit with an enumerated body is cleaner than fragile per-scope partial staging.
- For authoritative WP.org Plugin Directory guideline compliance (18 official rules: GPL, naming, trialware, external services), use the official `wp-plugin-directory-guidelines` skill (WordPress/agent-skills). This audit covers *code consistency and conventions*; that skill covers *directory submission rules*.

## References

- `references/checklist.md` — per-dimension grep commands, common false positives to kill, and the severity-grouped report template.
- `references/readme-txt.md` — readme.txt required/optional sections, field limits, Stable tag rules, common mistakes, and verification greps.
- `references/escaping-sanitization.md` — escaping functions by context, sanitization functions by input type, late-escape rule, common XSS/SQLi flags.
- `references/capability-nonce.md` — nonce creation/verification patterns, capability map, public webhook exception, false-positive patterns.
- `references/i18n-translator-comments.md` — all i18n function signatures, translator comment format and placement rules, variable/placeholder rules, common flags.
- `references/security.md` — advanced security patterns: file upload validation, object injection, SQL injection beyond `prepare()`, secrets storage, REST auth hardening, open redirect, path traversal, dependency CVE scanning.
- `references/skill-routing.md` — finding category → skill routing table with severity rules; use in Step 4 to dispatch the correct fix skill.
