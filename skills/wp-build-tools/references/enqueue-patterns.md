# WordPress Asset Enqueue Patterns

## Standard pattern with .asset.php

Always use the generated `.asset.php` for dependencies and version hash.

```php
function my_plugin_enqueue_scripts() {
    $asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
    if ( ! file_exists( $asset_file ) ) {
        return; // Build not run yet
    }
    $asset = include $asset_file;
    // $asset = [ 'dependencies' => ['wp-element', 'wp-i18n', ...], 'version' => 'abc123' ]

    wp_enqueue_script(
        'my-plugin-script',
        plugin_dir_url( __FILE__ ) . 'build/index.js',
        $asset['dependencies'],
        $asset['version'],
        true  // in footer
    );
}
```

## Admin-only scripts

```php
add_action( 'admin_enqueue_scripts', function( string $hook_suffix ) {
    // Only on specific admin pages
    if ( ! in_array( $hook_suffix, [ 'toplevel_page_my-plugin', 'my-plugin_page_settings' ], true ) ) {
        return;
    }

    $asset = include plugin_dir_path( __FILE__ ) . 'build/admin.asset.php';
    wp_enqueue_script( 'my-plugin-admin', plugin_dir_url( __FILE__ ) . 'build/admin.js', $asset['dependencies'], $asset['version'], true );
    wp_enqueue_style( 'my-plugin-admin-style', plugin_dir_url( __FILE__ ) . 'build/style-admin.css', [], $asset['version'] );

    // Pass data to JS
    wp_localize_script( 'my-plugin-admin', 'myPluginAdmin', [
        'nonce'  => wp_create_nonce( 'wp_rest' ),
        'apiUrl' => rest_url( 'my-plugin/v1/' ),
        'i18n'   => [
            'save'   => __( 'Save', 'my-plugin' ),
            'cancel' => __( 'Cancel', 'my-plugin' ),
        ],
    ] );
} );
```

## Frontend scripts (conditionally loaded)

```php
add_action( 'wp_enqueue_scripts', function() {
    // Only on singular pages with shortcode
    if ( ! is_singular() ) return;

    global $post;
    if ( ! has_shortcode( $post->post_content, 'my_plugin' ) ) return;

    $asset = include plugin_dir_path( __FILE__ ) . 'build/frontend.asset.php';
    wp_enqueue_script( 'my-plugin-frontend', plugin_dir_url( __FILE__ ) . 'build/frontend.js', $asset['dependencies'], $asset['version'], true );
    wp_enqueue_style( 'my-plugin-frontend-style', plugin_dir_url( __FILE__ ) . 'build/style.css', [], $asset['version'] );
} );
```

## Block editor assets (via block.json)

Let `register_block_type()` handle enqueuing — do NOT manually enqueue block scripts.

```php
// In your block's main PHP file or plugin init:
add_action( 'init', function() {
    register_block_type( plugin_dir_path( __FILE__ ) . 'build/my-block' );
    // Reads build/my-block/block.json automatically
    // Enqueues editorScript, script, editorStyle, style based on block.json
} );
```

**block.json example:**
```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-plugin/my-block",
    "title": "My Block",
    "textdomain": "my-plugin",
    "editorScript": "file:./index.js",
    "editorStyle":  "file:./index.css",
    "style":        "file:./style-index.css",
    "viewScript":   "file:./view.js"
}
```

## wp_add_inline_script for small data

Avoid `wp_localize_script` for non-string data (it JSON-encodes incorrectly for some types). Use `wp_add_inline_script` instead:

```php
wp_add_inline_script(
    'my-plugin-script',
    'const myPluginData = ' . wp_json_encode( [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'my_action' ),
        'items'   => $items_array,  // properly encoded as JSON array
    ] ) . ';',
    'before'  // 'before' or 'after' — 'before' ensures data available when script runs
);
```

## Script module (ES modules, WP 6.5+)

```php
add_action( 'wp_enqueue_scripts', function() {
    wp_register_script_module(
        '@my-plugin/frontend',
        plugin_dir_url( __FILE__ ) . 'build/frontend.js',
        [ '@wordpress/interactivity' ],
        '1.0.0'
    );
    wp_enqueue_script_module( '@my-plugin/frontend' );
} );
```

## CSS from JS entry (no .asset.php)

For standalone SCSS entry points in webpack config:

```php
// No .asset.php for pure CSS entry — use filemtime() or static version
wp_enqueue_style(
    'my-plugin-style',
    plugin_dir_url( __FILE__ ) . 'build/style-main.css',
    [],
    filemtime( plugin_dir_path( __FILE__ ) . 'build/style-main.css' )
);
```

## RTL support

```php
// Automatic RTL: name your RTL file with -rtl suffix
// build/style-main.css    → LTR
// build/style-main-rtl.css → loaded automatically by WP when RTL locale active

// Manual RTL flip:
wp_style_add_data( 'my-plugin-style', 'rtl', 'replace' );
// WP appends -rtl to the handle and loads build/style-main-rtl.css
```

## Checking for script/style registration

```php
if ( wp_script_is( 'my-plugin-script', 'enqueued' ) ) { ... }
if ( wp_script_is( 'my-plugin-script', 'registered' ) ) { ... }
// States: 'registered', 'enqueued', 'done', 'to_do'
```

## Conditional dequeue (e.g. conflicts)

```php
add_action( 'wp_enqueue_scripts', function() {
    // Dequeue a conflicting library only on our pages
    if ( is_page( 'my-plugin-page' ) ) {
        wp_dequeue_script( 'conflicting-slider' );
        wp_deregister_script( 'conflicting-slider' );
    }
}, 100 ); // High priority to run after other plugins enqueue
```
