=== Plugin Name ===
Contributors: wporg_username
Tags: tag1, tag2, tag3
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: X.Y.Z
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Short description, max 150 chars, one line, no markup.

== Description ==

What the plugin does.

**Features**

* Feature one.
* Feature two.

Dependencies / requirements callout.

== Installation ==

1. Upload to `/wp-content/plugins/` or install the zip via Plugins → Add New.
2. Activate.
3. Configure under <Settings location>.

== Frequently Asked Questions ==

= A question? =

An answer.

== Changelog ==

= X.Y.Z =
* Security: ...
* New: ...
* Fix: ...
* Change: ...

= <previous> =
* ...

== Upgrade Notice ==

= X.Y.Z =
One-line reason to upgrade (shown in the WP updates UI; keep it short).

= <previous> =
...


# ---------------------------------------------------------------------------
# Release sync — bump ALL of these to X.Y.Z together:
#   1. Plugin header   * Version: X.Y.Z        (main plugin file)
#   2. Version constant define('PLUGIN_VERSION','X.Y.Z')
#   3. readme.txt      Stable tag: X.Y.Z       (above)
#   4. readme.txt      == Changelog == entry
#   5. readme.txt      == Upgrade Notice == entry
#   6. .pot            regenerate (composer makepot) -> Project-Id-Version
# Do NOT bump: schema/DB $db_version unless the table actually changed.
# ---------------------------------------------------------------------------
