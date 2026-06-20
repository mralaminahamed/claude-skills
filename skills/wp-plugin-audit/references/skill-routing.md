# Skill Routing — Audit Finding → Fix Skill

After the audit report, route each finding category to the correct skill for fixing. Reference this table when the user asks to fix all or a subset of findings.

| Finding category | Severity | Skill to invoke | Notes |
|---|---|---|---|
| Version / metadata drift | 🔴 | `wp-plugin-release` | Keeps header, constant, Stable tag, changelog coherent |
| Escaping / sanitization violation | 🟠 | `wp-coding-standards` | `phpcbf` auto-fixes many; manual fixes guided by `references/escaping-sanitization.md` |
| Missing / wrong nonce or capability check | 🟠 | `wp-plugin-development` (official) | `references/capability-nonce.md` for patterns |
| File upload security gap | 🟠 | — | Follow `references/security.md` file-upload section |
| `unserialize()` on user input | 🟠 | — | Follow `references/security.md` object-injection section |
| SQL injection (ORDER BY / table name) | 🟠 | — | Follow `references/security.md` SQL section |
| Open redirect | 🟠 | — | Follow `references/security.md` open-redirect section |
| Path traversal | 🟠 | — | Follow `references/security.md` path-traversal section |
| REST API auth gap | 🟠 | `wp-rest-api` (official) | `references/security.md` REST section for advanced patterns |
| Secrets / API keys exposed | 🟠 | — | Follow `references/security.md` secrets section |
| Dependency CVE | 🟠 | — | `composer audit`; follow `references/security.md` |
| i18n violation (missing `__()`, wrong domain, no translator comment) | 🟡 | `wp-i18n-workflow` | POT regeneration + PO/MO recompile after fix |
| Naming / prefix inconsistency | 🟡 | — | Mechanical rename; verify via `wp-coding-standards` PHPCS run after |
| PHPStan type error / missing types | 🟡 | `wp-phpstan` (official) | For stubs packages for third-party plugins: `wp-phpstan-stubs` |
| Docs ↔ code mismatch | 🟡 | `wp-github-flow` | Scope into a scoped conventional commit; open PR |
| DB convention violation | 🟡 | `wp-database` | `dbDelta` patterns, migration versioning |
| Missing or failing tests | 🟡 | `wp-plugin-testing` | PHPUnit / BrainMonkey scaffolding |
| Multisite compat gap | 🟡 | `wp-multisite` | `switch_to_blog()`, network option patterns |
| Background job / cron issue | 🟡 | `wp-background-processing` | Action Scheduler / `WP_Background_Process` |
| Performance issue (slow query, autoloaded option) | 🟡 | `wp-performance` (official) | Query Monitor profiling |
| WP.org rejection pattern (GPL, naming, trialware) | 🟡 | `wp-org-submission` + `wp-plugin-directory-guidelines` (official) | 18 official rules live in the official skill |
| Build / asset convention drift | ⚪ | `wp-build-tools` | `.asset.php` enqueue, entry point config |
| Plugin header format | ⚪ | — | Mechanical PHPDoc block reformat |
| Cosmetic / comment style | ⚪ | `wp-coding-standards` | `phpcbf` handles most |

## Routing rules

1. Fix 🔴 (functional) before anything else — broken plugin ships no value.
2. Fix 🟠 (security/i18n) before release — these are public commitments.
3. Batch 🟡 (stale/naming) into a single scoped commit via `wp-github-flow`.
4. ⚪ (cosmetic) — fix only if `phpcbf` can do it automatically; skip manual cosmetics.
5. For each 🟠 security finding, check `references/security.md` first. If the fix requires cross-skill expertise (e.g. REST auth) invoke the linked skill directly.
6. After all fixes: re-run the audit (Step 1–2) to confirm no regressions introduced.
