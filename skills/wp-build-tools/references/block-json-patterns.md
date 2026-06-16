# block.json Patterns

## Full Schema Reference

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-plugin/my-block",
    "version": "1.0.0",
    "title": "My Block",
    "category": "common",
    "icon": "star-filled",
    "description": "A custom block for doing X.",
    "keywords": [ "my", "block", "custom" ],
    "textdomain": "my-plugin",

    "attributes": {
        "content": {
            "type": "string",
            "source": "html",
            "selector": "p",
            "default": ""
        },
        "url": {
            "type": "string",
            "format": "uri",
            "default": ""
        },
        "alignment": {
            "type": "string",
            "enum": [ "left", "center", "right" ],
            "default": "left"
        },
        "count": {
            "type": "integer",
            "minimum": 1,
            "maximum": 100,
            "default": 5
        },
        "isEnabled": {
            "type": "boolean",
            "default": false
        },
        "items": {
            "type": "array",
            "items": { "type": "string" },
            "default": []
        },
        "imageId": {
            "type": "integer",
            "default": 0
        }
    },

    "supports": {
        "html": false,
        "align": [ "wide", "full" ],
        "alignWide": true,
        "anchor": true,
        "className": true,
        "color": {
            "background": true,
            "text": true,
            "link": true,
            "gradients": true
        },
        "typography": {
            "fontSize": true,
            "lineHeight": true
        },
        "spacing": {
            "margin": true,
            "padding": true,
            "blockGap": true
        },
        "dimensions": {
            "minHeight": true
        },
        "border": {
            "radius": true,
            "color": true,
            "width": true,
            "style": true
        },
        "__experimentalBorder": {
            "radius": true
        }
    },

    "usesContext": [ "postId", "postType" ],

    "providesContext": {
        "my-plugin/itemCount": "count"
    },

    "selectors": {
        "root": ".wp-block-my-plugin-my-block"
    },

    "styles": [
        { "name": "default", "label": "Default", "isDefault": true },
        { "name": "rounded", "label": "Rounded" }
    ],

    "variations": [
        {
            "name": "large",
            "title": "Large",
            "description": "Large variant.",
            "attributes": { "count": 10 },
            "isDefault": false
        }
    ],

    "example": {
        "attributes": {
            "content": "Example content.",
            "count": 3
        }
    },

    "editorScript": "file:./build/index.js",
    "editorStyle":  "file:./build/index.css",
    "script":       "file:./build/frontend.js",
    "style":        "file:./build/style-index.css",
    "viewScript":   "file:./build/view.js",
    "render":       "file:./render.php"
}
```

## Registering Blocks in PHP

```php
// Register single block (auto-reads block.json)
add_action( 'init', function() {
    register_block_type( __DIR__ . '/build/blocks/my-block' );
} );

// Register all blocks in a directory
add_action( 'init', function() {
    $blocks = glob( __DIR__ . '/build/blocks/*', GLOB_ONLYDIR );
    foreach ( $blocks as $block ) {
        register_block_type( $block );
    }
} );
```

## Dynamic Block (render callback)

```php
register_block_type( __DIR__ . '/build', [
    'render_callback' => function( array $attributes, string $content, \WP_Block $block ): string {
        $count     = absint( $attributes['count'] ?? 5 );
        $alignment = sanitize_key( $attributes['alignment'] ?? 'left' );

        $wrapper_attributes = get_block_wrapper_attributes( [
            'class' => "align-{$alignment}",
        ] );

        $items = get_posts( [ 'posts_per_page' => $count ] );
        $html  = '<ul>';
        foreach ( $items as $item ) {
            $html .= '<li>' . esc_html( $item->post_title ) . '</li>';
        }
        $html .= '</ul>';

        return sprintf( '<div %s>%s</div>', $wrapper_attributes, $html );
    },
] );
```

Or use `render.php` (referenced in `block.json`'s `"render"` key):

```php
// build/blocks/my-block/render.php
// $attributes, $content, $block are available as variables
$count    = absint( $attributes['count'] ?? 5 );
$wrapper  = get_block_wrapper_attributes();
$items    = get_posts( [ 'posts_per_page' => $count ] );

?>
<div <?php echo $wrapper; ?>>
    <ul>
        <?php foreach ( $items as $item ) : ?>
            <li><?php echo esc_html( $item->post_title ); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
```

## JS Block Edit Component (apiVersion 3)

```js
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const { content, count } = attributes;
    const blockProps = useBlockProps();

    return (
        <div { ...blockProps }>
            <RichText
                tagName="p"
                value={ content }
                onChange={ ( value ) => setAttributes( { content: value } ) }
                placeholder={ __( 'Enter content…', 'my-plugin' ) }
            />
        </div>
    );
}
```

## Attribute Sources

| Source | Description | Example selector |
|---|---|---|
| `"html"` | Inner HTML of a selector | `"selector": "p"` |
| `"text"` | Text content of selector | `"selector": "h2"` |
| `"attribute"` | HTML attribute value | `"selector": "img", "attribute": "src"` |
| `"query"` | Array from repeated elements | `"selector": "li", "query": { "text": { "type": "string", "source": "text" } }` |
| _(none)_ | Stored in block comment | — |

## InnerBlocks

```js
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

const ALLOWED = [ 'core/paragraph', 'core/image', 'core/heading' ];

export function Edit() {
    return (
        <div { ...useBlockProps() }>
            <InnerBlocks
                allowedBlocks={ ALLOWED }
                template={ [
                    [ 'core/heading', { level: 2, placeholder: 'Title' } ],
                    [ 'core/paragraph', { placeholder: 'Content…' } ],
                ] }
                templateLock="insert"  // 'all' | 'insert' | false
            />
        </div>
    );
}

export function Save() {
    return (
        <div { ...useBlockProps.save() }>
            <InnerBlocks.Content />
        </div>
    );
}
```
