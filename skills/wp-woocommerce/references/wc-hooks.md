# WooCommerce Hooks Reference

Categorised hooks with signatures and minimum WC version. All hooks listed are available in WC 7.0+.

## Cart Hooks

```php
// Calculate fees/discounts
add_action( 'woocommerce_cart_calculate_fees', function( WC_Cart $cart ) {} );

// Modify item price display
add_filter( 'woocommerce_cart_item_price', function( string $price_html, array $cart_item, string $cart_item_key ): string {}, 10, 3 );

// Modify subtotal per item
add_filter( 'woocommerce_cart_item_subtotal', function( string $subtotal, array $cart_item, string $cart_item_key ): string {}, 10, 3 );

// Item quantity
add_filter( 'woocommerce_cart_item_quantity', function( string $product_quantity, string $cart_item_key, array $cart_item ): string {}, 10, 3 );

// After item added to cart
add_action( 'woocommerce_add_to_cart', function( string $cart_item_key, int $product_id, int $quantity, int $variation_id, array $variation, array $cart_item_data ) {}, 10, 6 );

// Validate before adding to cart
add_filter( 'woocommerce_add_to_cart_validation', function( bool $passed, int $product_id, int $quantity ): bool {}, 10, 3 );

// Cart contents
add_filter( 'woocommerce_get_cart_contents', function( array $cart_contents ): array {} );
```

## Checkout Hooks

```php
// Add/modify checkout fields
add_filter( 'woocommerce_checkout_fields', function( array $fields ): array {} );

// Validate checkout (show errors with wc_add_notice())
add_action( 'woocommerce_checkout_process', function() {} );

// Save custom field data to order
add_action( 'woocommerce_checkout_update_order_meta', function( int $order_id, array $posted_data ) {}, 10, 2 );

// Fires after order created at checkout
add_action( 'woocommerce_checkout_order_created', function( WC_Order $order ) {} );

// Before/after checkout form sections
add_action( 'woocommerce_checkout_before_customer_details', function() {} );
add_action( 'woocommerce_checkout_after_customer_details', function() {} );
add_action( 'woocommerce_review_order_before_submit', function() {} );
add_action( 'woocommerce_review_order_after_submit', function() {} );

// Thank you page
add_action( 'woocommerce_thankyou', function( int $order_id ) {} );
add_action( 'woocommerce_thankyou_{gateway_id}', function( int $order_id ) {} );
```

## Order Hooks

```php
// Order status changed
add_action( 'woocommerce_order_status_changed', function( int $order_id, string $from, string $to, WC_Order $order ) {}, 10, 4 );

// Specific status transitions
add_action( 'woocommerce_order_status_pending_to_processing', function( int $order_id, WC_Order $order ) {}, 10, 2 );
add_action( 'woocommerce_order_status_completed', function( int $order_id ) {} );
add_action( 'woocommerce_payment_complete', function( int $order_id ) {} );

// Register custom status
add_filter( 'wc_order_statuses', function( array $statuses ): array {
    $statuses['wc-awaiting-review'] = _x( 'Awaiting Review', 'Order status', 'my-plugin' );
    return $statuses;
} );
add_action( 'init', function() {
    register_post_status( 'wc-awaiting-review', [
        'label'                     => _x( 'Awaiting Review', 'Order status', 'my-plugin' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        /* translators: %s: number of orders */
        'label_count'               => _n_noop( 'Awaiting Review <span class="count">(%s)</span>', 'Awaiting Review <span class="count">(%s)</span>', 'my-plugin' ),
    ] );
} );

// Order list columns (classic orders table)
add_filter( 'manage_edit-shop_order_columns', function( array $columns ): array {} );
add_action( 'manage_shop_order_posts_custom_column', function( string $column, int $post_id ) {}, 10, 2 );

// HPOS order list columns
add_filter( 'woocommerce_shop_order_list_table_columns', function( array $columns ): array {} );
add_action( 'woocommerce_shop_order_list_table_custom_column', function( string $column, WC_Order $order ) {}, 10, 2 );
```

## Product Hooks

```php
// Product admin tabs
add_filter( 'woocommerce_product_data_tabs', function( array $tabs ): array {
    $tabs['my_tab'] = [
        'label'    => __( 'My Tab', 'my-plugin' ),
        'target'   => 'my_product_options',
        'class'    => [ 'show_if_simple', 'show_if_variable' ],
        'priority' => 60,
    ];
    return $tabs;
} );
add_action( 'woocommerce_product_data_panels', function() {
    echo '<div id="my_product_options" class="panel woocommerce_options_panel">';
    woocommerce_wp_text_input( [ 'id' => '_my_field', 'label' => __( 'My Field', 'my-plugin' ) ] );
    echo '</div>';
} );
add_action( 'woocommerce_process_product_meta', function( int $post_id ) {
    update_post_meta( $post_id, '_my_field', sanitize_text_field( wp_unslash( $_POST['_my_field'] ?? '' ) ) );
} );

// Product query
add_action( 'woocommerce_product_query', function( WP_Query $q ) {} );

// Product loop output
add_action( 'woocommerce_before_shop_loop_item_title', function() {}, 10 );
add_action( 'woocommerce_after_shop_loop_item', function() {}, 10 );

// Single product page
add_action( 'woocommerce_before_single_product_summary', function() {}, 10 );
add_action( 'woocommerce_single_product_summary', function() {}, 10 );
add_action( 'woocommerce_after_single_product_summary', function() {}, 10 );
```

## Email Hooks

```php
// Add custom email
add_filter( 'woocommerce_email_classes', function( array $emails ): array {
    $emails['My_Plugin_Email'] = new My_Plugin_Email();
    return $emails;
} );

// Modify email content
add_action( 'woocommerce_email_before_order_table', function( WC_Order $order, bool $sent_to_admin, bool $plain_text, WC_Email $email ) {}, 10, 4 );
add_action( 'woocommerce_email_after_order_table', function( WC_Order $order, bool $sent_to_admin, bool $plain_text, WC_Email $email ) {}, 10, 4 );
```

## REST API Hooks

```php
// Extend product schema
add_filter( 'woocommerce_rest_product_schema', function( array $schema ): array {} );

// Modify REST response
add_filter( 'woocommerce_rest_prepare_product_object', function( WP_REST_Response $response, WC_Product $product, WP_REST_Request $request ): WP_REST_Response {}, 10, 3 );
add_filter( 'woocommerce_rest_prepare_shop_order_object', function( WP_REST_Response $response, WC_Order $order, WP_REST_Request $request ): WP_REST_Response {}, 10, 3 );

// Modify data before insert
add_filter( 'woocommerce_rest_pre_insert_product_object', function( WC_Product $product, WP_REST_Request $request ): WC_Product {}, 10, 2 );
```
