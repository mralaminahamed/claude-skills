# PHPUnit Bootstrap & Configuration

## phpunit.xml.dist

```xml
<?xml version="1.0"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.0/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    colors="true"
    beStrictAboutOutputDuringTests="true"
    beStrictAboutTestsThatDoNotTestAnything="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">tests/Integration</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">includes</directory>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>vendor</directory>
            <directory>tests</directory>
        </exclude>
    </coverage>

    <php>
        <env name="WP_TESTS_DIR" value="/tmp/wordpress-tests-lib" />
        <env name="WP_CORE_DIR" value="/tmp/wordpress" />
    </php>
</phpunit>
```

## tests/bootstrap.php

```php
<?php
/**
 * PHPUnit bootstrap file.
 */

define( 'MY_PLUGIN_TESTS_DIR', __DIR__ );
define( 'MY_PLUGIN_DIR', dirname( __DIR__ ) );

// WP tests library location (set via env or CI script)
$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: sys_get_temp_dir() . '/wordpress-tests-lib';
$wp_core_dir  = getenv( 'WP_CORE_DIR' )  ?: sys_get_temp_dir() . '/wordpress';

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
    echo "WP tests not found at: {$wp_tests_dir}\n";
    echo "Run: bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n";
    exit( 1 );
}

// Load WP test functions first (before any WP code)
require_once $wp_tests_dir . '/includes/functions.php';

// Activate plugin during tests bootstrap
tests_add_filter( 'muplugins_loaded', function() {
    require MY_PLUGIN_DIR . '/my-plugin.php';
} );

// Initialise WP
require_once $wp_tests_dir . '/includes/bootstrap.php';

// Load composer autoloader after WP bootstrap (WP may define conflicting classes)
require MY_PLUGIN_DIR . '/vendor/autoload.php';
```

## bin/install-wp-tests.sh

```bash
#!/usr/bin/env bash
# Usage: ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
# Example: ./bin/install-wp-tests.sh wordpress_test root '' localhost latest

set -e

DB_NAME="${1:-wordpress_test}"
DB_USER="${2:-root}"
DB_PASS="${3:-}"
DB_HOST="${4:-localhost}"
WP_VERSION="${5:-latest}"

WP_TESTS_DIR="${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}"
WP_CORE_DIR="${WP_CORE_DIR:-/tmp/wordpress}"

download() {
    if command -v curl &>/dev/null; then
        curl -s "$1" > "$2"
    elif command -v wget &>/dev/null; then
        wget -nv -O "$2" "$1"
    fi
}

install_wp() {
    if [ -d "$WP_CORE_DIR" ]; then return; fi

    mkdir -p "$WP_CORE_DIR"
    if [ "$WP_VERSION" == "latest" ]; then
        WP_TESTS_TAG="trunk"
        download "https://wordpress.org/latest.tar.gz" /tmp/wordpress.tar.gz
    else
        WP_TESTS_TAG="tags/$WP_VERSION"
        download "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" /tmp/wordpress.tar.gz
    fi

    tar --strip-components=1 -zxf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"
}

install_test_suite() {
    if [ -d "$WP_TESTS_DIR" ]; then return; fi

    mkdir -p "$WP_TESTS_DIR"
    svn export --quiet \
        "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" \
        "$WP_TESTS_DIR/includes"
    svn export --quiet \
        "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/" \
        "$WP_TESTS_DIR/data"

    download \
        "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" \
        "$WP_TESTS_DIR/wp-tests-config.php"

    sed -i "s:youremptytestdbnamehere:$DB_NAME:" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s:yourusernamehere:$DB_USER:" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s:yourpasswordhere:$DB_PASS:" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|localhost|$DB_HOST|" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s:dirname( __FILE__ ) . '/src/':\"$WP_CORE_DIR/\":" "$WP_TESTS_DIR/wp-tests-config.php"
}

create_db() {
    mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" 2>/dev/null || true
}

install_wp
install_test_suite
create_db
```

## composer.json test setup

```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "brain/monkey": "^2.6",
        "mockery/mockery": "^1.6",
        "yoast/phpunit-polyfills": "^2.0"
    },
    "scripts": {
        "test":          "phpunit",
        "test:unit":     "phpunit --testsuite Unit",
        "test:int":      "phpunit --testsuite Integration",
        "test:coverage": "phpunit --coverage-html coverage"
    },
    "autoload-dev": {
        "psr-4": {
            "My_Plugin\\Tests\\": "tests/"
        }
    }
}
```

## GitHub Actions CI

```yaml
# .github/workflows/phpunit.yml
name: PHPUnit

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
        wp: ['latest', '6.4']

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mysqli, mbstring
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist

      - name: Install WP test suite
        run: |
          bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1:3306 ${{ matrix.wp }}

      - name: Run tests
        run: composer test
```
