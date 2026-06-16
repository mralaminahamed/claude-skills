# Packagist API

## Package info endpoint

```bash
wget -q -O- "https://packagist.org/packages/<vendor>/<package>.json"
```

**NOT** `repo.packagist.org/p2/` — that path returns a different structure.

## JSON structure

```
."package"."versions".<version-string>.{name, version, dist, require, ...}
```

Top-level key is `"package"`, then `"versions"` — not `"packages"` (plural) like the search API.

### Get all stable version keys

```bash
PACKAGIST_JSON="$(wget -q -O- "https://packagist.org/packages/<vendor>/<package>.json")"
jq -r '."package"."versions" | keys[]' <<<"$PACKAGIST_JSON" | grep -v 'dev\|alpha\|beta\|RC' | sort -V
```

### Get latest patch for a minor version

```bash
VERSIONS=(3.4 3.5 3.6 3.7 3.8 3.9)
for V in "${VERSIONS[@]}"; do
    printf -v JQ_FILTER '."package"."versions" | keys[] | select(test("^%s\\.%s\\.[0-9]+$"))' "${V%.*}" "${V#*.}"
    LATEST="$(jq -r "$JQ_FILTER" <<<"$PACKAGIST_JSON" | sort -t "." -k 3 -g | tail -n 1)"
    if [ -z "$LATEST" ]; then continue; fi
    # use $LATEST ...
done
```

**Do NOT use `\d` in jq string literals** — invalid. Use `[0-9]` instead.

## Updating source/composer.json version

```bash
# Replace version constraint for a specific package in source/composer.json
printf -v SED_EXP 's#\("<vendor>/<package>"\): "[^"]*"#\1: "%s"#' "${LATEST}"
sed -i -e "$SED_EXP" "$ROOT_DIR/source/composer.json"
composer --working-dir="$ROOT_DIR/source" update --no-interaction
```

## source/composer.json for composer-type packages

```json
{
    "require": {
        "php": ">=5.6",
        "<vendor>/<package>": "<initial-version>"
    },
    "minimum-stability": "stable"
}
```

Also create `source/.gitignore`:
```
/vendor/
/composer.lock
```

## finder.php path for composer packages

```php
->in(array(
    'source/vendor/<vendor>/<package>',
))
```
