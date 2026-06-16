# WooCommerce Orders

## Get Orders (HPOS-safe)

```php
// Single order
$order = wc_get_order( $order_id );  // WC_Order|false

// Query orders (HPOS-safe — never use WP_Query for orders)
$orders = wc_get_orders( [
    'status'       => 'processing',                          // string or array
    'status'       => [ 'processing', 'on-hold' ],
    'customer_id'  => $user_id,
    'customer'     => 'email@example.com',                   // by email
    'limit'        => 20,
    'paged'        => 1,
    'orderby'      => 'date',
    'order'        => 'DESC',
    'date_created' => '>' . ( time() - WEEK_IN_SECONDS ),   // since 7 days ago
    'date_paid'    => '>=' . strtotime( '2026-01-01' ),
    'return'       => 'objects',                             // 'objects' or 'ids'
    'type'         => 'shop_order',                          // 'shop_order', 'shop_order_refund'
    'meta_query'   => [
        [ 'key' => '_my_plugin_field', 'value' => 'yes' ],
    ],
] );
```

## WC_Order Getters

```php
$order->get_id();
$order->get_order_number();          // formatted: '#1001'
$order->get_status();                // 'pending', 'processing', 'completed', etc.
$order->get_total();                 // '59.99' (string)
$order->get_subtotal();
$order->get_total_tax();
$order->get_shipping_total();
$order->get_discount_total();
$order->get_currency();              // 'USD'

// Dates (WC_DateTime objects)
$order->get_date_created();
$order->get_date_modified();
$order->get_date_paid();
$order->get_date_completed();

// Customer
$order->get_customer_id();           // 0 for guest
$order->get_billing_email();
$order->get_billing_first_name();
$order->get_billing_last_name();
$order->get_billing_phone();
$order->get_billing_address_1();
$order->get_billing_city();
$order->get_billing_state();
$order->get_billing_postcode();
$order->get_billing_country();
$order->get_formatted_billing_address();   // HTML-formatted

// Shipping
$order->get_shipping_first_name();
$order->get_shipping_address_1();
$order->get_shipping_city();
$order->get_shipping_postcode();
$order->get_shipping_country();
$order->get_formatted_shipping_address();

// Payment
$order->get_payment_method();        // 'stripe', 'paypal', etc.
$order->get_payment_method_title();  // human-readable
$order->get_transaction_id();

// Misc
$order->get_customer_note();
$order->get_customer_ip_address();
$order->get_customer_user_agent();
$order->get_created_via();           // 'checkout', 'admin', 'rest-api'
```

## Line Items

```php
// Get all items
foreach ( $order->get_items() as $item_id => $item ) {
    /** @var WC_Order_Item_Product $item */
    $item->get_name();
    $item->get_product_id();
    $item->get_variation_id();      // 0 for non-variation
    $item->get_quantity();
    $item->get_total();             // line total excl tax
    $item->get_total_tax();
    $item->get_subtotal();
    $item->get_product();           // WC_Product|false
    $item->get_meta( '_custom_key' );
}

// Get shipping items
foreach ( $order->get_items( 'shipping' ) as $item ) {
    /** @var WC_Order_Item_Shipping $item */
    $item->get_name();              // shipping method title
    $item->get_method_id();         // 'flat_rate'
    $item->get_instance_id();
    $item->get_total();
}

// Get fee items
foreach ( $order->get_items( 'fee' ) as $item ) {
    /** @var WC_Order_Item_Fee $item */
    $item->get_name();
    $item->get_amount();
    $item->get_tax_class();
}

// Get coupon items
foreach ( $order->get_items( 'coupon' ) as $item ) {
    /** @var WC_Order_Item_Coupon $item */
    $item->get_code();
    $item->get_discount();
}
```

## WC_Order Setters + Save

```php
$order = wc_get_order( $order_id );
$order->set_status( 'completed' );
$order->set_billing_email( 'new@example.com' );
$order->set_payment_method( 'stripe' );
$order->set_customer_note( 'Urgent order.' );
$order->update_meta_data( '_my_plugin_key', 'value' );
$order->save();
```

