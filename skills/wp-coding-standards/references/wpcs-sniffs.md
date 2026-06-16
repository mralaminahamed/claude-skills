# WordPress Coding Standards Sniff Reference

## Ruleset Hierarchy

```
WordPress-Extra (superset — use this)
├── WordPress-Core      (spacing, naming, syntax)
├── WordPress-Docs      (PHPDoc requirements)
└── PHPCompatibility    (PHP version compat — install separately)
```

## WordPress-Core Sniffs (key rules)

### Spacing
- Spaces inside parentheses: `fn( $a, $b )` not `fn($a, $b)`
- Space before colon in ternary: `$a ? $b : $c`
- Space around concatenation: `$a . $b`
- Space after cast: `(int) $val` not `(int)$val`
- No space between function name and paren: `foo()` not `foo ()`

### Yoda Conditions
```php
// Correct (Yoda)
if ( true === $condition ) {}
if ( null === $value ) {}
if ( 'string' === $var ) {}

// Also OK (variables only, no literal)
if ( $a === $b ) {}   // fine — no literal on right
```

### Array Syntax
```php
// Correct (short array syntax allowed since WP 5.5 codebase)
$arr = [ 'key' => 'value' ];

// Long form also correct
$arr = array( 'key' => 'value' );
```

### Naming Conventions
```php
// Functions, methods, variables: snake_case
function my_plugin_do_thing() {}
$my_variable = '';

// Classes: PascalCase (CamelCase)
class My_Plugin_Manager {}   // WP style (underscores allowed)
class MyPluginManager {}     // PSR-2 style (also accepted)

// Constants: UPPER_CASE
define( 'MY_PLUGIN_VERSION', '1.0.0' );
const MY_PLUGIN_MAX = 100;

// Hook names: lowercase with hyphens for public, underscores for internal
do_action( 'my-plugin/item-saved', $item );    // public API style
do_action( 'my_plugin_item_saved', $item );    // traditional style
```

## WordPress-Extra Sniffs (security focus)

### Input Handling
```php
// Required: wp_unslash + sanitize before using superglobals
$val = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
$url = esc_url_raw( wp_unslash( $_POST['redirect_url'] ?? '' ) );

// Required: nonce check before processing form/AJAX
if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'my_action' ) ) {
    wp_die( esc_html__( 'Nonce check failed.', 'my-plugin' ) );
}
```

### Output Escaping (late escape rule)
```php
echo esc_html( $title );
echo esc_attr( $class );
echo esc_url( $link );
echo wp_kses_post( $html );
echo absint( $count );

// Wrong — escape at origin, not at output
$safe_title = esc_html( $title ); // early escape
echo $safe_title;                  // sniff still flags this
```

### i18n
```php
// Text domain must be a string literal
__( 'String', 'my-plugin' )   // correct
__( 'String', $domain )       // wrong — variable domain

// No dynamic strings
__( $string, 'my-plugin' )    // wrong — variable string

// Translator comment required for placeholders
/* translators: %s: item name */
sprintf( __( 'Saved %s', 'my-plugin' ), $name )
```

### Prefix All Globals
```php
// All functions, classes, constants, and hooks at global scope must use plugin prefix
function my_plugin_init() {}     // correct
function init() {}               // wrong — no prefix

class My_Plugin_Loader {}        // correct
class Loader {}                  // wrong

define( 'MY_PLUGIN_VER', '1.0' );  // correct
define( 'VERSION', '1.0' );        // wrong

add_action( 'my_plugin_loaded', ... );   // correct
add_action( 'loaded', ... );             // wrong — unprefixed hook
```

## WordPress-Docs Sniffs

### Function/Method Docblocks
```php
/**
 * Brief description of function purpose.
 *
 * Longer description if needed.
 *
 * @since 1.0.0
 *
 * @param int    $post_id  Post ID.
 * @param string $context  Optional. Context. Default 'view'.
 * @return string|false Title string or false on failure.
 */
function my_plugin_get_title( int $post_id, string $context = 'view' ) {}
```

Required tags: `@since`, `@param` (one per param), `@return`.

### Class Docblocks
```php
/**
 * Handles plugin initialisation.
 *
 * @since 1.0.0
 */
class My_Plugin_Init {}
```

### File Docblocks
```php
<?php
/**
 * Plugin main file.
 *
 * @package My_Plugin
 * @since   1.0.0
 */
```

## DB Sniffs

```php
// DirectDatabaseQuery — required when no WP wrapper exists
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- no WP API for bulk delete
$wpdb->query( ... );

// NoCaching — required with DirectQuery
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- result intentionally uncached
$wpdb->get_results( ... );

// SlowDBQuery — meta_query, tax_query in WP_Query
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- indexed meta key
new WP_Query( [ 'meta_key' => '_my_indexed_key', 'meta_value' => '1' ] );
```

## Suppress Cheat Sheet

```php
// Specific sniff on same line
$x = 1; // phpcs:ignore Sniff.Category.Name

// Specific sniff on next line
// phpcs:ignore Sniff.Category.Name
$x = 1;

// Block (multiple lines)
// phpcs:disable Sniff.Category.Name
$x = 1;
$y = 2;
// phpcs:enable Sniff.Category.Name

// Whole file (top of file — use sparingly)
// phpcs:disable WordPress.Security.NonceVerification
```
