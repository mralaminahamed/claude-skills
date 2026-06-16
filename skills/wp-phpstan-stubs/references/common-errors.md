# Common Errors & Fixes

## jq: Invalid escape `\d`

**Error:**
```
jq: error: Invalid escape at line 1, column 6 (while parsing '"\.\d"')
```

**Cause:** `\d` is not valid in jq string literals (unlike PCRE). Oniguruma supports `\d` only when the string is already inside a jq `test()` call evaluated at runtime — but the string literal parser rejects it.

**Fix:** Use `[0-9]` instead of `\d`:
```bash
# WRONG
printf -v JQ_FILTER '."versions" | keys[] | select(test("^\d+\.\d+\.\d+$"))'

# CORRECT
printf -v JQ_FILTER '."versions" | keys[] | select(test("^%s\\.%s\\.[0-9]+$"))' "${V%.*}" "${V#*.}"
```

---

## `find -exec rm -rf {} +` fails with `set -e`

**Error:** Script exits silently mid-loop.

**Cause:** `find` traverses subdirectories; after deleting a parent dir, find tries to access removed children and returns exit code 1. With `set -e`, this kills the script.

**Fix:**
```bash
# WRONG
find "$ROOT_DIR/source/" -mindepth 1 ! -name 'composer.json' ! -name '.gitignore' -exec rm -rf {} +

# CORRECT
rm -rf "$ROOT_DIR/source/<slug>" 2>/dev/null || true
rm -f "$ROOT_DIR/source/<slug>."*.zip 2>/dev/null || true
```

---

## `unzip` interactive prompt (overwrites existing files)

**Error:** `unzip` asks `replace file? [y]es/[n]o/[A]ll/[N]one` — stdin closed in CI/scripts → answers "None" → incomplete extraction.

**Fix:** Always use `-o` flag (overwrite without prompting):
```bash
unzip -q -o -d "$ROOT_DIR/source/" "$ROOT_DIR/source/<slug>.${VERSION}.zip"
```

And clean up the previous extraction before downloading:
```bash
rm -rf "$ROOT_DIR/source/<slug>" 2>/dev/null || true
```

---

## Fix reverted by `git reset --hard origin/main`

**Cause:** Release scripts start with `git reset --hard origin/main` to ensure clean state. Any local edits not yet pushed get wiped.

**Rule:** Always commit and push fixes to remote BEFORE re-running the release script.

```bash
git add bin/release-latest-versions.sh
git commit -m "fix: <description>"
git push origin main
# NOW safe to re-run
bash bin/release-latest-versions.sh
```

---

## `gh repo create` uses `trunk` instead of `main`

**Cause:** `gh repo create` pushes the current local branch, which defaults to `trunk` on some git configs.

**Fix:**
```bash
git branch -m trunk main
git push origin main
gh repo edit my-org/phpstan-<slug>-stubs --default-branch main
# Delete trunk branch via API if it was pushed
gh api repos/my-org/phpstan-<slug>-stubs/git/refs/heads/trunk -X DELETE
```

---

## Missing `source/composer.json` — post-install-cmd fails

**Error:** `composer install` exits with code 1.

**Cause:** Root `composer.json` has `post-install-cmd: @composer --working-dir=source/ update --no-interaction` but `source/composer.json` doesn't exist.

**Fix:** Always create `source/composer.json` for wp-plugin and paid packages:
```json
{"minimum-stability": "stable"}
```
And `source/.gitignore`:
```
/vendor/
/composer.lock
```

---

## Wrong Packagist API URL

**Error:** jq parse error or empty results.

| Wrong | Correct |
|-------|---------|
| `https://repo.packagist.org/p2/<vendor>/<package>.json` | `https://packagist.org/packages/<vendor>/<package>.json` |
| `.packages."<vendor>/<package>"[]` | `."package"."versions"` |

---

## Dangling local tag not on remote

**Symptom:** Local `git tag` shows `v1.54.0` but remote doesn't have it.

**Fix:**
```bash
git tag -d v1.54.0          # delete local dangling tag
# fix and push any pending commits first
bash bin/release-latest-versions.sh   # re-generates + re-tags
git push origin main --follow-tags
```

---

## Stub output file is empty

**Cause:** `finder.php` path doesn't match where source actually extracted.

**Debug:**
```bash
# Check what's actually in source/
ls source/
# Verify finder path matches
grep -n "->in(" configs/finder.php
```

Common issue: plugin extracts to `source/<slug>/` but finder points to `source/<slug>/src/` or similar.
