# GitHub Actions — WordPress Plugin CI Matrix

## Standard PHPUnit Matrix

```yaml
# .github/workflows/phpunit.yml
name: PHPUnit

on:
  push:
    branches: [trunk, develop]
  pull_request:

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['7.4', '8.2', '8.3', '8.4']   # 7.4 = WP's minimum floor; 8.3 = recommended
        wp: ['6.9', '7.0', 'latest']         # prior maintenance line + current major (WP 7.0, 2026)
        exclude:
          - php: '8.4'
            wp: '6.9'   # 6.9 predates broad PHP 8.4 testing

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v5

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mysqli, mbstring
          coverage: none   # use 'xdebug' only when generating coverage

      - name: Cache Composer
        uses: actions/cache@v5
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install WP test suite
        run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 ${{ matrix.wp }}

      - name: Run PHPUnit
        run: vendor/bin/phpunit --testsuite=unit,integration
```

---

## PHPCS Job

```yaml
name: PHPCS

on: [push, pull_request]

jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: cs2pr

      - uses: actions/cache@v5
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - run: composer install --no-interaction --prefer-dist

      - name: Run PHPCS
        run: vendor/bin/phpcs --report=checkstyle | cs2pr
        # cs2pr converts checkstyle output to GitHub PR annotations
```

---

## PHPStan Job

```yaml
name: PHPStan

on: [push, pull_request]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none
      - uses: actions/cache@v5
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/phpstan analyse --no-progress
```

---

## Caching Tips

```yaml
# Cache WP test suite download between runs
- name: Cache WP test suite
  uses: actions/cache@v5
  with:
    path: /tmp/wordpress-tests-lib
    key: wp-tests-${{ matrix.wp }}-${{ hashFiles('bin/install-wp-tests.sh') }}
```

---

## Debugging CI Failures Locally

```bash
# Reproduce the exact PHP version CI uses
docker run --rm -it shivammathur/php:8.2 bash

# Run install-wp-tests.sh against a local MySQL
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest

# Run exact same phpunit command
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit --verbose
```

---

## Common CI Failure Causes

| Error | Cause | Fix |
|-------|-------|-----|
| `Can't connect to MySQL` | Service not ready | Add `--health-cmd` options to MySQL service |
| `Class not found` in bootstrap | `composer install` not run | Ensure install step runs before test step |
| `WP_TESTS_DIR not set` | bootstrap.php uses wrong env var | Check `getenv('WP_TESTS_DIR')` in bootstrap |
| PHPUnit passes locally, fails CI | Different PHP/WP version | Add matrix for your local versions |
| `install-wp-tests.sh` fails on `latest` | SVN checkout timeout | Add retry logic or cache the suite |
| PHPCS passes locally, fails CI | Installed PHPCS version differs | Pin `squizlabs/php_codesniffer` in composer.json |
