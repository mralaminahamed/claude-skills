# Pre-submission checklist — WordPress.org plugin directory

Work top-to-bottom before generating the release zip. Sourced from 8 real plugin submissions; most rejections trace to §§3–5. See `review-issues-catalog.md` for reviewer quotes and corrective patterns for each item.

## 1. Identity & slug

- [ ] Plugin display name does **not** start with a generic word (`AI`, `Easy`, `Simple`, `Advanced`, `WordPress`, `WP`, `Plugin`)
- [ ] Display name and slug are distinguishable from existing plugins (search Google, DuckDuckGo, and `wordpress.org/plugins/`)
- [ ] Trademarks / third-party names appear **at the end** after `for` or `with` — never at the start
- [ ] Plugin slug, plugin folder name, and main PHP file name are **identical** (e.g. `my-plugin/my-plugin.php`)
- [ ] Main PHP file is **not** named `plugin.php`, `index.php`, or any generic name
- [ ] `Plugin URI`, `Author URI`, and `Terms`/`Privacy` URLs in `readme.txt` all return HTTP 200 within 5 s — verify with `curl -I`
- [ ] Text domain in every i18n call matches the plugin slug exactly
- [ ] `Tested up to:` reflects the current WordPress release

## 2. readme.txt (must pass the validator)

Validate at `https://wordpress.org/plugins/developers/readme-validator/`.

Required header block:

```
=== Plugin Name ===
Contributors: wporg_username
Tags: tag1, tag2, tag3
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Short description, max 150 chars, single line.
```

- `Stable tag` must equal the released version (matching `tags/<version>/` dir after deploy)
- Max **5** tags; unused/spammy tags hurt
- Required sections: `== Description ==`, `== Installation ==`, `== Frequently Asked Questions ==`, `== Screenshots ==`, `== Changelog ==`
- Each `== Screenshots ==` numbered line maps to `assets/screenshot-N.png`
- Every external service documented under `== External services ==` with Terms + Privacy URLs (both must return HTTP 200)

## 3. Guideline compliance

- [ ] All code, bundled libraries, images, and fonts are **GPL-compatible**
- [ ] No external code loading — bundle JS/CSS; no CDN calls (jQuery.com, jsDelivr, Google-hosted assets)
- [ ] No phoning home without consent — every external API call disclosed in `== External services ==` with opt-in
- [ ] **Security** — see §5 for the full nonce/capability/sanitize/escape pattern
- [ ] Every function, class, constant, option, hook, and JS global uses a **single** project-specific prefix of **4+ characters** (not `wp_`, `_`, `__`)
- [ ] No `if ( ! function_exists( 'NAME' ) )` wrappers around plugin-own functions
- [ ] **No obfuscation / no minified-only** — source must be in the zip or publicly linked; see §4
- [ ] **No trialware (Guideline 5)** — the free plugin must be 100% functional; no features gated behind a license key, upgrade nag, or Pro check. Freemium = separate Pro plugin hosted on your own site. See `trialware-compliance.md`
- [ ] No "powered by" links without opt-in. No admin notices that can't be dismissed
- [ ] No undisclosed user-data collection or tracking pixels
- [ ] No `unlink()` — use `wp_delete_file()`
- [ ] No `file_get_contents()` for remote URLs — use `wp_remote_get()`
- [ ] No `curl_*()` — use the WordPress HTTP API
- [ ] No inline `<style>` / `<script>` tags — use `wp_enqueue_*`, `wp_add_inline_script`, `wp_add_inline_style`
- [ ] No includes of core loading files (`wp-config.php`, `wp-load.php`, `wp-blog-header.php`)
- [ ] No hijacking the admin dashboard — notices scoped to plugin's own screens, dismissible, never persistent nags on unrelated pages (Guideline 11)

## 4. Production zip — source code

**Source code must ship in the zip.** Reviewers verify GPL compliance by reading source. If you have a compiled JS/CSS build, include `src/`, `webpack.config.js`, `package.json`, `composer.json` — or link a public repo in `readme.txt`.

Keep in zip (required for review):

