# Translation Debugging Guide

## Common Failure Modes

### 1. Text domain mismatch

Symptom: All strings show in English.

```bash
# Check text domain in plugin header
grep "Text Domain:" my-plugin.php

# Check load_plugin_textdomain() call
grep -r "load_plugin_textdomain" includes/ src/ *.php

# Check all __() calls use correct domain
grep -rn "__(" includes/ src/ | grep -v "'my-plugin'" | grep -v "vendor"
```

Fix: All three must match exactly:
```php
// Plugin header
Text Domain: my-plugin

// PHP code
__( 'Text', 'my-plugin' )

// load_plugin_textdomain
load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
```

### 2. .mo file missing or wrong locale

```bash
# Check WP locale
wp option get WPLANG

# List .mo files
ls -la languages/*.mo

# Expected filename for French
# languages/my-plugin-fr_FR.mo

# Compile from .po
wp i18n make-mo languages/my-plugin-fr_FR.po
```

### 3. String not in POT file

```bash
# Search for string in POT
grep -A5 "Your exact string" languages/my-plugin.pot

# Regenerate POT
wp i18n make-pot . languages/my-plugin.pot --domain=my-plugin
grep -A5 "Your exact string" languages/my-plugin.pot
```

If still missing, check string is:
- Using `__()`, `_e()`, `esc_html__()` etc. (not just echoed)
- Has string literal (not variable) as first argument
- In a PHP or JS file (not a config or data file)
- Not in an excluded directory

### 4. JS translations not loading

```bash
# Check .json files exist
ls languages/*.json

# Generate from .po
wp i18n make-json languages/my-plugin-fr_FR.po --no-purge

# Verify script handle matches
# In PHP:
wp_set_script_translations( 'my-plugin-editor', 'my-plugin', plugin_dir_path( __FILE__ ) . 'languages' );
# Handle 'my-plugin-editor' must match wp_enqueue_script( 'my-plugin-editor', ... )
```

JSON file name format: `{domain}-{locale}-{md5}.json`
The MD5 is derived from the JS source file path. If build output changes, MD5 changes — regenerate JSON.

### 5. load_plugin_textdomain() not called

For WP.org plugins with language packs, WP auto-loads from `WP_LANG_DIR/plugins/`. Manual `load_plugin_textdomain()` is for bundled `.mo` files.

```php
// Correct hook
add_action( 'init', function() {
    load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
} );
```

### 6. Translator comment not extracted

Translator comment must be on the line **immediately** preceding the translatable function call. No blank lines.

```php
// Wrong — blank line between comment and function
/* translators: %s: post title */

$title = sprintf( __( 'Post: %s', 'my-plugin' ), $post_title );

// Correct
/* translators: %s: post title */
$title = sprintf( __( 'Post: %s', 'my-plugin' ), $post_title );
```

### 7. Dynamic string — not extractable

```php
// Cannot be extracted
__( $dynamic_string, 'my-plugin' )

// Fix: use static lookup table
function my_plugin_get_status_label( string $status ): string {
    $labels = [
        'active'   => __( 'Active', 'my-plugin' ),
        'inactive' => __( 'Inactive', 'my-plugin' ),
        'pending'  => __( 'Pending', 'my-plugin' ),
    ];
    return $labels[ $status ] ?? $status;
}
```

## Debug Mode

```php
add_action( 'init', function() {
    add_filter( 'load_textdomain_mofile', function( $mofile, $domain ) {
        if ( 'my-plugin' === $domain ) {
            error_log( "my-plugin: loading MO from: {$mofile} (exists: " . ( file_exists( $mofile ) ? 'yes' : 'NO' ) . ")" );
        }
        return $mofile;
    }, 10, 2 );
}, 1 );
```

## Verify .mo File

```bash
# Check integrity
msgfmt --check languages/my-plugin-fr_FR.po -o /dev/null

# Count translated strings
msgfmt --statistics languages/my-plugin-fr_FR.po -o /dev/null
# Output: X translated messages, Y fuzzy, Z untranslated
```

## Language Pack vs Bundled .mo

| Scenario | How translations load |
|---|---|
| Plugin on WP.org | WP auto-downloads language pack from translate.wordpress.org |
| Premium/off-repo plugin | Must bundle `.mo` files in `languages/` dir |
| Development | Load from `languages/` via `load_plugin_textdomain()` |

Language packs: `{WP_CONTENT_DIR}/languages/plugins/my-plugin-fr_FR.mo`
Bundled: `{plugin_dir}/languages/my-plugin-fr_FR.mo`
WP checks language pack first, falls back to bundled.