## Order Status

### Default statuses

| Status | Slug |
|---|---|
| Pending payment | `pending` |
| Processing | `processing` |
| On hold | `on-hold` |
| Completed | `completed` |
| Cancelled | `cancelled` |
| Refunded | `refunded` |
| Failed | `failed` |
| Checkout draft | `checkout-draft` |

### Transition status

```php
// Update and trigger hooks
$order->update_status( 'completed', 'Manually completed by plugin.', true );
// arg 3 = true → adds order note with the message

// Hook into status changes
add_action( 'woocommerce_order_status_processing_to_completed', function( int $order_id, \WC_Order $order ) {
    // Send custom email, sync external system, etc.
}, 10, 2 );

// Hook for any status change
add_action( 'woocommerce_order_status_changed', function( int $order_id, string $old_status, string $new_status, \WC_Order $order ) {
    error_log( "Order #{$order_id}: {$old_status} → {$new_status}" );
}, 10, 4 );
```

### Register custom status

```php
add_action( 'init', function() {
    register_post_status( 'wc-awaiting-pickup', [
        'label'                     => _x( 'Awaiting Pickup', 'Order status', 'my-plugin' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        /* translators: %s: number of orders */
        'label_count'               => _n_noop( 'Awaiting Pickup <span class="count">(%s)</span>', 'Awaiting Pickup <span class="count">(%s)</span>', 'my-plugin' ),
    ] );
} );

add_filter( 'wc_order_statuses', function( array $statuses ) {
    $statuses['wc-awaiting-pickup'] = _x( 'Awaiting Pickup', 'Order status', 'my-plugin' );
    return $statuses;
} );
```

## Order Notes

```php
// Add note visible to customer (appears in "My Account")
$order->add_order_note( __( 'Your order is being prepared.', 'my-plugin' ), true );

// Add internal note (admin only)
$order->add_order_note( __( 'Flagged for review.', 'my-plugin' ), false );

// Get all notes
$notes = wc_get_order_notes( [ 'order_id' => $order_id ] );
foreach ( $notes as $note ) {
    $note->id;
    $note->date_created;    // WC_DateTime
    $note->content;
    $note->customer_note;   // true = visible to customer
    $note->added_by;        // 'system', 'WooCommerce', or user display name
}
```

## Refunds

```php
// Create refund
$refund = wc_create_refund( [
    'amount'         => 10.00,
    'reason'         => 'Damaged item.',
    'order_id'       => $order_id,
    'line_items'     => [],       // empty = manual refund amount only
    'refund_payment' => true,     // true = also call gateway's process_refund()
    'restock_items'  => true,     // true = return to stock
] );

if ( is_wp_error( $refund ) ) {
    // Handle error
}

// Partial refund with specific line items
$line_items = [];
foreach ( $order->get_items() as $item_id => $item ) {
    $line_items[ $item_id ] = [
        'qty'          => 1,
        'refund_total' => wc_format_decimal( $item->get_total() / $item->get_quantity() ),
        'refund_tax'   => [],
    ];
}
$refund = wc_create_refund( [
    'amount'         => 15.00,
    'order_id'       => $order_id,
    'line_items'     => $line_items,
    'refund_payment' => true,
] );

// Get order refunds
$refunds = $order->get_refunds();
foreach ( $refunds as $refund ) {
    $refund->get_id();
    $refund->get_amount();
    $refund->get_reason();
    $refund->get_date_created();
}
$order->get_total_refunded();
```

## Admin Order List Column

```php
add_filter( 'manage_woocommerce_page_wc-orders_columns', function( array $columns ) {
    $columns['my_plugin_col'] = __( 'My Field', 'my-plugin' );
    return $columns;
} );

add_action( 'manage_woocommerce_page_wc-orders_custom_column', function( string $column, \WC_Order $order ) {
    if ( 'my_plugin_col' === $column ) {
        echo esc_html( $order->get_meta( '_my_plugin_field' ) ?: '—' );
    }
}, 10, 2 );
```
