# HPOS Compatibility Checklist

High-Performance Order Storage (HPOS) moves orders from `wp_posts`/`wp_postmeta` to dedicated order tables. Available in WC 7.1+, stable in 8.2+.

## Declare Compatibility

Required for ALL WooCommerce extensions. Without it, the plugin is disabled when HPOS is active.

```php
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,   // path to main plugin file
            true        // true = compatible, false = incompatible (disables in HPOS mode)
        );
        // Also declare blocks compatibility if applicable
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
} );
```

## Pattern Migration Map

| ❌ Incompatible (posts table) | ✅ HPOS-compatible |
|---|---|
| `get_post_meta( $id, '_key', true )` | `$order->get_meta( '_key' )` |
| `update_post_meta( $id, '_key', $val )` | `$order->update_meta_data( '_key', $val ); $order->save();` |
| `delete_post_meta( $id, '_key' )` | `$order->delete_meta_data( '_key' ); $order->save();` |
| `get_post( $order_id )` for order data | `wc_get_order( $order_id )` |
| `WP_Query( ['post_type' => 'shop_order'] )` | `wc_get_orders( [...] )` |
| `$wpdb->query` on `{prefix}posts` for orders | `wc_get_orders()` + WC_Order methods |
| `get_posts( ['post_type' => 'shop_order'] )` | `wc_get_orders( ['limit' => -1] )` |

## wc_get_orders() Reference

```php
$orders = wc_get_orders( [
    'limit'        => 20,
    'offset'       => 0,
    'orderby'      => 'date',
    'order'        => 'DESC',
    'status'       => [ 'wc-processing', 'wc-completed' ],
    'customer_id'  => $user_id,
    'date_created' => '>' . ( time() - DAY_IN_SECONDS * 30 ), // last 30 days
    'return'       => 'ids',    // or 'objects' (default)
    'meta_query'   => [         // custom meta still works
        [ 'key' => '_my_field', 'value' => 'target', 'compare' => '=' ],
    ],
] );
```

## WC_Order Getter/Setter Reference

```php
$order = wc_get_order( $order_id );

// Read
$order->get_id();
$order->get_status();
$order->get_total();
$order->get_subtotal();
$order->get_billing_email();
$order->get_billing_first_name();
$order->get_billing_address_1();
$order->get_shipping_address_1();
$order->get_customer_id();
$order->get_date_created();         // WC_DateTime
$order->get_date_modified();
$order->get_payment_method();
$order->get_transaction_id();
$order->get_items();                // array of WC_Order_Item
$order->get_meta( '_key', true );   // single value; false = array of all

// Write
$order->set_status( 'processing' );
$order->set_billing_email( $email );
$order->update_meta_data( '_key', $value );
$order->delete_meta_data( '_key' );
$order->add_order_note( $note, $is_customer_note = false );
$order->save();                     // always call save() after writing

// Payment
$order->payment_complete( $transaction_id );
$order->update_status( 'on-hold', 'Awaiting payment confirmation.' );
```

## Grep Commands for Audit

```bash
# Find incompatible patterns in your plugin
grep -rn "get_post_meta\|update_post_meta\|delete_post_meta" includes/ src/ \
  | grep -v vendor | grep -v "\.pot"

grep -rn "'post_type'.*shop_order\|post_type.*=.*shop_order" includes/ src/

grep -rn "WP_Query" includes/ src/ | grep -v vendor

grep -rn "posts_to_orders\|wc_postmeta" includes/ src/
```

## Testing HPOS Compatibility

1. Enable HPOS in **WooCommerce → Settings → Advanced → Features → Order Storage**
2. Run your plugin's full test suite
3. Test create order, update meta, query orders, REST API
4. Check **WooCommerce → Status → Tools → Verify Order Data** for inconsistencies
