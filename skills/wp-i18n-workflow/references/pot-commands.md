# WP-CLI i18n Command Reference

All commands require WP-CLI 2.2+ (`wp --info` to check version).

## make-pot

Generate a POT file by scanning PHP, JS, and block.json files.

```bash
# Basic
wp i18n make-pot . languages/my-plugin.pot

# With domain and exclusions
wp i18n make-pot . languages/my-plugin.pot \
  --domain=my-plugin \
  --exclude=vendor,node_modules,build,tests,*.min.js

# With custom headers
wp i18n make-pot . languages/my-plugin.pot \
  --domain=my-plugin \
  --headers='{"Project-Id-Version":"My Plugin 1.0.0","Report-Msgid-Bugs-To":"https://github.com/my-org/my-plugin/issues","Last-Translator":"FULL NAME <EMAIL>","Language-Team":"LANGUAGE <LL@li.org>"}'

# Merge with existing POT (marks removed strings as obsolete)
wp i18n make-pot . languages/my-plugin.pot \
  --domain=my-plugin \
  --merge

# Scan specific source dirs only
wp i18n make-pot . languages/my-plugin.pot \
  --domain=my-plugin \
  --include=includes,src,templates \
  --exclude=vendor
```

## make-mo

Compile `.po` files to binary `.mo` files.

```bash
# Single file
wp i18n make-mo languages/my-plugin-fr_FR.po

# All .po files in directory
wp i18n make-mo languages/

# Output to different directory
wp i18n make-mo languages/my-plugin-fr_FR.po dist/languages/
```

## make-json

Generate JSON files for JavaScript translations.

```bash
# From a single PO file (produces one or more .json files)
wp i18n make-json languages/my-plugin-fr_FR.po

# Don't delete source strings from the PO file
wp i18n make-json languages/my-plugin-fr_FR.po --no-purge

# All PO files in a directory
wp i18n make-json languages/ --no-purge

# Specify output directory
wp i18n make-json languages/ --destination=build/languages/
```

Output filename format: `{text-domain}-{locale}-{md5-of-source-js-path}.json`
Example: `my-plugin-fr_FR-a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4.json`

## make-php

Convert a PO file to PHP (legacy format, rarely needed).

```bash
wp i18n make-php languages/my-plugin-fr_FR.po
```

## update-po

Update existing PO files from a POT file.

```bash
# Update all PO files from POT
wp i18n update-po languages/my-plugin.pot languages/

# Update single PO file
wp i18n update-po languages/my-plugin.pot languages/my-plugin-fr_FR.po
```

## Full release i18n workflow

```bash
#!/bin/bash
# Run from plugin root

DOMAIN="my-plugin"
LANG_DIR="languages"

echo "Generating POT..."
wp i18n make-pot . "${LANG_DIR}/${DOMAIN}.pot" \
  --domain="${DOMAIN}" \
  --exclude=vendor,node_modules,build,tests

echo "Updating PO files..."
wp i18n update-po "${LANG_DIR}/${DOMAIN}.pot" "${LANG_DIR}/"

echo "Compiling MO files..."
wp i18n make-mo "${LANG_DIR}/"

echo "Generating JS JSON files..."
wp i18n make-json "${LANG_DIR}/" --no-purge

echo "Done. Files in ${LANG_DIR}/:"
ls -la "${LANG_DIR}/"
```

## Locale codes reference

Common locales used in WP translation:

| Locale | Language |
|---|---|
| `en_US` | English (US) — default, no file needed |
| `fr_FR` | French (France) |
| `de_DE` | German |
| `es_ES` | Spanish (Spain) |
| `pt_BR` | Portuguese (Brazil) |
| `it_IT` | Italian |
| `nl_NL` | Dutch |
| `ja` | Japanese |
| `zh_CN` | Chinese (Simplified) |
| `zh_TW` | Chinese (Traditional) |
| `ar` | Arabic (RTL) |
| `he_IL` | Hebrew (RTL) |
| `ru_RU` | Russian |
| `pl_PL` | Polish |
| `sv_SE` | Swedish |

Full list: `https://make.wordpress.org/polyglots/teams/`

## File naming conventions

```
languages/
├── my-plugin.pot                    # Source strings (commit to git)
├── my-plugin-fr_FR.po              # French translation source (commit)
├── my-plugin-fr_FR.mo              # Compiled binary (commit or generate on deploy)
├── my-plugin-fr_FR-{hash}.json    # JS translations for fr_FR (generate on deploy)
├── my-plugin-de_DE.po
├── my-plugin-de_DE.mo
└── my-plugin-de_DE-{hash}.json
```

## WP-CLI install in CI

```bash
# Download and install WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Verify
wp --info
```
