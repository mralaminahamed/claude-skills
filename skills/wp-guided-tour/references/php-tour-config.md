# PHP Tour Config Reference

Tour configs are returned by a single function filtered via `apply_filters`. Each key is a scope string matching `getCurrentScope()`.

## Full config shape

```php
function myplugin_get_tour_configs() {
    return apply_filters( 'myplugin_tour_configs', array(

        'dashboard' => array(
            'autoStart' => true,      // only ONE scope should be true
            'pages'     => array( 'myplugin' ),  // metadata only — not used for routing
            'steps'     => array(

                // Centered popover (no element)
                array(
                    'popover' => array(
                        'title'       => __( 'Welcome!', 'my-plugin' ),
                        'description' => __( 'Quick intro.', 'my-plugin' ),
                        'side'        => 'bottom',
                    ),
                ),

                // Element-targeted step
                array(
                    'element' => '#stable-element-id',
                    'popover' => array(
                        'title'       => __( 'Step Title', 'my-plugin' ),
                        'description' => __( 'Step description.', 'my-plugin' ),
                        'side'        => 'right',  // top | bottom | left | right
                    ),
                ),

            ),
        ),

        'settings' => array(
            'autoStart' => false,
            'pages'     => array( 'myplugin-settings' ),
            'steps'     => array( /* ... */ ),
        ),

    ) );
}
```

## Rules

- `autoStart: true` on exactly one scope (primary onboarding page only).
- `pages` array is metadata — actual routing is `getCurrentScope()` in JS.
- Always wrap strings in `__()` — run `makepot` after adding steps.
- `side` values: `top`, `bottom`, `left`, `right`. Do NOT set `align: 'start'` (it's the default).
- Tailwind class selectors need backslash escaping in PHP strings:
  ```php
  // CSS: .border-[#F0EDFB]   PHP: '.border-\\[\\#F0EDFB\\]'
  ```
