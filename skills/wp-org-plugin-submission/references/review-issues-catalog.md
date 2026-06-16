# WordPress.org Plugin Review — Issues Catalog

Distilled from 8 real plugin submissions (Squad Modules for Divi, Author Profile Blocks, EasyCommerce FakerPress, Warranty Cart, Swift Menu Duplicator, Alamin AI Provider for OpenCode Zen, Alamin AI Provider for MiniMax, Multi-Account for GiveWP PayPal Donations — June 2023 to May 2026). Each entry includes the exact reviewer language, the rule, and the corrective action.

## Issue 1 — Invalid Plugin / Author / Privacy URLs

**Rule:** Every URL in the plugin header (`Plugin URI`, `Author URI`) and in `readme.txt` (terms, privacy, external service links) must return HTTP 200 within ~5 seconds.

**Reviewer quote:**
> *Plugin URI: `https://github.com/…` — This URL replies us with a 404 HTTP code.*
> *Author URI: `https://alaminahamed.com` — Resolving timed out after 5001 milliseconds.*
> *Terms/Privacy URL: `https://opencode.ai/privacy` — This URL replies us with a 404 HTTP code.*

**Fix:**

```bash
for url in 'https://<plugin-uri>' 'https://<author-uri>' 'https://<service>/terms' 'https://<service>/privacy'; do
    echo "$(curl -o /dev/null -s -w '%{http_code}' --max-time 5 "$url")  $url"
done
```

- GitHub plugin URI: the repo must be **public** — private repos return 404 to unauthenticated requests.
- Self-hosted URIs: verify global reachability; Cloudflare/hosting setups can block non-local IPs.
- If a third-party service has no published terms/privacy page, wait until it does.

---

## Issue 2 — Undocumented Use of an External Service

**Rule:** Every domain the plugin contacts must be disclosed in `== External services ==` in `readme.txt`, even if the developer owns the service.

**Reviewer quote:**
> *Plugins are permitted to require the use of third party/external services as long as they are clearly documented. This is true even if you are the one providing that service.*

**Required format:**

```
== External services ==

This plugin connects to the OpenCode Zen API to:

1. Retrieve the list of available AI models (cached for one hour via WordPress transients)
2. Send text-generation requests using the configured AI model

Service: OpenCode Zen
API endpoint: https://opencode.ai/zen/v1
When data is sent: When a WordPress feature triggers a text-generation request, or when the model cache is refreshed.
Data sent: The API key (in the Authorization header) and the prompt / conversation content.
Terms of Service: https://opencode.ai/terms
Privacy Policy: https://opencode.ai/privacy
```

Both Terms and Privacy URLs must return HTTP 200 (see Issue 1). No marketing copy — plain data-flow language only.

---

## Issue 3 — Plugin Name & Slug Issues

**Rule:** Display names must not start with generic terms (`AI`, `Easy`, `Simple`, `Advanced`, `WP`, `WordPress`), must not be too similar to existing plugins, and must not start with a third-party trademark.

**Reviewer quotes:**
> *The display name begins with the generic term "AI", which is discouraged.*
> *"Classic Menu Duplicator" is very similar to several existing, popular plugins (e.g. "Duplicate Menu", "Menu Duplicator").*

**Fix:**

1. Distinctive term at the start — personal brand prefix (`Alamin`, `Codexpert`, coined name).
2. Trademarks at the end: `Alamin AI Provider for OpenCode Zen` ✓ — `OpenCode Zen AI Provider` ✗
3. Search Google + DuckDuckGo + `wordpress.org/plugins/` before submitting.
4. No portmanteaus (`PricesPress` → rejected).
5. Slug changes during review: reply explicitly — *"Please change the slug to `<new-slug>`."* Slug is permanent after approval.

---

## Issue 4 — Main Plugin File Name Does Not Match Slug

**Rule:** The main plugin file must have the same filename as the plugin folder and slug.

**Reviewer quote:**
> *We expect the main plugin file to have the same name as the plugin folder. If your plugin slug is `ai-provider-for-opencode-zen` we expect `ai-provider-for-opencode-zen.php`. The main file of this plugin is named `plugin.php`.*

**Fix:** Rename the bootstrap file to `<plugin-slug>.php`. Never ship `plugin.php`, `index.php`, `main.php`, `init.php` in the plugin root.

---

## Issue 5 — Unneeded Folders / WP.org Assets in Zip

