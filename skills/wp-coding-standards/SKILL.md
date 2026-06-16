---
name: wp-coding-standards
description: Use when setting up PHPCS with WordPress Coding Standards (WPCS), configuring phpcs.xml.dist, running or fixing sniff violations, adding PHPCS to CI, or auditing a plugin's code style against WP.org requirements. Not for PHPStan type analysis — use wp-phpstan-stubs for that.
---

# WordPress Coding Standards (PHPCS + WPCS)

Configure and enforce the WordPress Coding Standards via PHP_CodeSniffer. WPCS is required for WP.org submission and is the canonical style guide for all WordPress PHP code.

## When to use

- "Set up PHPCS for my plugin", "add WordPress coding standards", "configure phpcs.xml".
- "Fix sniff violations", "run phpcbf", "auto-fix coding standards".
- "Add PHPCS to GitHub Actions / CI".
- "Why is PHPCS flagging X?", "suppress a false-positive sniff".
- Pre-submission audit: "is my code style WP.org–compliant?"

**Not for:** PHPStan static analysis or type checking — use `wp-phpstan-stubs`. Security auditing beyond style issues — use `wp-plugin-audit`.

## Method

### 1. Install

```bash
composer require --dev squizlabs/php_codesniffer wp-coding-standards/wpcs dealerdirect/phpcodesniffer-composer-installer
```

`dealerdirect/phpcodesniffer-composer-installer` auto-registers WPCS paths so no manual `--config-set` is needed. Verify:

```bash
vendor/bin/phpcs -i
# should list: WordPress, WordPress-Core, WordPress-Docs, WordPress-Extra
```

### 2. Configure `phpcs.xml.dist`

Place at project root. This is the canonical config file (`.dist` allows local `phpcs.xml` override).

```xml
<?xml version="1.0"?>
<ruleset name="My Plugin">
    <description>WordPress Coding Standards for My Plugin</description>

    <!-- What to scan -->
    <file>.</file>
    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>
    <exclude-pattern>build/*</exclude-pattern>
    <exclude-pattern>*.min.js</exclude-pattern>
    <exclude-pattern>*.min.css</exclude-pattern>
    <exclude-pattern>tests/bootstrap.php</exclude-pattern>

    <!-- PHP version target -->
    <config name="testVersion" value="7.4-"/>

    <!-- Ruleset -->
    <rule ref="WordPress-Extra">
        <!-- Suppress if you use short array syntax (WP allows it since WP 5.5) -->
        <!-- <exclude name="Generic.Arrays.DisallowShortArraySyntax"/> -->
    </rule>
    <rule ref="WordPress-Docs"/>

    <!-- Text domain for i18n sniffs -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array" value="my-plugin"/>
        </properties>
    </rule>

    <!-- Minimum WP version for deprecated functions -->
    <rule ref="WordPress.WP.DeprecatedFunctions">
        <properties>
            <property name="minimum_supported_version" value="5.9"/>
        </properties>
    </rule>

    <!-- Prefix all globals -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array" value="my_plugin,MyPlugin"/>
        </properties>
    </rule>

    <!-- Show sniff codes in output (for targeted suppression) -->
    <arg value="ps"/>
    <arg name="extensions" value="php"/>
    <arg name="colors"/>
</ruleset>
```

### 3. Run

```bash
# Check
vendor/bin/phpcs

# Auto-fix (safe mechanical fixes only — review after)
vendor/bin/phpcbf

# Single file or directory
vendor/bin/phpcs includes/class-my-class.php

# Show full sniff code for each violation (useful for writing suppressions)
vendor/bin/phpcs --report=full -s
```

### 4. Inline suppression

Suppress only when the sniff is a genuine false positive, not to hide real issues.

```php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in template
echo $pre_escaped_html;

// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}my_table WHERE id = %d", $id ) );
// phpcs:enable WordPress.DB.DirectDatabaseQuery
```

Common false positives and correct suppression codes:

| Situation | Sniff to ignore |
|---|---|
| Pre-escaped variable via custom escaper | `WordPress.Security.EscapeOutput.OutputNotEscaped` |
| Intentional direct DB query with `prepare()` | `WordPress.DB.DirectDatabaseQuery.DirectQuery` |
| Custom DB cache managed explicitly | `WordPress.DB.DirectDatabaseQuery.NoCaching` |
| `__FILE__` used in `plugin_dir_url()` | `WordPress.Security.PluginMenuSlug` (rare) |
| Slow DB query that is intentional | `WordPress.DB.SlowDBQuery.slow_db_query_meta_query` |

### 5. Common sniff violations and fixes

**Missing nonce verification:**
```php
// Bad
$value = sanitize_text_field( $_POST['field'] );

// Good
if ( ! isset( $_POST['my_plugin_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['my_plugin_nonce'] ), 'my_action' ) ) {
    wp_die( esc_html__( 'Security check failed.', 'my-plugin' ) );
}
$value = sanitize_text_field( wp_unslash( $_POST['field'] ) );
```

**Missing `wp_unslash()` before sanitize:**
```php
// Bad  — sanitize_text_field on slashed data
$val = sanitize_text_field( $_POST['field'] );

// Good
$val = sanitize_text_field( wp_unslash( $_POST['field'] ) );
```

**Unescaped output:**
```php
echo $title;                            // Bad
echo esc_html( $title );               // Good
echo wp_kses_post( $html_content );    // Good for HTML
```

**Yoda conditions:**
```php
if ( $value == true ) {}   // Bad
if ( true == $value ) {}   // Good (Yoda)
if ( $value ) {}           // Also fine
```

**Incorrect hook comment spacing:**
```php
add_action('init', 'my_fn');             // Bad — no spaces inside parens
add_action( 'init', 'my_fn' );          // Good
```

### 6. GitHub Actions CI

```yaml
# .github/workflows/phpcs.yml
name: PHPCS

on: [push, pull_request]

jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          tools: composer
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/phpcs
```

### 7. IDE integration

**PHPStorm:** Settings → PHP → Quality Tools → PHP_CodeSniffer → set path to `vendor/bin/phpcs`. Enable "Inspections → PHP → PHP Code Sniffer validation".

**VS Code:** Install `shevaua.phpcs` extension. Set `phpcs.executablePath` to `./vendor/bin/phpcs` in workspace settings.

## Notes

- `WordPress-Extra` is a superset of `WordPress-Core`; always use `WordPress-Extra` unless you have a specific reason to be less strict.
- `WordPress-Docs` is separate — it enforces PHPDoc blocks. Include it for WP.org submissions.
- WPCS sniffs for i18n (`WordPress.WP.I18n`) catch missing text domains and non-translatable strings — complement to `wp-plugin-audit` Dimension B.
- WP.org review does **not** run PHPCS automatically, but reviewers check style manually and will reject poorly formatted code. PHPCS passing is a strong signal of submission readiness.
- For WooCommerce extensions, add `WooCommerce-Core` ruleset if available (`woocommerce/woocommerce-sniffs`).
