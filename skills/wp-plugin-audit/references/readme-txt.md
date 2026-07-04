# readme.txt Format Reference

## Required Header Fields

```
=== Plugin Name ===
Contributors:      wporg-username1, wporg-username2
Tags:              tag1, tag2, tag3
Requires at least: 6.5
Tested up to:      7.0
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Short description here. Keep it under 150 characters. No markup.
```

> **Currency (July 2026):** `Tested up to` should equal the current released WordPress major — **7.0** at time of writing. Both a stale value and one *ahead* of the latest release trigger a WP.org warning. `Requires PHP` floor is **7.4** (WP 7.0 dropped 7.2/7.3; 8.3+ recommended). `Requires Plugins` (comma-separated WP.org slugs) is the supported way to declare hard plugin dependencies — verify each slug exists on WP.org.

## Required Sections

```
== Description ==
Full plugin description. Markdown supported.

== Changelog ==

= 1.0.0 =
* First release.
```

## Optional Sections (order recommended)

```
== Installation ==
== Frequently Asked Questions ==
== Screenshots ==
== Upgrade Notice ==
```

## Rules & Limits

| Field | Rule |
|---|---|
| Tags | Max **5** tags. Use existing WP.org taxonomy terms. |
| Short description | **< 150 chars**, no markup, no newlines. |
| `Stable tag` | Must match the **SVN tag** (or `trunk`). Mismatches break updates. |
| `Tested up to` | Latest WP version tested against — not necessarily latest released. |
| `Requires at least` | Must not be higher than `Tested up to`. |
| Contributors | **WP.org usernames only** (not real names or GitHub handles). |
| Changelog | Newest version first. Each `= X.Y.Z =` heading must match an SVN tag. |
| Upgrade Notice | Max **300 chars** per version. Shown in the WP admin update UI. |

## Screenshots

```
== Screenshots ==

1. Caption for screenshot-1.png (or .jpg, .gif).
2. Caption for screenshot-2.png.
```

Files placed in `/assets/screenshot-1.png` in SVN (not the plugin zip).

## Common Mistakes to Flag

- `Stable tag: trunk` — do **not** use; causes update issues on WP.org hosted plugins.
- `Stable tag` value differs from `Version:` in plugin header → update breaks.
- More than 5 tags → extras silently dropped.
- Short description > 150 chars → truncated in directory listing.
- Contributors not WP.org usernames → profile links 404.
- Changelog heading version not in SVN → update checker confusion.
- `Tested up to` > actual tested version → false advertising, flags review.

## Checklist Greps

```bash
PLUGIN_ROOT=.
grep -n "Stable tag:\|Requires at least:\|Tested up to:\|Requires PHP:\|Tags:" readme.txt
# Short description length (line after blank line after License URI)
awk '/^$/{found++} found==1 && NF{print length($0); exit}' readme.txt
# Changelog versions
grep -n "^= " readme.txt
# Cross-check Stable tag vs plugin header Version
ST=$(grep "Stable tag:" readme.txt | awk '{print $NF}')
PV=$(grep "^Version:" *.php | awk '{print $NF}')
echo "Stable tag: $ST | Plugin Version: $PV"
```