**Rule:** Dev directories and `.wordpress-org/` must not appear in the production zip. Directory listing images ship via SVN `/assets/` only.

**Reviewer quotes:**
> *Your plugin contains folders and files that typically shouldn't be included in a production release. From your plugin: `ai-provider-for-opencode-zen-build/.wordpress-org`*
> *These files (banners, icons, screenshots for the directory page) are not part of the plugin code and should not be included in your plugin zip file.*

**Fix:**

- `.distignore` must exclude `.wordpress-org/`, `tests/`, `node_modules/`, `.git/`, `.github/`, `release/`
- Release script must `rm -rf release/<slug>/` before each build (prevents re-zipping prior output)
- Top-level folder inside zip = plugin slug exactly (no timestamp prefix, no `-build` suffix)
- `composer.json`, `package.json`, `webpack.config.js` **must remain** in the zip

---

## Issue 6 — No Source for Compiled / Minified Content

**Rule:** Minified JS/CSS must ship alongside readable source, or the source must be publicly linked from `readme.txt`.

**Reviewer quote:**
> *We cannot find a non-compiled version of your javascript and/or css related source code. We require you to make the source code to any compressed files available to the public in an easy to find location, by documenting it in the readme.*

**Fix:**

Option A — include `src/` in the zip alongside `build/`:
- Do not add `src/` to `.distignore`
- Reviewers can then read the source directly from the zip

Option B — public repo + readme note (the section name is a suggestion, not a required heading):

```
== Source code ==

Minified files in `build/` are generated from `src/`. Source:
https://github.com/mralaminahamed/<plugin-slug>

Build: composer install && yarn install && yarn build
```

Repo must be **public** at submission time.

---

## Issue 7 — Out-of-Date Bundled Libraries

**Rule:** Bundled third-party libraries must be at their latest stable release.

**Reviewer quote:**
> *At least one of the 3rd party libraries you're using is out of date. From your plugin: `dompdf/dompdf v2.0.8` — current is `v3.1.4`.*

**Fix:** Before submission, run `composer outdated` and audit every vendored JS. No betas or RC versions.

---

## Issue 8 — Improper Asset Enqueuing

**Rule:** All JS and CSS must use the WordPress enqueue API — no inline `<style>` / `<script>` tags, no `echo '<style>…</style>'`.

**Reviewer quote:**
> *Your plugin is not correctly including JS and/or CSS.*
> *`templates/admin/inline-styles.php:12 <style id="swmd-inline-styles">`*
> *`includes/Admin/Product/Columns.php:86 echo '<style>table.wp-list-table .column-warranty_type { width: 8%;}</style>';`*

**Fix:**

| Need | Use |
|---|---|
| Register + enqueue JS | `wp_register_script()` + `wp_enqueue_script()` |
| Register + enqueue CSS | `wp_register_style()` + `wp_enqueue_style()` |
| Inline JS attached to handle | `wp_add_inline_script()` |
| Inline CSS attached to handle | `wp_add_inline_style()` |
| Pass PHP data to JS | `wp_localize_script()` |
| Frontend | hook `wp_enqueue_scripts` |
| Admin | hook `admin_enqueue_scripts` (check `$hook_suffix`) |

---

## Issue 9 — Remote CDN / External Asset Loading

**Rule:** No loading of JS/CSS/fonts from third-party CDNs. Bundle locally.

**Reviewer quote:**
> *Offloading images, js, css, and other scripts to your servers or any remote service (like Google, MaxCDN, jQuery.com etc) is disallowed.*
> *`includes/Admin/Assets.php:31 wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', ...);`*

**Fix:** Download the asset into `assets/vendor/` and enqueue from the local path. Permitted exceptions: documented API calls (Issue 2), oEmbed providers, Akismet-style services.

---

## Issue 10 — Calling Core Loading Files Directly

**Rule:** Plugins must not include `wp-config.php`, `wp-load.php`, or `wp-blog-header.php` directly. Must not require `wp-admin/includes/` files outside of an admin-side context.

**Reviewer quotes:**
> *`includes/utils/class-filesystem.php:89 require_once ABSPATH . 'wp-admin/includes/file.php';`*
> *`class-warranty-cart.php:159 include_once ABSPATH . 'wp-admin/includes/plugin.php';`*

**Fix:** Move file-system / plugin-admin logic into a function hooked to `admin_init` or `admin_menu` — the file is already loaded by then. External-facing endpoints: use REST API or admin AJAX, never a standalone PHP file that bootstraps WordPress.

---

