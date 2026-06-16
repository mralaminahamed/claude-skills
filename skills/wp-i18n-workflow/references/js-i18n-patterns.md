# JavaScript i18n Patterns (@wordpress/i18n)

## Install & Import

```bash
npm install @wordpress/i18n
```

```js
import { __, _n, _x, _nx, sprintf } from '@wordpress/i18n';
```

## Core Functions

### `__()` — Basic translation

```js
const label = __( 'Save Changes', 'my-plugin' );
```

### `_x()` — With context (disambiguate same string)

```js
const post   = _x( 'Post', 'noun: a blog post', 'my-plugin' );
const action = _x( 'Post', 'verb: submit the form', 'my-plugin' );
```

### `_n()` — Singular/plural

```js
const message = _n(
    'One item found.',    // singular
    '%d items found.',    // plural
    count,                // number (determines which form)
    'my-plugin'
);
// NOTE: _n() returns the pattern string — use sprintf() to insert the count:
const text = sprintf( _n( 'One item', '%d items', count, 'my-plugin' ), count );
```

### `_nx()` — Plural with context

```js
const text = sprintf(
    _nx( 'One comment', '%d comments', count, 'comment count in list', 'my-plugin' ),
    count
);
```

### `sprintf()` — WP's own sprintf

```js
import { sprintf } from '@wordpress/i18n';

// %s = string, %d = integer, %f = float, %% = literal %
const greeting = sprintf( __( 'Hello, %s!', 'my-plugin' ), username );
const msg      = sprintf( __( 'Page %1$d of %2$d', 'my-plugin' ), current, total );
```

**Important:** Always `sprintf` AFTER `__()` — never embed variables inside the translatable string:

```js
// Wrong — dynamic string breaks extraction
__( `Hello, ${username}!`, 'my-plugin' )

// Correct
sprintf( __( 'Hello, %s!', 'my-plugin' ), username )
```

## React / JSX

```jsx
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

// Basic
<p>{ __( 'Settings saved.', 'my-plugin' ) }</p>

// With variable
<p>{ sprintf( __( 'Hello, %s!', 'my-plugin' ), user.name ) }</p>

// With JSX inside translated string (links, bold, etc.)
<p>
    { createInterpolateElement(
        sprintf(
            /* translators: %s: link to settings page */
            __( 'See the <a>settings page</a> for details.', 'my-plugin' ),
            ''  // placeholder for link text injected by createInterpolateElement
        ),
        {
            a: <a href={ settingsUrl } />,
        }
    ) }
</p>
```

## Loading Translations in PHP

```php
// Must call wp_set_script_translations() after wp_enqueue_script()
wp_enqueue_script(
    'my-plugin-editor',
    plugins_url( 'build/index.js', __FILE__ ),
    $asset['dependencies'],
    $asset['version']
);

wp_set_script_translations(
    'my-plugin-editor',      // must match handle above
    'my-plugin',             // text domain
    plugin_dir_path( __FILE__ ) . 'languages'  // path to .json files
);
```

## Generating JS Translation JSON Files

```bash
# From .po file (generates JSON alongside it)
wp i18n make-json languages/my-plugin-fr_FR.po

# Without purging (keep .po untouched)
wp i18n make-json languages/my-plugin-fr_FR.po --no-purge

# From POT (create empty .po first, then .json)
msginit --input=languages/my-plugin.pot --locale=fr_FR --output=languages/my-plugin-fr_FR.po
wp i18n make-json languages/my-plugin-fr_FR.po --no-purge
```

Output: `languages/my-plugin-fr_FR-{md5hash}.json`
The MD5 is derived from the relative path of the JS source file.

## Locale Data at Runtime (alternative to .json files)

```js
import { setLocaleData } from '@wordpress/i18n';

// Inject translations via wp_add_inline_script()
setLocaleData(
    {
        'Save Changes': [ 'Enregistrer' ],
        'One item%d items': [ 'Un élément', '%d éléments' ],
    },
    'my-plugin'
);
```

PHP side to inject:

```php
$locale_data = [
    'Save Changes'            => [ 'Enregistrer' ],
];
wp_add_inline_script(
    'my-plugin-editor',
    'wp.i18n.setLocaleData(' . wp_json_encode( $locale_data ) . ', "my-plugin");',
    'before'
);
```

## Domain Extraction Configuration

`wp i18n make-pot` and `@wordpress/babel-plugin-makepot` both extract JS strings. Ensure your build doesn't minify `__` / `_n` function names:

```js
// webpack.config.js
module.exports = {
    ...defaultConfig,
    optimization: {
        ...defaultConfig.optimization,
        // Don't mangle i18n function names in production
        minimizer: defaultConfig.optimization?.minimizer ?? [],
    },
};
```

With `@wordpress/scripts` this is handled automatically — `__`, `_n`, `_x`, `_nx`, `sprintf` are preserved.

## Plural Forms per Locale

Some languages have complex plural rules. WP handles this via `Plural-Forms` in the .po header — the JS runtime applies the same rules from the .json file. You don't need to manage this manually when using `wp i18n make-json`.

Example .po header for Russian (3 plural forms):
```
Plural-Forms: nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);
```

Always test with languages that have 3+ plural forms (Russian, Polish, Arabic) to ensure `_n()` returns correct form.
