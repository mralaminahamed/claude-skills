# WooCommerce: Coupons, Tax & Webhooks

## Coupons

### Create coupon programmatically

```php
$coupon = new \WC_Coupon();
$coupon->set_code( 'SUMMER30' );
$coupon->set_description( 'Summer sale 30% off.' );
$coupon->set_discount_type( 'percent' );        // 'percent' | 'fixed_cart' | 'fixed_product'
$coupon->set_amount( 30 );                       // 30%
$coupon->set_individual_use( true );             // can't combine with other coupons
$coupon->set_usage_limit( 100 );                 // total usage limit
$coupon->set_usage_limit_per_user( 1 );          // per customer
$coupon->set_minimum_amount( '50.00' );          // minimum cart value
$coupon->set_maximum_amount( '500.00' );
$coupon->set_date_expires( '2026-12-31' );       // WC_DateTime or timestamp or string
$coupon->set_free_shipping( false );
$coupon->set_product_ids( [ 42, 99 ] );          // restrict to these products
$coupon->set_excluded_product_ids( [ 10 ] );
$coupon->set_product_categories( [ $cat_id ] );
$coupon->set_excluded_product_categories( [] );
$coupon->set_email_restrictions( [ 'vip@example.com' ] );
$coupon->save();

$coupon_id = $coupon->get_id();
```

### Get / check coupon

```php
$coupon = new \WC_Coupon( 'SUMMER30' );  // by code or ID
$coupon->is_valid();                      // bool — runs all validation checks
$coupon->get_code();
$coupon->get_discount_type();
$coupon->get_amount();
$coupon->get_usage_count();
$coupon->get_usage_limit();
$coupon->get_date_expires();              // WC_DateTime|null
```

### Apply coupon programmatically

```php
// To cart
$result = WC()->cart->apply_coupon( 'SUMMER30' );
// Returns true on success, adds wc_add_notice() on failure

// Check coupon applied
WC()->cart->has_discount( 'SUMMER30' );  // bool

// Remove coupon
WC()->cart->remove_coupon( 'SUMMER30' );
```

### Hook: validate custom coupon rules

```php
add_filter( 'woocommerce_coupon_is_valid', function( bool $valid, \WC_Coupon $coupon, \WC_Discounts $discounts ) {
    if ( 'SUMMER30' === $coupon->get_code() ) {
        // Only valid on weekends
        $day = (int) gmdate( 'N' );
        if ( $day < 6 ) {
            throw new \Exception( __( 'This coupon is only valid on weekends.', 'my-plugin' ) );
        }
    }
    return $valid;
}, 10, 3 );
```

---

## Tax

### Tax class

```php
// Get all tax classes (slugs)
$classes = \WC_Tax::get_tax_classes();  // [ '', 'reduced-rate', 'zero-rate' ]

// Get rates for a class
$rates = \WC_Tax::get_rates( '' );       // standard rate
$rates = \WC_Tax::get_rates( 'reduced-rate' );

// Calculate tax
$taxes = \WC_Tax::calc_tax( 100.00, $rates, false );  // false = price excl tax
$taxes = \WC_Tax::calc_tax( 110.00, $rates, true );   // true = price incl tax
$tax_total = \WC_Tax::get_tax_total( $taxes );
```

### Set product tax class

```php
$product = wc_get_product( $id );
$product->set_tax_class( 'reduced-rate' );  // '' = standard, 'zero-rate', 'reduced-rate'
$product->set_tax_status( 'taxable' );       // 'taxable' | 'shipping' | 'none'
$product->save();
```

### Modify tax rates on the fly

```php
// Filter rates for specific conditions
add_filter( 'woocommerce_tax_rate_location', function( array $matched_tax_rates, string $tax_class ) {
    // Override rates based on custom logic
    return $matched_tax_rates;
}, 10, 2 );

// Add tax exempt for specific customers
add_filter( 'woocommerce_product_is_taxable', function( bool $taxable, \WC_Product $product ) {
    if ( WC()->customer && WC()->customer->get_meta( 'tax_exempt' ) ) {
        return false;
    }
    return $taxable;
}, 10, 2 );
```

---

## Webhooks

### Register webhook programmatically

```php
$webhook = new \WC_Webhook();
$webhook->set_name( 'My Plugin: Order Created' );
$webhook->set_user_id( get_current_user_id() );
$webhook->set_topic( 'order.created' );               // see topics below
$webhook->set_delivery_url( 'https://my-endpoint.example.com/wc-webhook' );
$webhook->set_secret( wp_generate_password( 40, false ) );
$webhook->set_status( 'active' );
$webhook->save();
```

### Available topics

```
order.created     order.updated     order.deleted     order.restored
product.created   product.updated   product.deleted   product.restored
customer.created  customer.updated  customer.deleted  customer.restored
coupon.created    coupon.updated    coupon.deleted    coupon.restored
```

Custom topic:
```php
add_filter( 'woocommerce_webhook_topics', function( array $topics ) {
    $topics['action.my_plugin_event'] = __( 'My Plugin Event', 'my-plugin' );
    return $topics;
} );

// Trigger it
do_action( 'woocommerce_webhook_dispatch', 'action.my_plugin_event', [ 'data' => $payload ] );
```

### Receive and verify webhook

```php
// Your endpoint (registered via REST or separate PHP file)
add_action( 'rest_api_init', function() {
    register_rest_route( 'my-plugin/v1', '/wc-webhook', [
        'methods'             => 'POST',
        'callback'            => 'my_plugin_handle_wc_webhook',
        'permission_callback' => '__return_true',
    ] );
} );

function my_plugin_handle_wc_webhook( \WP_REST_Request $request ): \WP_REST_Response {
    $secret  = 'your-webhook-secret';
    $payload = $request->get_body();

    // Verify HMAC-SHA256 signature
    $expected  = base64_encode( hash_hmac( 'sha256', $payload, $secret, true ) );
    $signature = $request->get_header( 'x-wc-webhook-signature' );

    if ( ! hash_equals( $expected, (string) $signature ) ) {
        return new \WP_REST_Response( 'Unauthorized', 401 );
    }

    $data  = $request->get_json_params();
    $topic = $request->get_header( 'x-wc-webhook-topic' );  // 'order.created'

    if ( 'order.created' === $topic ) {
        $order_id = absint( $data['id'] ?? 0 );
        // Handle new order
    }

    return new \WP_REST_Response( 'OK', 200 );
}
```

### Webhook headers sent by WC

```
X-WC-Webhook-Source:     https://your-store.com/
X-WC-Webhook-Topic:      order.created
X-WC-Webhook-Resource:   order
X-WC-Webhook-Event:      created
X-WC-Webhook-Signature:  base64( HMAC-SHA256( body, secret ) )
X-WC-Webhook-ID:         1
X-WC-Webhook-Delivery-ID: unique-uuid
Content-Type:            application/json
```

### Manage webhooks

```php
// Get all webhooks
$data_store = \WC_Data_Store::load( 'webhook' );
$webhook_ids = $data_store->search_webhooks( [ 'status' => 'active', 'limit' => -1 ] );

// Get single webhook
$webhook = wc_get_webhook( $webhook_id );
$webhook->get_delivery_url();
$webhook->get_topic();
$webhook->get_status();       // 'active', 'paused', 'disabled'

// Pause
$webhook->set_status( 'paused' );
$webhook->save();

// Delete
$webhook->delete( true );     // true = force delete

// Manually trigger
$webhook->deliver( $data_array );
```