## Issue 11 — Missing Nonce + Capability Checks

**Rule:** Every form submission, AJAX handler, REST endpoint, and admin-side `$_GET`-driven action must verify a nonce **and** check `current_user_can()`.

**Reviewer quotes:**
> *Please add a nonce check to your input calls (`$_POST`, `$_GET`, `$_REQUEST`) to prevent unauthorized access. If you use `wp_ajax_` to trigger submission checks, remember they also need a nonce check.*
> *A nonce check alone is not bulletproof security. Do not rely on nonces for authorization purposes.*
> *`templates/admin/menu-manager.php:1 No nonce check found validating input origin on lines 1-36.`*

**Fix:**

```php
// Form processor
if ( ! isset( $_POST['my_plugin_nonce'] )
    || ! wp_verify_nonce(
        sanitize_text_field( wp_unslash( $_POST['my_plugin_nonce'] ) ),
        'my_plugin_save_settings'
    )
) {
    wp_die( esc_html__( 'Security check failed.', 'my-plugin' ), 403 );
}
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Forbidden.', 'my-plugin' ), 403 );
}

// AJAX handler
function my_plugin_ajax_handler() {
    check_ajax_referer( 'my_plugin_action', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( __( 'Forbidden.', 'my-plugin' ), 403 );
    }
}
add_action( 'wp_ajax_my_plugin_action', 'my_plugin_ajax_handler' );
```

Never run nonce/`$_POST` checks at the top level of a file — it executes on every page load. Always wrap in a function bound to an action.

---

## Issue 12 — Late Escaping Not Applied

**Rule:** Every variable must be escaped at the point of output using the context-appropriate function.

**Reviewer quotes:**
> *All variables that are echoed need to be escaped when they're echoed. You should not be escaping when you build a variable, but when you output it at the end. We call this 'escaping late.'*
> *`includes/Frontend/CheckoutFields.php:107 echo $section_html;`*
> *A common mistake is to use `esc_html` to escape HTML. This function strips HTML tags — use `wp_kses_post()` or `wp_kses()` for HTML output.*

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
| Translated strings | `esc_html__()`, `esc_attr__()`, `esc_html_e()` |

---

## Issue 13 — Sanitize / Validate / Escape on All Input

**Rule:** Every `$_POST`, `$_GET`, `$_REQUEST`, `$_FILES`, `$_COOKIE` access must be sanitized before any processing.

**Reviewer quote:**
> *When you include `POST/GET/REQUEST/FILE` calls in your plugin, it's important to sanitize, validate, and escape them.*
> *`includes/Admin/Product/Import.php:45 $file = $_FILES['csv_file'];`*

**Mantra:** Sanitize early → validate always → escape late.

`wp_unslash()` must happen **before** `sanitize_*()` (WordPress magic-quotes superglobals):

```php
$val = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
```

---

## Issue 14 — Generic / Mixed Prefixes

**Rule:** Every globally-scoped identifier must use a **single** project-specific prefix of **4+ characters** (not `wp_`, `_`, `__`). Mixing multiple prefixes in one plugin is also flagged.

**Reviewer quote:**
> *This plugin is using the prefix "swmd" for 12 element(s). This plugin is using the prefix "swift_menu_duplicator" for 10 element(s). This plugin is using the prefix "classic_menu_duplicator" for 17 element(s).*

**Fix:** Choose one prefix and apply it consistently to: functions, classes (or use a namespace), `define()` constants, option keys, transient keys, hook names the plugin defines, `wp_ajax_*` handles, `wp_localize_script` JS globals.

After a slug rename, every identifier from the old name must also be renamed — leftover identifiers from the previous slug are flagged.

---

## Issue 15 — Text Domain Does Not Match Plugin Slug

**Rule:** The text domain in every i18n call must match the plugin slug exactly.

**Reviewer quote:**
> *This `text domain` must be the same as your plugin slug so that the plugin can be translated by the community. From your plugin, you have set your text domain as follows: This plugin is using the domain `swift-menu-duplicator` for 119 element(s). However, the current plugin slug is this: `classic-menu-duplicator`.*

**Fix:** After any slug rename, search-replace the text domain across all PHP files and update `phpcs.xml` `text_domain` property.

---

## Issue 16 — Missing or Incorrect REST API `permission_callback`

**Rule:** Every `register_rest_route()` must include an explicit `permission_callback`. Omitting it is treated as a security defect.

