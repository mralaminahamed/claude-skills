# PHPCS in CI (GitHub Actions)

## Minimal workflow

```yaml
# .github/workflows/phpcs.yml
name: PHPCS

on: [push, pull_request]

jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer, cs2pr

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHPCS
        run: vendor/bin/phpcs --report=checkstyle | cs2pr
```

`cs2pr` converts checkstyle XML output to GitHub PR annotations (inline code comments).

## Matrix PHP version check

```yaml
jobs:
  phpcs:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          tools: composer
      - run: composer install --prefer-dist
      - run: vendor/bin/phpcs
```

## With PHPStan in same workflow

```yaml
jobs:
  code-quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer

      - run: composer install --prefer-dist

      - name: PHPCS
        run: vendor/bin/phpcs --report=checkstyle | cs2pr

      - name: PHPStan
        run: vendor/bin/phpstan analyse --no-progress --error-format=github
```

`--error-format=github` outputs PHPStan errors as GitHub Actions annotations.

## composer.json scripts

```json
{
    "scripts": {
        "phpcs":  "phpcs",
        "phpcbf": "phpcbf",
        "lint":   ["@phpcs", "@phpstan"]
    }
}
```

Run locally: `composer phpcs` or `composer phpcbf` (auto-fix).

## phpcs.xml.dist for CI

```xml
<?xml version="1.0"?>
<ruleset name="My Plugin">
    <description>PHPCS rules for CI</description>

    <file>includes</file>
    <file>src</file>
    <file>my-plugin.php</file>

    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>
    <exclude-pattern>build/*</exclude-pattern>

    <!-- Report in checkstyle format for cs2pr -->
    <arg name="report" value="full"/>
    <arg name="colors"/>
    <arg value="sp"/>

    <rule ref="WordPress-Extra"/>
    <rule ref="WordPress-Docs"/>

    <config name="minimum_supported_wp_version" value="6.3"/>
    <config name="testVersion" value="8.1-"/>

    <!-- PHPCompatibility -->
    <rule ref="PHPCompatibilityWP"/>
</ruleset>
```

## Pre-commit hook (local enforcement)

```bash
# .git/hooks/pre-commit
#!/bin/bash
set -e

STAGED=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)
if [ -z "$STAGED" ]; then
    exit 0
fi

echo "Running PHPCS on staged files…"
echo "$STAGED" | xargs vendor/bin/phpcs
```

```bash
chmod +x .git/hooks/pre-commit
```

Or use a package like [husky](https://typicode.github.io/husky/) for cross-team enforcement.

## Cache PHPCS results (speed up CI)

```yaml
- name: Cache PHPCS results
  uses: actions/cache@v4
  with:
    path: .phpcs-cache
    key: phpcs-${{ hashFiles('phpcs.xml.dist', 'composer.lock') }}-${{ github.sha }}
    restore-keys: phpcs-${{ hashFiles('phpcs.xml.dist', 'composer.lock') }}-
```

In `phpcs.xml.dist`:
```xml
<arg name="cache" value=".phpcs-cache"/>
```

## Ignoring files/paths from command line

```bash
# Ignore specific paths at CLI level (supplements phpcs.xml.dist)
vendor/bin/phpcs --ignore=vendor,node_modules,build

# Run on specific files only (useful in pre-commit hook)
vendor/bin/phpcs includes/class-my-feature.php

# Show only errors, not warnings
vendor/bin/phpcs --warning-severity=0
```
