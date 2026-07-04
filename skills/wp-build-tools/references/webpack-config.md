# @wordpress/scripts Webpack Configuration

## Default Behaviour (no custom config)

With only `package.json` scripts set to `wp-scripts`:
- Entry: `src/index.js`
- Output: `build/index.js` + `build/index.asset.php`
- Externals: all `@wordpress/*` and `react` packages
- CSS: extracted to `build/style-index.css`
- Source maps: development only

## Custom webpack.config.js Patterns

### Multiple entry points

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    entry: {
        // Block editor scripts
        'block-editor':    './src/blocks/index.js',
        // Admin page
        'admin':           './src/admin/index.js',
        // Front-end interactive
        'frontend':        './src/frontend/index.js',
        // Pure CSS entry (no .asset.php generated)
        'style-admin':     './src/admin/admin.scss',
        'style-frontend':  './src/frontend/frontend.scss',
    },
};
```

### Multiple blocks

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const glob = require( 'glob' );

// Auto-discover all blocks
const blockEntries = glob.sync( './src/blocks/*/index.js' ).reduce( ( acc, file ) => {
    const blockName = path.basename( path.dirname( file ) );
    acc[ `blocks/${ blockName }` ] = file;
    return acc;
}, {} );

module.exports = {
    ...defaultConfig,
    entry: {
        ...defaultConfig.entry,
        ...blockEntries,
    },
};
```

### TypeScript support

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    resolve: {
        ...defaultConfig.resolve,
        extensions: [ ...( defaultConfig.resolve?.extensions ?? [] ), '.ts', '.tsx' ],
    },
};
```

Also add `tsconfig.json`:
```json
{
    "compilerOptions": {
        "target": "ES2018",
        "module": "ESNext",
        "moduleResolution": "Node",
        "jsx": "react",
        "strict": true,
        "outDir": "./build",
        "rootDir": "./src"
    },
    "include": [ "src/**/*" ]
}
```

### Custom output directory

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
    ...defaultConfig,
    output: {
        ...defaultConfig.output,
        path: path.resolve( __dirname, 'assets/dist' ),
    },
};
```

### Add aliases

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
    ...defaultConfig,
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            ...defaultConfig.resolve?.alias,
            '@': path.resolve( __dirname, 'src' ),
            '@components': path.resolve( __dirname, 'src/components' ),
        },
    },
};
```

## package.json Scripts

```json
{
    "scripts": {
        "build":           "wp-scripts build",
        "start":           "wp-scripts start",
        "start:hot":       "wp-scripts start --hot",
        "lint:js":         "wp-scripts lint-js",
        "lint:js:fix":     "wp-scripts lint-js --fix",
        "lint:css":        "wp-scripts lint-style",
        "lint:css:fix":    "wp-scripts lint-style --fix",
        "lint:md:docs":    "wp-scripts lint-md-docs",
        "lint:pkg-json":   "wp-scripts lint-pkg-json",
        "format":          "wp-scripts format",
        "test:unit":       "wp-scripts test-unit-js",
        "test:e2e":        "wp-scripts test-playwright",
        "packages-update": "wp-scripts packages-update",
        "plugin-zip":      "wp-scripts plugin-zip"
    }
}
```

## Common package.json devDependencies

```json
{
    "devDependencies": {
        "@wordpress/scripts": "^32.0.0",
        "@wordpress/eslint-plugin": "^22.0.0",
        "@wordpress/prettier-config": "^4.0.0",
        "classnames": "^2.3.2"
    },
    "dependencies": {
        "@wordpress/components": "*",
        "@wordpress/data": "*",
        "@wordpress/element": "*",
        "@wordpress/i18n": "*"
    }
}
```

**Note:** `@wordpress/*` packages are externals in production build — mark them as `dependencies` (not `devDependencies`) for correct npm metadata, but they won't be bundled.

## Source Maps

```js
// Development: inline source maps (default from @wordpress/scripts)
// Production: no source maps (default)

// Override to always generate source maps:
module.exports = {
    ...defaultConfig,
    devtool: process.env.NODE_ENV === 'production' ? 'source-map' : 'eval-source-map',
};
```

## Analysing Bundle Size

```bash
# Generate stats file
npx wp-scripts build --stats

# Visualise with webpack-bundle-analyzer
npx webpack-bundle-analyzer build/stats.json
```

## .eslintrc.js for WP

```js
module.exports = {
    extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
    rules: {
        'no-console': 'warn',
    },
};
```

## .stylelintrc.json for WP

```json
{
    "extends": [ "@wordpress/stylelint-config" ]
}
```

## Vite config (alternative to @wordpress/scripts)

```js
// vite.config.js
import { defineConfig } from 'vite';

export default defineConfig( {
    build: {
        outDir: 'build',
        rollupOptions: {
            external: [
                // Mark WP globals as external (available as window.wp.*)
                /^@wordpress\/.*/,
                'react',
                'react-dom',
                'lodash',
                'jquery',
            ],
            output: {
                globals: {
                    react: 'React',
                    'react-dom': 'ReactDOM',
                    lodash: '_',
                    '@wordpress/element': 'wp.element',
                    '@wordpress/i18n': 'wp.i18n',
                    '@wordpress/data': 'wp.data',
                    '@wordpress/components': 'wp.components',
                },
            },
        },
    },
} );
```
