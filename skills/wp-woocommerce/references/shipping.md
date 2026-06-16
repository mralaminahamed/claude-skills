# WooCommerce Shipping

## Custom Shipping Method

```php
class My_Plugin_Shipping_Method extends \WC_Shipping_Method {

    public function __construct( int $instance_id = 0 ) {
        $this->id                 = 'my_plugin_shipping';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'My Shipping Method', 'my-plugin' );
        $this->method_description = __( 'Description shown in admin.', 'my-plugin' );
        $this->supports           = [
            'shipping-zones',           // can be added to zones
            'instance-settings',        // per-zone settings
            'instance-settings-modal',  // settings open in a modal
        ];
        $this->title = $this->get_option( 'title', __( 'My Shipping', 'my-plugin' ) );

        $this->init();
    }

    public function init(): void {
        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields(): void {
        $this->instance_form_fields = [
            'title' => [
                'title'   => __( 'Method Title', 'my-plugin' ),
                'type'    => 'text',
                'default' => __( 'My Shipping', 'my-plugin' ),
            ],
            'cost'  => [
                'title'       => __( 'Cost', 'my-plugin' ),
                'type'        => 'price',
                'placeholder' => '0.00',
                'default'     => '5.00',
            ],
            'free_above' => [
                'title'       => __( 'Free shipping above', 'my-plugin' ),
                'type'        => 'price',
                'default'     => '',
                'description' => __( 'Leave empty to disable.', 'my-plugin' ),
            ],
        ];
    }

    public function calculate_shipping( array $package = [] ): void {
        $free_above = (float) $this->get_option( 'free_above', 0 );
        $cost       = (float) $this->get_option( 'cost', 5 );

        // Check cart total for free shipping threshold
        $cart_total = 0;
        foreach ( $package['contents'] as $item ) {
            $cart_total += (float) $item['line_total'];
        }

        if ( $free_above > 0 && $cart_total >= $free_above ) {
            $cost = 0;
        }

        $this->add_rate( [
            'id'       => $this->get_rate_id(),  // unique rate ID
            'label'    => $this->title,
            'cost'     => $cost,
            'taxes'    => '',        // '' = auto-calculate; false = no tax; array = pre-calculated
            'calc_tax' => 'per_order',
            'meta_data' => [
                'my_plugin_estimated_delivery' => '3-5 business days',
            ],
        ] );
    }

    public function is_available( array $package ): bool {
        // Restrict to specific countries
        $allowed = [ 'US', 'CA', 'GB' ];
        $country = $package['destination']['country'] ?? '';
        return in_array( $country, $allowed, true ) && parent::is_available( $package );
    }
}

// Register method
add_filter( 'woocommerce_shipping_methods', function( array $methods ) {
    $methods['my_plugin_shipping'] = My_Plugin_Shipping_Method::class;
    return $methods;
} );
```

## Package / Rate Filters

```php
// Filter available rates for a package (e.g. remove methods conditionally)
add_filter( 'woocommerce_package_rates', function( array $rates, array $package ) {
    // Remove flat_rate if cart contains a heavy item
    foreach ( $package['contents'] as $item ) {
        if ( (float) ( $item['data']->get_weight() ?? 0 ) > 50 ) {
            unset( $rates['flat_rate:1'] ); // rate key = method_id:instance_id
            break;
        }
    }
    return $rates;
}, 10, 2 );

// Add surcharge to all rates
add_filter( 'woocommerce_package_rates', function( array $rates, array $package ) {
    if ( WC()->cart->get_cart_contents_count() > 10 ) {
        foreach ( $rates as $key => $rate ) {
            $rates[ $key ]->cost += 5;
        }
    }
    return $rates;
}, 10, 2 );
```

## WC_Shipping_Rate

```php
/** @var WC_Shipping_Rate $rate */
$rate->get_id();            // 'flat_rate:1'
$rate->get_label();         // 'Flat Rate'
$rate->get_cost();          // '5.00'
$rate->get_taxes();         // array of tax amounts
$rate->get_shipping_tax();  // total tax
$rate->get_method_id();     // 'flat_rate'
$rate->get_instance_id();   // 1
$rate->get_meta_data();     // custom key/value data
```

## Shipping Zones (PHP API)

```php
// Get all zones
$zones = \WC_Shipping_Zones::get_zones();

// Get a specific zone
$zone = new \WC_Shipping_Zone( $zone_id );
$zone->get_zone_name();
$zone->get_shipping_methods();         // array of WC_Shipping_Method instances

// Get zone for a destination
$zone = \WC_Shipping_Zones::get_zone_matching_package( $package );

// Add a method to a zone programmatically
$zone = new \WC_Shipping_Zone( $zone_id );
$instance_id = $zone->add_shipping_method( 'flat_rate' );
$method = $zone->get_shipping_methods()[ $instance_id ] ?? null;
if ( $method ) {
    $method->set_option( 'cost', '9.99' );
}
```

## Packages

WC splits cart into packages (usually one, but multiple if plugin splits them):

```php
// Get current packages
$packages = WC()->shipping()->get_packages();

foreach ( $packages as $i => $package ) {
    $package['contents'];           // cart items in this package
    $package['contents_cost'];      // total cost of contents
    $package['applied_coupons'];
    $package['destination'];        // [ 'country', 'state', 'postcode', 'city', 'address', 'address_2' ]
    $package['rates'];              // available WC_Shipping_Rate instances for this package
}
```

Split cart into multiple packages:

```php
add_filter( 'woocommerce_cart_shipping_packages', function( array $packages ) {
    $regular = [];
    $bulky   = [];

    foreach ( WC()->cart->get_cart() as $key => $item ) {
        if ( (float) ( $item['data']->get_weight() ?? 0 ) > 20 ) {
            $bulky[ $key ] = $item;
        } else {
            $regular[ $key ] = $item;
        }
    }

    $result = [];
    if ( $regular ) {
        $result[] = array_merge( $packages[0], [ 'contents' => $regular, 'contents_cost' => array_sum( array_column( $regular, 'line_total' ) ) ] );
    }
    if ( $bulky ) {
        $result[] = array_merge( $packages[0], [ 'contents' => $bulky,   'contents_cost' => array_sum( array_column( $bulky,   'line_total' ) ) ] );
    }
    return $result ?: $packages;
} );
```

## Local Pickup (WC built-in, WC 7.0+)

```php
// Check if customer selected local pickup
add_action( 'woocommerce_checkout_order_processed', function( int $order_id, array $posted, \WC_Order $order ) {
    $chosen = WC()->session->get( 'chosen_shipping_methods', [] );
    $is_pickup = ! empty( array_filter( $chosen, fn( $method ) => str_starts_with( $method, 'pickup_location' ) ) );
    if ( $is_pickup ) {
        $order->update_meta_data( '_my_plugin_local_pickup', true );
        $order->save();
    }
}, 10, 3 );
```

## Trigger Shipping Recalculation

```php
// Force WC to recalculate shipping rates (clears transient cache)
WC()->cart->calculate_shipping();

// Or from JS (triggers cart update)
// jQuery( document.body ).trigger( 'update_checkout' );  // classic checkout
// jQuery( document.body ).trigger( 'wc_update_cart' );   // cart page
```
