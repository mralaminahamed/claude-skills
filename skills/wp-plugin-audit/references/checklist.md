# Audit Checklist & Report Template

Concrete checks per dimension, the `grep`s that surface them, and the report shape.

## Dimension A — Version & metadata

```bash
# Version fields across sources
grep -n "Version:\|Requires at least\|Requires PHP\|Tested up to\|Text Domain" *.php
grep -n "_VERSION'" *.php
grep -n "Stable tag\|Requires\|Tested up to" readme.txt
grep -n '"php"\|"version"\|"license"\|psr-4' composer.json
grep -n "Project-Id-Version" languages/*.pot
grep -rn "db_version\|schema_version" includes/ src/
grep -rIn "[0-9]\+\.[0-9]\+\.[0-9]\+" --include=*.php --include=readme.txt .   # stray version strings

# Plugin file header format checks (run on the main plugin file)
MAIN_PLUGIN=$(find . -maxdepth 1 -name "*.php" | xargs grep -l "Plugin Name:" 2>/dev/null | head -1)
# PHPDoc block style (/** vs /*)
grep -n "^/\*\*\|^ \* @wordpress-plugin\|^ \* @package\|^ \* @author\|^ \* @copyright\|^ \* @license" "$MAIN_PLUGIN"
# Description length check
grep -n "Description:" "$MAIN_PLUGIN"
# License field vs License URI consistency
grep -n "License:" "$MAIN_PLUGIN"
```

Cross-check:
- Header `Version` == version constant == `readme.txt` Stable tag.
- `Requires PHP` consistent across header/readme/composer.
- Changelog has an entry for the current version.
- Plugin file uses `/** */` PHPDoc block (not plain `/* */`).
- PHPDoc block contains `@wordpress-plugin` marker, `@package`, `@author`, `@copyright`, `@license` fields.
- `Description` ≤ 140 characters.
- `License` slug is consistent with `License URI` (e.g. `GPL v2 or later` → `gpl-2.0.txt`).
- `Text Domain` present when plugin has `__()` / `_e()` calls.

Flag mismatches. **Do not flag** schema `$db_version` for differing from plugin version — it's independent.

### Canonical header format (reference)

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
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * Text Domain:       plugin-slug
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://example.com/my-plugin/
 * Requires Plugins:  my-plugin, yet-another-plugin
 */
```

## Dimension B — Naming / prefix / i18n

```bash
# legacy prefixes outside the migration file
grep -rn "oldprefix_\|legacy_" includes/ src/ templates/ | grep -v Migration
# text domain on every translation call
grep -rn "__(\|_e(\|esc_html__(\|esc_attr__(\|_n(\|_x(" includes/ src/ templates/
# @package variants
grep -rn "@package" includes/ src/ templates/ *.php
# identifier prefixes
grep -rn "register_rest_route\|add_option\|set_transient\|add_action\|add_filter\|wp_enqueue_\|wp_create_nonce\|wp_nonce_field" includes/ src/
# sprintf/printf with translatable strings — must have translator comment on preceding line
grep -rn "printf\s*(.*__(\|sprintf\s*(.*__(" --include=*.php includes/ src/ templates/
# variables embedded directly in translatable strings (wrong — use placeholders)
grep -rn "__(\s*\"[^\"]*\$\|__(\s*'"'"'[^'"'"']*\$" --include=*.php includes/ src/ templates/
```

Check: one canonical prefix everywhere; one text domain on every string; translator comments (`/* translators: ... */`) on line immediately before every `sprintf`/`printf` with a translatable string containing `%s`/`%d`/`%1$s` etc.; uniform `@package` matching the plugin's declared `@package` in the file header PHPDoc; consistent option/transient/hook/REST/cookie/nonce/handle/CSS-class prefixes. See `references/i18n-translator-comments.md` for full function list and placement rules.

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
grep -rn "->update(\|->insert(\|\$wpdb->" includes/ src/                    # DB-write style
grep -rn "current_user_can\|check_ajax_referer\|wp_verify_nonce\|permission_callback" includes/ src/
grep -rn "@since\|@param\|@return" includes/ src/ templates/                # docblock completeness
# Escaping / sanitization
grep -rn "echo \$_GET\|echo \$_POST\|echo \$_REQUEST" --include=*.php .    # raw output
grep -rn "echo get_option\|echo get_post_meta" --include=*.php .            # unescaped option
grep -rn '\$wpdb->query\s*(\s*"' --include=*.php .                          # raw SQL (SQLi)
grep -rn 'href=.*esc_html\|src=.*esc_html' --include=*.php .               # wrong escape fn on URL
grep -rn 'href="<?php echo\|src="<?php echo' --include=*.php . | grep -v esc_url  # missing esc_url
```

Check: DB-write style matches the repo's CLAUDE.md rule; capability/nonce coverage on every privileged action; consistent `@since`/`@param`/`@return` docblock style; no raw user-input output; no unescaped option echo; no unparameterised `$wpdb->query`. Tag each: bug-risk / convention / cosmetic. See `references/escaping-sanitization.md` for full context table and `references/capability-nonce.md` for false-positive patterns.

## Verify-before-report (mandatory)

Agents over-report. For each candidate, `Read` the exact line and confirm. Common false positives to kill:
- "Missing translator comment" on a `sprintf` whose format is pure HTML (`'<a href="%s">%s</a>'`) — not translatable.
- "Inconsistent prefix" on the migration file's intentional legacy names.
- "No capability check" on an HMAC-verified public webhook — HMAC is the auth mechanism.
- `wp_ajax_nopriv_` handler with no `current_user_can()` — only a bug if it **writes data**; read-only public AJAX is intentional.
- `permission_callback` returning `true` on a truly public GET endpoint — intentional; must be documented.
- "ORM interpolation" where the ORM method actually routes to `$wpdb->update()` internally.
- Plain `/* Plugin Name: ... */` block comment (not `/** @wordpress-plugin */` PHPDoc style) in code generated by the official `wp-plugin-development` skill — that skill does not prescribe a header format; flag only if the file is hand-authored and the project has adopted the PHPDoc style.

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