- `src/` — JS/TS/CSS source
- `composer.json`, `composer.lock`
- `package.json`, `yarn.lock` / `package-lock.json`
- `webpack.config.js`, `phpcs.xml`, `phpstan.neon`

Exclude from zip (dev-only):

- `.git`, `.github`, `node_modules`
- `tests/`, `bin/`, `coverage/`
- `.wordpress-org/` — **never** in the zip; assets ship via SVN `/assets/` only
- `CLAUDE.md`, `.editorconfig`, `*.log`, `release/`, `docs/`

Minimal `.distignore`:

```
/.git
/.github
/node_modules
/tests
/bin
/release
/.wordpress-org
/coverage
CLAUDE.md
*.log
```

**Never exclude `vendor/` when the plugin loads it at runtime.** A Composer-autoloaded plugin whose bootstrap does `require __DIR__ . '/vendor/autoload.php';` (and bails without it) **must ship `vendor/`** in the distribution. Adding `vendor/` to `.distignore` produces a zip that fatals on activation — the deploy looks clean but every install is broken. Only exclude `vendor/` for plugins that don't autoload at runtime.

**Always anchor patterns with a leading `/`.** `.distignore` feeds `rsync --exclude-from`, so an unanchored `src/` matches `src/` at **any** depth — including `vendor/<pkg>/src` — silently stripping files out of shipped Composer packages. Anchor every top-level entry (`/src`, not `src/`); reserve unanchored patterns for things you genuinely want gone everywhere (`.DS_Store`, `*.log`).

Build check after zip:

```bash
# Must return no output
unzip -l release/<slug>.zip | grep -E '\.wordpress-org/|/tests/|/node_modules/|/\.git/|/\.github/|/bin/|/coverage/'
```

## 5. Security pattern

```php
// Every form/AJAX/state-changing action:
if ( ! isset( $_POST['my_nonce'] )
    || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['my_nonce'] ) ), 'my_action' )
) {
    wp_die( esc_html__( 'Security check failed.', 'my-plugin' ), 403 );
}
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Forbidden.', 'my-plugin' ), 403 );
}

// Sanitize early (with wp_unslash first):
$val = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );

// Escape late — context-appropriate:
echo esc_html( $plain_text );
echo esc_attr( $attribute );
echo esc_url( $url );
echo wp_kses_post( $html );       // not esc_html() for HTML
```

Escape function quick reference:

| Context | Function |
|---|---|
| Plain text inside HTML | `esc_html()` |
| HTML attribute | `esc_attr()` |
| URL in href/src | `esc_url()` |
| URL to database | `esc_url_raw()` |
| HTML body content | `wp_kses_post()` |
| HTML with custom allowed tags | `wp_kses( $html, $allowed )` |
| Inline JS | `esc_js()` |
| Textarea content | `esc_textarea()` |

REST routes must have explicit `permission_callback`; intentionally public routes use `'__return_true'` with an inline comment explaining why.

## 6. Automated verification

```bash
composer phpcs      # zero errors
composer phpstan    # zero errors
composer test       # all pass
```

Run the [Plugin Check](https://wordpress.org/plugins/plugin-check/) plugin on a clean WordPress install — zero errors, "Plugin Repo" category clean.

URL reachability:

```bash
for url in 'https://<plugin-uri>' 'https://<author-uri>' 'https://<service>/terms' 'https://<service>/privacy'; do
    echo "$(curl -o /dev/null -s -w '%{http_code}' --max-time 5 "$url")  $url"
done
```

All must return `200`.

## 7. Submit & reply

- Submit zip at `https://wordpress.org/plugins/developers/add/`
- Reply in the **same email thread** — do not re-submit via the form
- Replies must be **brief**: 2–4 sentences max. Provide context and clarifications only — no change lists (reviewers re-review the entire plugin every cycle)
- Slug changes require an explicit line: *"Please change the slug to `new-slug`."*
- Three-month timeout — unresolved issues = rejected slug (burned permanently)

See `review-issues-catalog.md` for the full 17-issue catalog with exact reviewer quotes.

## References

- Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- readme.txt spec: https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- Plugin handbook: https://developer.wordpress.org/plugins/
