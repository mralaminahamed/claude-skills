#!/usr/bin/env bash
#
# Deploy a built WordPress plugin to its WordPress.org SVN repo.
#
# Usage: svn-deploy.sh <slug> <build-dir> <version> [svn-username]
#
#   <slug>         WP.org plugin slug (e.g. my-plugin)
#   <build-dir>    directory of production files (NO dev artifacts, .git, node_modules)
#   <version>      release version, must match Stable tag in build-dir/readme.txt
#   [svn-username] optional; defaults to $WPORG_SVN_USER or an interactive prompt
#
# Stages trunk + tag in a working copy and PRINTS the commit command.
# It does NOT commit — review `svn st` first, then run the printed `svn ci`.
set -euo pipefail

SLUG="${1:?slug required}"
BUILD="${2:?build dir required}"
VER="${3:?version required}"
SVN_USER="${4:-${WPORG_SVN_USER:-}}"

[ -d "$BUILD" ] || { echo "build dir not found: $BUILD" >&2; exit 1; }
[ -f "$BUILD/readme.txt" ] || { echo "no readme.txt in build dir" >&2; exit 1; }

# Stable tag must match the version being deployed.
stable=$(grep -i '^Stable tag:' "$BUILD/readme.txt" | head -1 | sed 's/.*:[[:space:]]*//' | tr -d '\r')
if [ "$stable" != "$VER" ]; then
  echo "Stable tag ($stable) != version ($VER) — fix readme.txt before deploying." >&2
  exit 1
fi

SVN_URL="https://plugins.svn.wordpress.org/$SLUG"
WC="svn-$SLUG"

if [ -d "$WC/.svn" ]; then
  svn up "$WC"
else
  svn co "$SVN_URL" "$WC"
fi

if [ -d "$WC/tags/$VER" ]; then
  echo "tags/$VER already exists in SVN — bump the version (tags are immutable)." >&2
  exit 1
fi

# 1. mirror build into trunk (deletes removed files)
rsync -a --delete --exclude='.svn/' "$BUILD"/ "$WC/trunk/"

# 2. stage adds + deletes
( cd "$WC"
  svn add --force trunk >/dev/null
  svn st | awk '/^!/ {print $2}' | xargs -r svn rm
  # 3. immutable tag from trunk
  svn cp "trunk" "tags/$VER"
)

echo
echo "Staged trunk + tags/$VER. Review:"
echo "  ( cd $WC && svn st )"
echo
echo "Then commit:"
if [ -n "$SVN_USER" ]; then
  echo "  ( cd $WC && svn ci -m \"Release $VER\" --username $SVN_USER )"
else
  echo "  ( cd $WC && svn ci -m \"Release $VER\" )"
fi
echo
echo "Assets (banner/icon/screenshots) deploy separately into $WC/assets/."
