# Audit Checklist & Report Template

Concrete checks per dimension, the `grep`s that surface them, and the report shape.

## Dimension A — Version & metadata

```bash
grep -n "Version:\|Requires at least\|Requires PHP\|Tested up to\|Text Domain" *.php
grep -n "_VERSION'" *.php
grep -n "Stable tag\|Requires\|Tested up to" readme.txt
grep -n '"php"\|"version"\|"license"\|psr-4' composer.json
grep -n "Project-Id-Version" languages/*.pot
grep -rn "db_version\|schema_version" includes/ src/
grep -rIn "[0-9]\+\.[0-9]\+\.[0-9]\+" --include=*.php --include=readme.txt .   # stray version strings
```

Cross-check: header `Version` == version constant == `readme.txt` Stable tag. `Requires PHP` consistent across header/readme/composer. Changelog has an entry for the current version. Flag mismatches. **Do not flag** schema `$db_version` for differing from plugin version — it's independent.

## Dimension B — Naming / prefix / i18n

```bash
# legacy prefixes outside the migration file
grep -rn "oldprefix_\|legacy_" includes/ templates/ | grep -v Migration
# text domain on every translation call
grep -rn "__(\|_e(\|esc_html__(\|esc_attr__(\|_n(\|_x(" includes/ templates/
# @package variants
grep -rn "@package" includes/ templates/ *.php
# identifier prefixes
grep -rn "register_rest_route\|add_option\|set_transient\|add_action\|add_filter\|wp_enqueue_\|wp_create_nonce\|wp_nonce_field" includes/
```

Check: one canonical prefix everywhere; one text domain on every string; translator comments on `sprintf`/`printf` with placeholders; uniform `@package`; consistent option/transient/hook/REST/cookie/nonce/handle/CSS-class prefixes.

## Dimension C — Docs ↔ code

```bash
grep -rn "src/\|composer test:\|tests/" CLAUDE.md README.md readme.txt docs/
# verify documented composer scripts exist
sed -n '/"scripts"/,/}/p' composer.json
# verify documented test counts
grep -rc 'public function test' tests --include=*Test.php | awk -F: '{s+=$2} END{print s}'
```

Check: renamed dirs/functions/options/tables; documented commands actually exist; test counts current; architecture trees list real files; behavior claims match code. Separate *historical* docs (leave) from *current* docs (must match).

## Dimension D — Code conventions

```bash
grep -rn "->update(\|->insert(\|\$wpdb->" includes/      # DB-write style
grep -rn "current_user_can\|check_ajax_referer\|wp_verify_nonce\|permission_callback" includes/
grep -rn "@since\|@package" includes/
```

Check: DB-write style matches the repo's CLAUDE.md rule; capability/nonce coverage on every privileged action; consistent return types / docblock style; no leftover renamed-dir refs; duplicated logic. Tag bug-risk / convention / cosmetic.

## Verify-before-report (mandatory)

Agents over-report. For each candidate, `Read` the exact line and confirm. Common false positives to kill:
- "Missing translator comment" on a `sprintf` whose format is pure HTML (`'<a href="%s">%s</a>'`) — not translatable.
- "Inconsistent prefix" on the migration file's intentional legacy names.
- "No capability check" on an HMAC-verified public webhook.
- "ORM interpolation" where the ORM method actually routes to `$wpdb->update()` internally.

## Report template

```markdown
# Plugin Audit — Inconsistencies Report

## 🔴 Functional — <will break / wrong behavior>
| # | Finding | Location | Reality |

## 🟠 Security / i18n
| # | Finding | Location |

## 🟡 Stale docs / naming
| # | Finding | Location | Reality |

## ⚪ Cosmetic / convention
| # | Finding | Location |

## ✅ Checked, NOT bugs (don't "fix")
- <intentional pattern + why>

## Suggested fix priority
1. 🔴 … 2. 🟠 … 3. 🟡 … 4. ⚪ …
```

Report only — do not fix unless asked.
