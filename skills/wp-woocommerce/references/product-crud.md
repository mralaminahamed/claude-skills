# WooCommerce Product CRUD

## Get Products

```php
// By ID (preferred)
$product = wc_get_product( $post_id );  // WC_Product|false

// Product type check
$product->get_type();      // 'simple', 'variable', 'grouped', 'external'
$product->is_type( 'simple' );
$product->is_type( [ 'simple', 'variable' ] );
```

## WC_Product Getters

```php
$product->get_id();
$product->get_name();
$product->get_slug();
$product->get_status();          // 'publish', 'draft', etc.
$product->get_description();
$product->get_short_description();
$product->get_sku();
$product->get_price();           // current price (sale or regular)
$product->get_regular_price();
$product->get_sale_price();
$product->get_stock_quantity();
$product->get_stock_status();    // 'instock', 'outofstock', 'onbackorder'
$product->is_in_stock();
$product->get_weight();
$product->get_length();
$product->get_width();
$product->get_height();
$product->get_image_id();
$product->get_gallery_image_ids(); // array of attachment IDs
$product->get_category_ids();      // array of term IDs
$product->get_tag_ids();
$product->get_attributes();        // array of WC_Product_Attribute
$product->get_permalink();
$product->get_date_created();      // WC_DateTime|null
$product->get_date_modified();     // WC_DateTime|null
```

## WC_Product Setters + Save

```php
$product = wc_get_product( $id );
$product->set_name( 'New Name' );
$product->set_regular_price( '29.99' );
$product->set_sale_price( '19.99' );
$product->set_date_on_sale_from( '2026-06-01' );
$product->set_date_on_sale_to( '2026-06-30' );
$product->set_sku( 'MY-SKU-001' );
$product->set_manage_stock( true );
$product->set_stock_quantity( 100 );
$product->set_stock_status( 'instock' );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );  // 'visible', 'catalog', 'search', 'hidden'
$product->save(); // returns post ID
```

## Create New Product

```php
$product = new \WC_Product_Simple();
$product->set_name( 'My New Product' );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_description( 'Full description.' );
$product->set_short_description( 'Short.' );
$product->set_sku( 'NEW-001' );
$product->set_regular_price( '49.99' );
$product->set_manage_stock( true );
$product->set_stock_quantity( 10 );
$product->set_category_ids( [ $cat_id ] );
$product->set_image_id( $attachment_id );
$product_id = $product->save();
```

## Product Meta (Custom Fields)

```php
// Read
$value = $product->get_meta( '_my_plugin_field' );
$all   = $product->get_meta_data(); // array of WC_Meta_Data

// Write
$product->update_meta_data( '_my_plugin_field', 'value' );
$product->add_meta_data( '_my_plugin_field', 'value', true ); // true = unique
$product->delete_meta_data( '_my_plugin_field' );
$product->save(); // always save after meta changes
```

**Do NOT use `get_post_meta()` / `update_post_meta()` for products** — bypasses WC caching and HPOS.

## Variable Product

```php
/** @var \WC_Product_Variable $variable */
$variable = wc_get_product( $variable_id );

// Get all variations
$variation_ids = $variable->get_children(); // array of post IDs

// Get available attributes (for attribute dropdowns)
$attributes = $variable->get_variation_attributes();
// Returns: [ 'attribute_pa_color' => [ 'Red', 'Blue' ], 'attribute_pa_size' => [ 'S', 'M' ] ]

// Get a specific variation by attributes
$variation_id = $variable->get_matching_variation( [
    'attribute_pa_color' => 'red',
    'attribute_pa_size'  => 's',
] );
$variation = wc_get_product( $variation_id ); // WC_Product_Variation

// Variation getters
$variation->get_parent_id();
$variation->get_variation_id();
$variation->get_attributes();    // [ 'attribute_pa_color' => 'red' ]
$variation->get_price();
$variation->get_sku();
$variation->is_in_stock();
$variation->get_image_id();
```

## wc_get_products() (HPOS-safe query)

```php
$products = wc_get_products( [
    'status'        => 'publish',
    'type'          => 'simple',           // or [ 'simple', 'variable' ]
    'category'      => [ 'shirts' ],       // array of category slugs
    'tag'           => [ 'sale' ],
    'sku'           => 'MY-*',             // partial match with wildcard
    'limit'         => 20,
    'paged'         => 1,
    'orderby'       => 'date',
    'order'         => 'DESC',
    'return'        => 'objects',          // 'objects' or 'ids'
    'meta_query'    => [
        [
            'key'     => '_my_plugin_field',
            'value'   => 'yes',
            'compare' => '=',
        ],
    ],
    'price_list'    => true,   // include price variations in results
    'stock_status'  => 'instock',
] );

// $products is array of WC_Product when return='objects'
// or array of int when return='ids'
```

## Product Attributes

```php
// Read attributes
foreach ( $product->get_attributes() as $attribute ) {
    $attribute->get_name();      // 'pa_color' or custom 'Color'
    $attribute->get_options();   // array of term IDs or values
    $attribute->is_taxonomy();   // true = global attribute (pa_*), false = custom
    $attribute->get_variation(); // true = used for variations
}

// Add global attribute to product
$attribute = new \WC_Product_Attribute();
$attribute->set_id( wc_attribute_taxonomy_id_by_name( 'pa_color' ) );
$attribute->set_name( 'pa_color' );
$attribute->set_options( [ $term_id_red, $term_id_blue ] );
$attribute->set_position( 0 );
$attribute->set_visible( true );
$attribute->set_variation( true );

$product->set_attributes( [ $attribute ] );
$product->save();
```
