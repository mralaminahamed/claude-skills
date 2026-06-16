# AcmeBlocks — Entry Points & Conventions

AcmeBlocks = Elementor + WooCommerce addon. Two plugins, two repos. **Issues are tracked in the free repo; code + PRs may belong to either.**

## Repos ↔ local plugin dirs

| Plugin dir (`wp-content/plugins/`) | GitHub repo | What it is |
|---|---|---|
| `acme-blocks-free/` | `acme-org/acme-blocks` | **AcmeBlocks (Free)** v4.x. Issues #NNN are filed here. |
| `acme-blocks-pro/` | `acme-org/acme-blocks-pro` | **AcmeBlocks Pro**. 55+ pro widgets, builder modules. |

Confirm with `git -C <dir> remote get-url origin`. A free-repo issue (e.g. `acme-org/acme-blocks#247`) is very often fixed in `acme-blocks-pro` → PR opens on `acme-blocks-pro`, body says `Closes acme-org/acme-blocks#247`.

Free + Pro both work on branch `feature-refactor` (the in-flight restructure, tracker #154).

## Free → Pro contract

Pro hard-depends on Free and hooks its filters/actions:
- **Widget registry:** Free `acme_blocks_widgets()` (`acme-blocks-free/app/Helpers/functions.php`) declares ALL widgets free + pro; pro ones flagged `pro_feature => true`. Pro ships only the render class, loaded from `acme-blocks-pro/widgets/{slug}/{slug}.php`.
- **Registration loop:** `acme-blocks-free/app/Controllers/Common/Widgets.php::register_widgets()` iterates `acme_blocks_active_widgets()` (option `acme_blocks_widgets`), requires the pro file when `acme_blocks_is_pro_feature($slug)`, instantiates `Acme\AcmeBlocks_Pro\{Class}`.
- **Class→id:** `acme_blocks_get_widget_id(__CLASS__)` = lowercase, `_`→`-`, namespace stripped. `get_name()` returns it; `get_title()/get_icon()/get_categories()` read `acme_blocks_get_widget($id)`.
- **Layout switcher / unified widgets:** unified widgets dispatch layouts via the `acme_blocks_{group}_layouts` filter; pro appends pro layouts. If nothing registers that filter, switcher panes render empty.

## Key files by symptom

| Symptom | Look at |
|---|---|
| Widget missing/blank in **editor** | the widget's `render()` — check for context guards (`is_product()`, cookie, sale schedule) that early-`return` with no edit-mode placeholder |
| Widget not registered | `acme_blocks_active_widgets()` (option) + `register_widgets()` loop + pro file path exists |
| Title/icon/category null | `acme_blocks_widgets()` missing the `{slug}` key |
| Template library: categories / action status | server-side Library API (not plugin code) |
| Layout switcher dead | `acme-blocks-pro/app/Layout_Switcher.php` + missing `acme_blocks_{group}_layouts` providers |
| Per-widget JS/CSS not loading | `get_script_depends()/get_style_depends()` handles must be registered; pro `Front::enqueue_scripts()` ships one bundle, per-widget assets register in the widget |

## Editor-render gotcha (recurring bug class)

Context-dependent widgets (Barcode, Recently Viewed, Sale Countdown) bail when their runtime context is absent — no single-product page, empty `recently_viewed` cookie, no active sale. In the Elementor **editor/preview** that context never exists, so they render nothing. Fix pattern: in `acme_blocks_is_edit_mode() || acme_blocks_is_preview_mode()`, fall back to a sample product / seeded data / demo so the widget is visible and styleable. Front-end behaviour stays unchanged.

## Conventions

- **Never use deprecated `ab_*` aliases.** The 4.29 restructure renamed `ab_*` → `acme_blocks_*`; aliases live in `acme-blocks-free/app/BC.php` and emit `_deprecated_function()` under WP_DEBUG. Use canonical names: `acme_blocks_is_edit_mode`, `acme_blocks_is_preview_mode`, `acme_blocks_get_product_id`, `acme_blocks_is_woocommerce_activated`, `acme_blocks_get_widget`, `acme_blocks_get_widget_id`. When editing a method, convert deprecated calls inside that hunk too.
- No build step — static assets; `*.min.*` shipped as-is. `ACME_BLOCKS_PRO_DEBUG`/`ACME_BLOCKS_DEBUG` (default true in dev) toggles min vs non-min. Missing `.min` files are a production-only 404 (debug off).
- Commit scope = the widget/module slug (`product-barcode`, `recently-viewed-products`) or a system area (`library`, `widgets`, `build`).
- Verify PHP with `php -l <file>` before commit. Editor-context render has no automated harness — verify manually, say so in the PR.
