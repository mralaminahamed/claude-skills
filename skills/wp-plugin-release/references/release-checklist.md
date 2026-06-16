# Plugin Release Checklist

## Pre-Release

### Code
- [ ] All PRs merged to trunk
- [ ] Version bumped in plugin header: `Version: X.Y.Z`
- [ ] Version constant updated: `define( 'MY_PLUGIN_VERSION', 'X.Y.Z' )`
- [ ] `Tested up to:` in readme.txt updated to current WP stable
- [ ] `Stable tag:` in readme.txt set to new version
- [ ] `== Changelog ==` section prepended with new version entry
- [ ] No `TODO` / `FIXME` / `var_dump` / `error_log` left in release code
- [ ] All strings translatable (`__()`, `_e()`, `esc_html__()`)
- [ ] POT file regenerated: `wp i18n make-pot . languages/my-plugin.pot`

### Tests
- [ ] PHPUnit integration tests pass
- [ ] PHPCS reports zero errors (`composer phpcs`)
- [ ] PHPStan passes at project level
- [ ] Manual smoke test on clean WP install
- [ ] Test upgrade path from previous stable version
- [ ] Check PHP 8.1, 8.2, 8.3 compatibility

### Assets (WP.org)
- [ ] `assets/banner-1544x500.png` updated if needed
- [ ] `assets/icon-256x256.png` present
- [ ] Screenshots in `assets/screenshot-N.png` accurate

## Version Bump Pattern

```php
// my-plugin.php header
/**
 * Plugin Name: My Plugin
 * Version:     1.2.0
 */

// Constants
define( 'MY_PLUGIN_VERSION', '1.2.0' );
define( 'MY_PLUGIN_DB_VERSION', '1.2' ); // bump only if schema changed

// DB upgrade check (in init or admin_init)
if ( get_option( 'my_plugin_db_version' ) !== MY_PLUGIN_DB_VERSION ) {
    my_plugin_run_db_upgrades();
    update_option( 'my_plugin_db_version', MY_PLUGIN_DB_VERSION );
}
```

## CHANGELOG.md Format (Keep a Changelog)

```markdown
# Changelog

## [Unreleased]

## [1.2.0] - 2026-06-16
### Added
- New export feature with CSV and JSON formats.
- REST API endpoint `GET /my-plugin/v1/items`.

### Changed
- Settings page redesigned with tabs.
- Minimum PHP version raised to 8.1.

### Fixed
- Fixed fatal error when WooCommerce is not active (#123).
- Corrected wrong capability check on settings save.

### Removed
- Dropped legacy `my_plugin_compat` filter (deprecated since 1.0.0).

## [1.1.0] - 2026-03-01
...
```

## Git Release Flow

```bash
# 1. Final commit on trunk
git add -A
git commit -m "chore(release): bump version to 1.2.0"

# 2. Tag
git tag -a "v1.2.0" -m "Release 1.2.0"

# 3. Push
git push origin trunk --tags

# 4. Create GitHub Release (auto-generates release notes from tag)
gh release create "v1.2.0" \
  --title "1.2.0" \
  --notes-file CHANGELOG_FRAGMENT.md \
  --latest
```

## readme.txt Template

```
=== My Plugin ===
Contributors:      myusername
Donate link:       https://example.com/donate
Tags:              my-plugin, feature, wordpress
Requires at least: 6.3
Tested up to:      6.7
Stable tag:        1.2.0
Requires PHP:      8.1
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Short description (max 150 chars).

== Description ==

Full description here. Supports Markdown-ish formatting.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory.
2. Activate through 'Plugins' menu.
3. Go to Settings > My Plugin.

== Frequently Asked Questions ==

= How do I configure X? =

Go to Settings > My Plugin and enable X.

== Screenshots ==

1. Main settings page.
2. Plugin output on front end.

== Changelog ==

= 1.2.0 =
* Added export feature.
* Fixed fatal error when WooCommerce inactive.

= 1.1.0 =
* Added REST API.

== Upgrade Notice ==

= 1.2.0 =
Requires PHP 8.1+. Update PHP before upgrading.
```
