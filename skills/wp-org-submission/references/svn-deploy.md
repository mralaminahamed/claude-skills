# SVN deploy to WordPress.org

After approval the plugin lives in Subversion at `https://plugins.svn.wordpress.org/<slug>/`. Git is the source of truth; SVN is just the distribution channel.

## Repository layout

```
<slug>/
├── trunk/        # latest development copy
├── tags/         # one immutable dir per release — tags/1.2.0/
└── assets/       # listing images only; never shipped in the download
```

## How users get a version

`trunk/readme.txt` → `Stable tag: X.Y.Z` is authoritative. WP.org serves whatever `tags/X.Y.Z/` contains. Rules:

- `tags/X.Y.Z/` must exist and contain that release's full code.
- Bumping `Stable tag` in trunk is what actually publishes the release.
- Stable tag pointing at `trunk` is allowed but discouraged — release from tags so the live version is immutable and reproducible.

## First-time setup

```bash
svn co https://plugins.svn.wordpress.org/<slug> <slug>-svn
cd <slug>-svn
# copy production build into trunk/ (see build step in submission-checklist.md)
```

## Deploy a release (manual)

```bash
SLUG=<slug>; VER=1.2.0; BUILD=/path/to/built-plugin    # built dir = production files only

svn co https://plugins.svn.wordpress.org/$SLUG svn-$SLUG
cd svn-$SLUG

# 1. sync trunk to the build (rsync mirrors deletions too)
rsync -a --delete --exclude='.svn/' "$BUILD"/ trunk/

# 2. stage adds and deletes for SVN
svn add --force trunk > /dev/null
svn st | awk '/^!/ {print $2}' | xargs -r svn rm        # remove files gone from build

# 3. ensure Stable tag in trunk readme.txt names this version
grep -n 'Stable tag:' trunk/readme.txt                  # must read: Stable tag: 1.2.0

# 4. create the immutable tag from trunk
svn cp trunk tags/$VER

# 5. review, then commit (single commit for trunk + tag)
svn st
svn ci -m "Release $VER"
```

SVN credentials = WP.org username + an **application password** generated in the WP.org profile (not the account password). First commit prompts and caches it.

## Assets (banner / icon / screenshots)

Live only under `assets/`. Never in trunk/tags (they'd bloat the user download and won't render on the listing).

| File | Purpose | Size |
|------|---------|------|
| `banner-772x250.png` | listing banner | 772×250 |
| `banner-1544x500.png` | retina banner | 1544×500 |
| `icon-128x128.png` | icon | 128×128 |
| `icon-256x256.png` | retina icon | 256×256 |
| `icon.svg` | vector icon (preferred) | vector |
| `screenshot-1.png`, `screenshot-2.png`, … | screenshots | any |

```bash
cp banner-772x250.png icon-256x256.png screenshot-1.png assets/
svn add --force assets
svn ci -m "Update assets"
```

`screenshot-N.png` maps to the Nth line under `== Screenshots ==` in `trunk/readme.txt`.

## Hotfix flow

1. Fix in git, bump patch version, sync all version sources ([[wp-plugin-release]]).
2. Re-run the deploy block with the new `VER` and updated `Stable tag`.
3. Never edit an existing `tags/X.Y.Z/` — cut a new tag instead.

## Verify after deploy

- Listing shows the new version within a few minutes: `https://wordpress.org/plugins/<slug>/`.
- `https://plugins.trac.wordpress.org/browser/<slug>/tags/` lists the new tag.
- Download zip and confirm it matches the tag, with no dev files.
