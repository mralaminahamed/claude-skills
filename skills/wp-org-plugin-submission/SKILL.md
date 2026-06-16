---
name: wp-org-plugin-submission
description: Use when submitting a plugin to the WordPress.org plugin directory for the first time, or deploying a new version to an already-approved plugin via SVN (trunk/tags/assets, screenshots, banners, icons). Covers the pre-submission review checklist, readme.txt requirements, the git→SVN deploy flow, and how the Stable tag controls what users receive.
---

# WordPress.org Plugin Submission & SVN Deploy

Get a plugin into the WP.org directory and keep releasing to it. Two distinct phases — know which one applies:

- **Phase 1 — Initial submission.** Plugin not yet in the directory. One-time human review, then SVN access is granted.
- **Phase 2 — SVN deploy.** Plugin already approved. Ship a new version into the existing SVN repo.

`wp-org-plugin-submission` is about the *directory/SVN side*. Sync the version sources first with [[wp-plugin-release]] — this skill assumes the codebase already carries the target version.

## When to use

- "Submit this plugin to WordPress.org", "publish to the .org directory", "add my plugin to wp.org".
- "Deploy the new version to SVN", "push the release to wp.org", "tag a release on plugins.svn".
- "Set up screenshots / banner / icon", "why aren't my assets showing".

## Phase 1 — Initial submission

The review is done by humans and can take days to weeks. Submitting a clean plugin avoids round-trips.

1. **Slug availability** — the directory slug is derived from the plugin name in the main file header. Pick a name not already taken at `https://wordpress.org/plugins/<slug>/` (404 = free). Slug is permanent.
2. **readme.txt valid** — must parse in the official validator: `https://wordpress.org/plugins/developers/readme-validator/`. Required header fields, valid `Stable tag`, GPL-compatible `License`. See `references/submission-checklist.md`.
3. **Guidelines compliance** — sanitize input, escape output, nonce-protect actions, prefix all globals, no obfuscation/minified-only code, no external loading of scripts, no tracking or calling home without explicit opt-in consent, GPL-compatible code + assets only. Full list in `references/submission-checklist.md`.
4. **Build a clean zip** — exclude dev files (tests, `.git`, `node_modules`, `composer.json`, build configs) via `.distignore` or an export. The submitted zip should be only what runs in production. Keep it lean.
5. **Submit** at `https://wordpress.org/plugins/developers/add/`. Watch the email tied to the WP.org account — the reviewer replies there. Fix what they flag, reply with the updated zip. On approval, SVN access is granted at `https://plugins.svn.wordpress.org/<slug>/`.

## Phase 2 — SVN deploy

WP.org distributes via **Subversion**, not git. The SVN repo has three top-level dirs:

```
<slug>/
├── trunk/        # current development copy of the plugin
├── tags/         # one immutable dir per released version (tags/1.2.0/)
└── assets/       # directory listing images — NOT shipped in the plugin zip
```

**The `Stable tag` in `trunk/readme.txt` decides what users download** — it must name a directory under `tags/`. Set `Stable tag: 1.2.0` and ensure `tags/1.2.0/` exists. (Pointing Stable tag at `trunk` is legal but discouraged — always release from a tag.)

Deploy = copy the production build into `trunk/`, then `svn cp trunk tags/<version>`, then commit. Use the helper:

```bash
scripts/svn-deploy.sh <slug> <path-to-built-plugin-dir> <version>
```

It checks out SVN, syncs `trunk/` to the build (adding/removing files), copies `trunk` → `tags/<version>`, and prints the `svn commit` to run after review. Full manual walkthrough and the add/delete handling in `references/svn-deploy.md`.

**Assets** (banner, icon, screenshots) live only in `assets/`, never in the zip. Exact filenames and dimensions are mandatory — `banner-772x250.png`, `banner-1544x500.png` (retina), `icon-128x128.png`, `icon-256x256.png`, `icon.svg`, `screenshot-1.png` (matched to the `1.` line under `== Screenshots ==` in readme.txt). See `references/svn-deploy.md`.

## Common rejections / pitfalls

- Generic or trademarked slug; "WordPress"/"Woo" in the name.
- Unsanitized `$_GET`/`$_POST`, unescaped output, missing nonces.
- Loading JS/CSS from a CDN instead of bundling; calling an external API without disclosure + opt-in.
- Stable tag names a tag that doesn't exist under `tags/` → users get nothing or the wrong build.
- Assets committed into `trunk/` instead of `assets/` → they don't appear on the listing and bloat the download.

## References

- `references/submission-checklist.md` — full pre-submission guideline + readme.txt field checklist and review-process notes.
- `references/svn-deploy.md` — complete SVN workflow, asset spec, Stable-tag mechanics, hotfix flow.
- `scripts/svn-deploy.sh` — git/build → SVN trunk+tag deploy helper.
