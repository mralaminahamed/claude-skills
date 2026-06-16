# WooCommerce Block Cart & Checkout

## Architecture Overview

```
Cart Block          → uses Cart store (@woocommerce/block-data)
Checkout Block      → uses Checkout store + Payment store
                   ↓
Inner Blocks (slots that accept extensions)
  - woocommerce/checkout-fields-block
  - woocommerce/checkout-billing-address-block
  - woocommerce/checkout-shipping-address-block
  - woocommerce/checkout-payment-block       ← payment methods render here
  - woocommerce/checkout-order-summary-block
```

## Install dependencies

```bash
npm install @woocommerce/blocks-registry @woocommerce/settings @woocommerce/block-data @wordpress/element @wordpress/i18n
```

## Extend Checkout with SlotFills

SlotFills are the official extension API — inject content into predefined slots.

```js
// src/checkout-extension/index.js
import { registerPlugin } from '@wordpress/plugins';
import {
    ExperimentalOrderMeta,
    ExperimentalOrderShippingPackages,
    ExperimentalDiscountsMeta,
} from '@woocommerce/blocks-checkout';
import { __ } from '@wordpress/i18n';

const MyCheckoutExtension = () => (
    <ExperimentalOrderMeta>
        <div className="my-plugin-checkout-extra">
            <p>{ __( 'Delivery note:', 'my-plugin' ) }</p>
            <textarea name="my_delivery_note" rows="2" />
        </div>
    </ExperimentalOrderMeta>
);

registerPlugin( 'my-plugin-checkout', {
    render: MyCheckoutExtension,
    scope: 'woocommerce-checkout',
} );
```

### Available SlotFill components

```js
import {
    ExperimentalOrderMeta,              // below order summary
    ExperimentalOrderShippingPackages,  // below shipping methods
    ExperimentalDiscountsMeta,          // below coupon field
    ExperimentalOrderLocalPickupPackages,
    ExperimentalPaymentGatewayList,
    TotalsMeta,
} from '@woocommerce/blocks-checkout';
```

## Checkout Filters

Modify data flowing through the checkout blocks:

```js
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';

registerCheckoutFilters( 'my-plugin', {
    // Modify cart item name displayed in checkout
    itemName: ( value, extensions, args ) => {
        if ( args?.cartItem?.name?.includes( 'Special' ) ) {
            return value + ' ★';
        }
        return value;
    },

    // Modify subtotal price format
    subtotalPriceFormat: ( value, extensions, args ) => value,

    // Add/modify coupon messages
    couponName: ( value, extensions, args ) => value,

    // Modify total label
    totalLabel: ( value, extensions, args ) => value,

    // Modify shipping rate label
    shippingRateLabel: ( value, extensions, args ) => {
        if ( args?.shippingRate?.rate_id?.includes( 'my_method' ) ) {
            return value + ' (eco)';
        }
        return value;
    },
} );
```

## Save Extension Data (PHP + JS)

**JS — push data to the checkout store:**

```js
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';

// Call on change of a field your extension adds
const saveDeliveryNote = ( note ) => {
    extensionCartUpdate( {
        namespace: 'my-plugin',
        data: { delivery_note: note },
    } );
};
```

**PHP — receive and validate data:**

```php
add_action( 'woocommerce_blocks_loaded', function() {
    if ( ! class_exists( '\Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute' ) ) return;

    woocommerce_store_api_register_update_callback( [
        'namespace' => 'my-plugin',
        'callback'  => function( array $data ) {
            $note = sanitize_textarea_field( $data['delivery_note'] ?? '' );
            WC()->session->set( 'my_plugin_delivery_note', $note );
        },
    ] );
} );
```

**PHP — persist to order on checkout:**

```php
add_action( 'woocommerce_checkout_order_created', function( \WC_Order $order ) {
    $note = WC()->session->get( 'my_plugin_delivery_note', '' );
    if ( $note ) {
        $order->update_meta_data( '_my_plugin_delivery_note', $note );
        $order->save();
    }
} );
```

## Register Custom Endpoint Data (Store API)

Expose plugin data in the Store API `/wc/store/v1/cart` response:

```php
add_action( 'woocommerce_blocks_loaded', function() {
    if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) return;

    woocommerce_store_api_register_endpoint_data( [
        'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
        'namespace'       => 'my-plugin',
        'data_callback'   => function() {
            return [
                'promo_active' => (bool) get_option( 'my_plugin_promo_active', false ),
            ];
        },
        'schema_callback' => function() {
            return [
                'promo_active' => [
                    'description' => 'Whether the promo is currently active.',
                    'type'        => 'boolean',
                    'readonly'    => true,
                ],
            ];
        },
        'schema_type'     => ARRAY_A,
    ] );
} );
```

Access in JS:

```js
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';

const { cartData } = useSelect( ( select ) => ( {
    cartData: select( CART_STORE_KEY ).getCartData(),
} ) );

const promoActive = cartData?.extensions?.['my-plugin']?.promo_active;
```

## Enqueue Block Scripts

```php
add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', function() {
    $asset = require plugin_dir_path( __FILE__ ) . 'build/checkout-extension.asset.php';
    wp_enqueue_script(
        'my-plugin-checkout',
        plugins_url( 'build/checkout-extension.js', __FILE__ ),
        $asset['dependencies'],
        $asset['version'],
        true
    );
    wp_set_script_translations( 'my-plugin-checkout', 'my-plugin', plugin_dir_path( __FILE__ ) . 'languages' );
} );

add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', function() {
    $asset = require plugin_dir_path( __FILE__ ) . 'build/cart-extension.asset.php';
    wp_enqueue_script(
        'my-plugin-cart',
        plugins_url( 'build/cart-extension.js', __FILE__ ),
        $asset['dependencies'],
        $asset['version'],
        true
    );
} );
```

## Validate Checkout Data (PHP)

```php
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

add_action( 'woocommerce_store_api_checkout_order_processed', function( \WC_Order $order ) {
    $note = $order->get_meta( '_my_plugin_delivery_note' );
    if ( strlen( $note ) > 500 ) {
        throw new RouteException(
            'my_plugin_note_too_long',
            __( 'Delivery note must be under 500 characters.', 'my-plugin' ),
            400
        );
    }
} );
```
