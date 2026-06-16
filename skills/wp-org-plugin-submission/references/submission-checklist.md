# Pre-submission checklist — WordPress.org plugin directory

Work top to bottom before the first submission. Most rejections come from sections 2 and 3.

## 1. Identity & slug

- Main plugin file header `Plugin Name:` sets the public name; the directory derives the permanent **slug** from it.
- Confirm the slug is free: `https://wordpress.org/plugins/<slug>/` returns 404.
- Do **not** use "WordPress", "WooCommerce", "Woo", or other trademarks in the name. "X for WooCommerce" is acceptable; "WooCommerce X" is not.
- One plugin per submission. No "framework" submissions that do nothing on their own.

## 2. readme.txt (must pass the validator)

Validate at `https://wordpress.org/plugins/developers/readme-validator/`.

Required header block:

```
=== Plugin Name ===
Contributors: wporg_username
Tags: tag1, tag2, tag3
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Short description, max 150 chars, single line.
```

- `Stable tag` must equal the released version (and a matching `tags/<version>/` dir after deploy).
- Max **5** tags; unused/spammy tags hurt.
- Required sections: `== Description ==`, `== Installation ==`, `== Frequently Asked Questions ==`, `== Screenshots ==`, `== Changelog ==`. `== Upgrade Notice ==` recommended.
- Each `== Screenshots ==` numbered line maps to `assets/screenshot-N.png` (N matches the list order).

## 3. Guideline compliance (the 18 guidelines, distilled)

- **GPL-compatible** — all code, bundled libraries, images, and fonts.
- **No external code loading** — bundle JS/CSS; don't pull executable code from a CDN/remote at runtime.
- **No phoning home without consent** — any external API call, analytics, or telemetry needs clear disclosure and explicit opt-in. Document every external service in the readme.
- **Security** — sanitize all input (`sanitize_text_field`, `absint`, etc.), escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`), use nonces + capability checks on every state-changing action, prepared statements for all SQL (`$wpdb->prepare`).
- **Prefix everything** — functions, classes, constants, options, globals, hooks. Generic names (`init`, `register`, `$options`) collide.
- **No obfuscation / no minified-only** — human-readable source must be present.
- **No trialware / no "powered by" links** without opt-in. No admin nags that can't be dismissed.
- **No tracking pixels** or undisclosed user-data collection.
- **Sane file footprint** — no executables, no unrelated files. Don't include `node_modules`, build tooling, or `.git` in the zip.
- **Respect the user's site** — no modifying other plugins/core, no creating admin pages that hijack the dashboard.

## 4. Build the submission zip

Ship only production files. Exclude dev artifacts with a `.distignore` (WP-CLI `dist-archive`) or an explicit export:

```
# .distignore
/.git
/.github
/node_modules
/tests
/bin
.distignore
.editorconfig
phpunit.xml
phpcs.xml
phpstan.neon
composer.json
composer.lock
package.json
package-lock.json
```

```bash
wp dist-archive . ./build/<slug>.zip        # respects .distignore
# or, without WP-CLI:
git archive --format=zip --prefix=<slug>/ -o build/<slug>.zip HEAD
```

The zip's top-level folder must be the slug, containing the main plugin file at its root.

## 5. Submit & review

- Submit the zip at `https://wordpress.org/plugins/developers/add/`.
- Review is by a human volunteer team — expect days to weeks, sometimes longer.
- All correspondence goes to `plugins@wordpress.org` ↔ the account email. Reply in-thread; attach the corrected zip (don't resubmit through the form).
- On approval, SVN is provisioned at `https://plugins.svn.wordpress.org/<slug>/`. Proceed to `references/svn-deploy.md`.

## References

- Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- readme.txt spec: https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- Plugin handbook: https://developer.wordpress.org/plugins/