**Reviewer quote:**
> *When using `register_rest_route()` to define custom REST API endpoints, it is crucial to include a proper `permission_callback`.*
> *`includes/webhooks/class-webhook-router.php:144 register_rest_route(…, 'permission_callback' => '__return_true')`*

**Fix:**

```php
// Restricted endpoint
register_rest_route( 'my-plugin/v1', '/settings', array(
    'methods'             => 'POST',
    'callback'            => 'my_plugin_rest_save',
    'permission_callback' => static function () {
        return current_user_can( 'manage_options' );
    },
) );

// Intentionally public — add comment explaining why
register_rest_route( 'my-plugin/v1', '/webhook', array(
    'methods'             => 'POST',
    'callback'            => array( $this, 'handle_webhook' ),
    // Public: authenticated via HMAC signature in payload, not WP user capability.
    'permission_callback' => '__return_true',
) );
```

Never omit `permission_callback` — the fallback has changed across WordPress versions.

---

## Issue 17 — Admin Dashboard Hijacking (Guideline 11)

**Rule:** Upgrade notices, review prompts, and persistent alerts must be contextual, dismissible, and confined to the plugin's own screens.

**Reviewer quote:**
> *Plugins should not hijack the admin dashboard. Upgrade prompts, notices, alerts, and the like must be limited in scope and used with moderation. (Guideline 11)*

**Patterns that trigger this:**
- Admin notices displayed on every admin screen (not scoped to plugin pages)
- Non-dismissible banners on unrelated pages
- Full-page upsell screens in the admin flow
- Persistent nags that reappear after dismissal
- Automatic redirects to welcome/upgrade page on every activation

**Fix:**

```php
add_action( 'admin_notices', static function () {
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'my-plugin' ) === false ) {
        return; // Only show on the plugin's own screens.
    }
    // Render notice.
} );
```

Dismissals stored in user meta via AJAX; activation redirects fire once (transient guard).

---

## Issue occurrence matrix

| Issue | Squad Modules | Author Profiles | OpenCode Zen | MiniMax | Swift Menu | Warranty Cart | EasyCommerce | GiveWP Multi |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| 1 Invalid URLs | | | ✓ | ✓ | ✓ | | ✓ | |
| 2 Undisclosed service | | | | ✓ | | | | |
| 3 Name / trademark | | | ✓ | | ✓ | | | ✓ |
| 4 Main file name | | | ✓ | | | | | |
| 5 Unneeded folders / WP.org assets in zip | | | ✓ | | | | ✓ | |
| 6 No source for compiled output | | | | | | ✓ | ✓ | |
| 7 Out-of-date libraries | | | | | | ✓ | | |
| 8 Improper enqueueing | | | | | ✓ | ✓ | | ✓ |
| 9 Remote CDN calls | | | | | | ✓ | | |
| 10 Core loading files | | | | | ✓ | ✓ | | |
| 11 Missing nonce / capability | | | | | ✓ | | | ✓ |
| 12 Late escaping | | | | | ✓ | ✓ | | |
| 13 Sanitize / validate input | | | | | | ✓ | | |
| 14 Generic / mixed prefix | | | | | ✓ | ✓ | ✓ | |
| 15 Text domain ≠ slug | | | | | ✓ | | | |
| 16 REST `permission_callback` | | | | | | | | ✓ |
| 17 Admin hijacking (Guideline 11) | | | | | | | | ✓ |

---

## Email types from the review team

| Template | Subject prefix | Review ID | Meaning |
|---|---|---|---|
| Submission confirmation | `Successful Plugin Submission` | — | Automated, no action needed |
| AI-assisted pre-review | `Review in Progress` | `AUTOPREREVIEW` | Automated, superficial checks; human review follows |
| Human volunteer review | `Review in Progress` | `R*` / `F1*` | Substantive feedback; fix all items and reply with updated zip |
| Approval | `Review in Progress` | — | *"Your review has been successfully completed."* SVN within 24 h |

## Reply etiquette

1. **Be brief** — 2–4 sentences. Reviewers process ~1,500 reviews/week. Reviewers flag verbose AI replies in their template: *"please, avoid copy-pasting bloated AI responses, our AI is quite brief."*
2. **No change lists** — reviewers re-review the entire plugin; itemized changelogs waste their time.
3. **Reply in the same thread** — do not re-submit via the form.
4. **Slug change = explicit text** — *"Please change the slug to `new-slug`."*
5. **Three-month timeout** — unresolved = rejected slug (burned permanently; new submission needs a new slug).
