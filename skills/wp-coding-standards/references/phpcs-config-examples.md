# PHPCS Configuration Examples

## Minimal phpcs.xml.dist (WP.org submission ready)

```xml
<?xml version="1.0"?>
<ruleset name="Plugin Name">
    <file>.</file>
    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>
    <exclude-pattern>build/*</exclude-pattern>
    <exclude-pattern>*.min.js</exclude-pattern>
    <exclude-pattern>*.min.css</exclude-pattern>

    <config name="testVersion" value="7.4-"/>

    <rule ref="WordPress-Extra"/>
    <rule ref="WordPress-Docs"/>

    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array" value="my-plugin"/>
        </properties>
    </rule>

    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array" value="my_plugin,MyPlugin,MY_PLUGIN"/>
        </properties>
    </rule>

    <arg value="ps"/>
    <arg name="extensions" value="php"/>
    <arg name="colors"/>
    <arg name="parallel" value="8"/>
</ruleset>
```

## With PHPCompatibilityWP

```bash
composer require --dev phpcompatibility/phpcompatibility-wp
```

```xml
<rule ref="PHPCompatibilityWP"/>
<config name="testVersion" value="7.4-8.3"/>
```

## WooCommerce extension additions

```bash
composer require --dev woocommerce/woocommerce-sniffs
```

```xml
<rule ref="WooCommerce-Core"/>
```

## Exclusion patterns for common false positives

```xml
<!-- Allow short ternary -->
<rule ref="WordPress.PHP.DisallowShortTernary">
    <exclude name="WordPress.PHP.DisallowShortTernary"/>
</rule>

<!-- Allow short array syntax (WP 5.5+ codebase uses it) -->
<rule ref="Generic.Arrays.DisallowShortArraySyntax">
    <exclude name="Generic.Arrays.DisallowShortArraySyntax"/>
</rule>

<!-- Exclude specific file from a rule -->
<rule ref="WordPress.DB.DirectDatabaseQuery">
    <exclude-pattern>includes/class-migration.php</exclude-pattern>
</rule>
```

## Inline suppressions cheat sheet

```php
// Single line
$val = $unsafe_val; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

// Block
// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( ... );
$wpdb->get_results( ... );
// phpcs:enable WordPress.DB.DirectDatabaseQuery

// Disable for whole file (rare — prefer targeted)
// phpcs:disable WordPress.Security.NonceVerification
```

## Common sniff codes

| Code | Meaning |
|---|---|
| `WordPress.Security.EscapeOutput.OutputNotEscaped` | Echo/print without escape function |
| `WordPress.Security.NonceVerification.Missing` | Missing nonce check on `$_POST`/`$_GET` |
| `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` | Unsanitized user input |
| `WordPress.Security.ValidatedSanitizedInput.MissingUnslash` | Missing `wp_unslash()` before sanitize |
| `WordPress.DB.DirectDatabaseQuery.DirectQuery` | Direct `$wpdb` query without caching |
| `WordPress.DB.DirectDatabaseQuery.NoCaching` | Direct query result not cached |
| `WordPress.DB.SlowDBQuery.slow_db_query_meta_query` | Slow `meta_query` in WP_Query |
| `WordPress.WP.I18n.MissingTranslatorsComment` | Missing translator comment before `sprintf` |
| `WordPress.WP.I18n.NonSingularStringLiteralText` | Variable passed as translatable string |
| `WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound` | Function missing plugin prefix |
| `WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound` | Hook missing plugin prefix |
| `WordPress.WP.DeprecatedFunctions.wp_make_content_images_responsiveFound` | Using deprecated WP function |
| `WordPress.PHP.YodaConditions.NotYoda` | Non-Yoda condition |

## Composer scripts

```json
{
    "scripts": {
        "lint": "phpcs",
        "lint:fix": "phpcbf",
        "lint:report": "phpcs --report=summary",
        "lint:diff": "phpcs --report=diff"
    }
}
```

## GitHub Actions matrix (multiple PHP versions)

```yaml
strategy:
  matrix:
    php: ['7.4', '8.0', '8.1', '8.2', '8.3']
steps:
  - uses: shivammathur/setup-php@v2
    with:
      php-version: ${{ matrix.php }}
      tools: composer
  - run: composer install --no-interaction
  - run: vendor/bin/phpcs
```
