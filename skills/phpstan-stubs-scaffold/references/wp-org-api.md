# WordPress.org Plugin API

## Plugin info endpoint

```bash
wget -q -O- "https://api.wordpress.org/plugins/info/1.0/<slug>.json"
```

Returns JSON with `versions` object (keys = version strings, values = download URLs).

### Get all versions (sorted)

```bash
WP_JSON="$(wget -q -O- "https://api.wordpress.org/plugins/info/1.0/<slug>.json")"
jq -r '."versions" | keys[]' <<<"$WP_JSON" | grep -v "trunk" | sort -V
```

### Get latest patch for a minor version

```bash
# For minor version V (e.g. "2.3"):
printf -v JQ_FILTER '."versions" | keys[] | select(test("^%s\\.%s\\.[0-9]+$"))' "${V%.*}" "${V#*.}"
LATEST="$(jq -r "$JQ_FILTER" <<<"$WP_JSON" | sort -t "." -k 3 -g | tail -n 1)"
```

**Do NOT use `\d` in jq string literals** — invalid. Use `[0-9]` instead.

### Download URL pattern

```
https://downloads.wordpress.org/plugin/<slug>.<version>.zip
```

```bash
wget -q -P "$ROOT_DIR/source/" "https://downloads.wordpress.org/plugin/<slug>.${VERSION}.zip"
unzip -q -o -d "$ROOT_DIR/source/" "$ROOT_DIR/source/<slug>.${VERSION}.zip"
rm -f "$ROOT_DIR/source/<slug>.${VERSION}.zip"
```

Always use `-o` with unzip (overwrite without prompt). Always `rm` the zip after extract.

### Cleanup before download

```bash
rm -rf "$ROOT_DIR/source/<slug>" 2>/dev/null || true
rm -f "$ROOT_DIR/source/<slug>."*.zip 2>/dev/null || true
```

Never use `find -exec rm -rf {} +` with `set -e` — find returns non-zero when subdirs are gone.

### Check if plugin slug exists

```bash
STATUS=$(wget -q -O- "https://api.wordpress.org/plugins/info/1.0/<slug>.json" | jq -r '.error // "ok"')
# returns "Plugin not found." if invalid slug
```

## Trunk download

```
https://downloads.wordpress.org/plugin/<slug>.zip
```

(No version suffix = latest/trunk)
