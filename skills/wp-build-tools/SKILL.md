---
name: wp-build-tools
description: Use when setting up or debugging the JavaScript/CSS build pipeline for a WordPress plugin — @wordpress/scripts, webpack, Vite, block editor assets, asset enqueuing with .asset.php files, or compiling Sass/PostCSS. Not for block registration logic — use the official wp-block-development skill.
---

# WordPress Plugin Build Tools

Configure and operate the JS/CSS build pipeline for WordPress plugins: `@wordpress/scripts` (webpack-based), Vite alternative, asset manifest handling, and correct enqueuing with the generated `.asset.php` dependency file.

## When to use

- "Set up `@wordpress/scripts`", "configure webpack for my plugin".
- "Build blocks and admin scripts", "compile Sass for a plugin".
- "Why isn't my JS loading?", "fix asset enqueue with versioned hash".
- "Switch from @wordpress/scripts to Vite".
- "Set up separate entry points for front-end vs admin vs block editor".

**Not for:** Block registration, `block.json` structure, or Gutenberg API — use the official `wp-block-development` skill. PHP-side REST API — use `wp-rest-api`.

## Method

### 1. Install @wordpress/scripts

```bash
npm install --save-dev @wordpress/scripts
```

**`package.json`:**
```json
{
  "scripts": {
    "build":   "wp-scripts build",
    "start":   "wp-scripts start",
    "lint:js": "wp-scripts lint-js",
    "lint:css": "wp-scripts lint-style"
  }
}
```

Default entry point: `src/index.js` → `build/index.js` + `build/index.asset.php`.

### 2. Multiple entry points

Create `webpack.config.js` at plugin root to override the default entry:

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    entry: {
        'admin':        './src/admin/index.js',
        'frontend':     './src/frontend/index.js',
        'block-editor': './src/blocks/index.js',
        'style-admin':  './src/admin/admin.scss',
    },
};
```

Outputs:
```
build/
├── admin.js          + admin.asset.php
├── frontend.js       + frontend.asset.php
├── block-editor.js   + block-editor.asset.php
└── style-admin.css   (no .asset.php for pure CSS entry)
```

### 3. Enqueue assets correctly

The `.asset.php` file contains the dependency array and a content hash — always use it.

```php
function my_plugin_enqueue_admin_assets() {
    $asset_file = plugin_dir_path( __FILE__ ) . 'build/admin.asset.php';
    if ( ! file_exists( $asset_file ) ) return;

    $asset = include $asset_file;

    wp_enqueue_script(
        'my-plugin-admin',
        plugin_dir_url( __FILE__ ) . 'build/admin.js',
        $asset['dependencies'],  // auto-includes wp-element, wp-i18n, etc.
        $asset['version'],       // content hash — cache busted on change
        true                     // in footer
    );

    wp_enqueue_style(
        'my-plugin-admin-style',
        plugin_dir_url( __FILE__ ) . 'build/style-admin.css',
        [],
        $asset['version']
    );

    // Pass PHP data to JS
    wp_localize_script( 'my-plugin-admin', 'myPluginData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'my_plugin_action' ),
        'apiUrl'  => rest_url( 'my-plugin/v1/' ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'my_plugin_enqueue_admin_assets' );
```

For block assets registered via `block.json` — do NOT manually enqueue; WP handles it:
```php
register_block_type( __DIR__ . '/build/my-block' ); // reads block.json automatically
```

### 4. Sass / PostCSS

`@wordpress/scripts` supports Sass out of the box (via webpack sass-loader). No extra config needed for `.scss` files imported in JS:

```js
// src/admin/index.js
import './admin.scss';
```

For standalone `.scss` entry (CSS-only build):
```js
// webpack.config.js entry
entry: {
    'admin-styles': './src/admin/admin.scss',
}
```

Output: `build/admin-styles.css` (no `.asset.php` generated for pure CSS entries — hardcode version or use `filemtime()`).

PostCSS config (`postcss.config.js`) is picked up automatically if present:
```js
module.exports = {
    plugins: {
        autoprefixer: {},
        'postcss-custom-properties': {},
    },
};
```

### 5. Vite alternative

For non-block plugins where `@wordpress/scripts` dependency auto-detection isn't needed:

```bash
npm install --save-dev vite @vitejs/plugin-legacy
```

**`vite.config.js`:**
```js
import { defineConfig } from 'vite';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig( {
    plugins: [ legacy( { targets: [ 'defaults', 'ie >= 11' ] } ) ],
    build: {
        outDir: 'build',
        rollupOptions: {
            input: {
                admin: 'src/admin/index.js',
                frontend: 'src/frontend/index.js',
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name]-[hash].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
} );
```

**Caveat:** Vite does not generate `.asset.php`. Manage WP script dependencies manually, or use `wp-scripts` for anything that imports `@wordpress/*` packages (they must be `externals`).

### 6. Externals — don't bundle WordPress packages

`@wordpress/scripts` automatically externalises all `@wordpress/*` imports (they're on the global `wp` object). If you use a custom webpack config, preserve this:

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
// defaultConfig already has the correct externals — spread it, don't replace it
module.exports = { ...defaultConfig, entry: { ... } };
```

Never `import { useState } from 'react'` in WP code — import from `@wordpress/element`:
```js
import { useState, useEffect } from '@wordpress/element';
```

### 7. `.gitignore` and production builds

```gitignore
node_modules/
build/
```

Include `build/` in the SVN/release zip but NOT in git. In the release workflow (`wp-plugin-release` + `wp-org-submission`), run `npm run build` before zipping.

CI build step for GitHub Actions:
```yaml
- uses: actions/setup-node@v4
  with:
    node-version: '20'
    cache: 'npm'
- run: npm ci
- run: npm run build
```

## Notes

- Always use `npm ci` (not `npm install`) in CI — respects `package-lock.json` exactly.
- `@wordpress/scripts` pins its webpack/babel versions; don't add conflicting `webpack` or `babel-loader` to `devDependencies`.
- For TypeScript: `@wordpress/scripts` supports `.ts`/`.tsx` out of the box — just rename files and add `tsconfig.json`.
- Minimum Node version for `@wordpress/scripts` v27+: Node 20.
- Use `wp-scripts lint-js` and `wp-scripts lint-style` in CI alongside PHPCS (`wp-coding-standards`) for full code quality coverage.
