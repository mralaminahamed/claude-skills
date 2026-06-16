# WooCommerce Payment Methods

## Classic vs Block-Based Registration

| Context | API |
|---|---|
| Shortcode cart/checkout | `WC_Payment_Gateway` class (see `payment-gateway.md`) |
| Block cart/checkout | `registerPaymentMethod()` in JS |
| Express checkout (Apple Pay, Google Pay) | `registerExpressPaymentMethod()` in JS |

A gateway supporting both must implement the classic PHP class **and** register the block integration.

## Register Payment Method in Blocks (JS)

```js
// src/payment-method/index.js
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const settings = window.wc?.wcSettings?.getSetting?.( 'my_gateway_data', {} );
const label    = decodeEntities( settings.title ?? __( 'My Gateway', 'my-plugin' ) );

const MyPaymentMethodLabel = ( { components } ) => {
    const { PaymentMethodLabel } = components;
    return <PaymentMethodLabel text={ label } />;
};

const MyPaymentMethodContent = ( { eventRegistration, emitResponse } ) => {
    const { onPaymentSetup } = eventRegistration;

    useEffect( () => {
        const unsubscribe = onPaymentSetup( async () => {
            // Validate and collect payment data before order is placed
            const token = document.getElementById( 'my-gateway-token' )?.value;
            if ( ! token ) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: __( 'Please enter your payment details.', 'my-plugin' ),
                };
            }
            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: { my_gateway_token: token },
                },
            };
        } );
        return unsubscribe;
    }, [ onPaymentSetup ] );

    return (
        <div>
            <input type="hidden" id="my-gateway-token" name="my_gateway_token" />
            { decodeEntities( settings.description ?? '' ) }
        </div>
    );
};

registerPaymentMethod( {
    name:              'my_plugin_gateway',   // must match $this->id in PHP class
    label:             <MyPaymentMethodLabel />,
    content:           <MyPaymentMethodContent />,
    edit:              <MyPaymentMethodContent />,    // block editor preview
    canMakePayment:    () => true,
    ariaLabel:         label,
    supports: {
        features: settings.supports ?? [],
    },
} );
```

## Pass Settings from PHP to JS

```php
add_action( 'woocommerce_blocks_payment_method_type_registration', function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry ) {
    $registry->register( new My_Plugin_Blocks_Payment_Method() );
} );

class My_Plugin_Blocks_Payment_Method extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {

    protected $name = 'my_plugin_gateway'; // must match gateway id

    public function initialize(): void {
        $this->settings = get_option( 'woocommerce_my_plugin_gateway_settings', [] );
    }

    public function is_active(): bool {
        return filter_var( $this->settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );
    }

    public function get_payment_method_script_handles(): array {
        $asset = require plugin_dir_path( __FILE__ ) . 'build/payment-method.asset.php';
        wp_register_script(
            'my-plugin-payment-method',
            plugins_url( 'build/payment-method.js', __FILE__ ),
            $asset['dependencies'],
            $asset['version'],
            true
        );
        return [ 'my-plugin-payment-method' ];
    }

    public function get_payment_method_data(): array {
        return [
            'title'       => $this->settings['title'] ?? '',
            'description' => $this->settings['description'] ?? '',
            'public_key'  => $this->settings['public_key'] ?? '',
            'supports'    => $this->get_supported_features(),
        ];
    }
}
```

## Express Payment Method (Apple Pay / Google Pay)

```js
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';

registerExpressPaymentMethod( {
    name: 'my_plugin_express',

    content: <MyExpressButton />,
    edit:    <MyExpressButton />,

    canMakePayment: async () => {
        // Check if browser supports the method (e.g. PaymentRequest API)
        if ( ! window.PaymentRequest ) return false;
        const pr = new PaymentRequest( [ { supportedMethods: 'https://apple.com/apple-pay' } ], { total: { label: 'Test', amount: { currency: 'USD', value: '0' } } } );
        return await pr.canMakePayment();
    },

    paymentMethodId: 'my_plugin_gateway', // parent gateway id

    supports: {
        features: [ 'products', 'refunds' ],
    },
} );
```

## Process Block Payment in PHP

Block checkout posts payment method data to the Store API — the `process_payment()` method on the gateway still receives and processes it:

```php
class My_Plugin_Payment_Gateway extends \WC_Payment_Gateway {

    public function process_payment( int $order_id ): array {
        $order = wc_get_order( $order_id );

        // Data submitted via blocks arrives in $_POST just like classic checkout
        $token = sanitize_text_field( wp_unslash( $_POST['my_gateway_token'] ?? '' ) );

        // Or retrieve from order meta if you stored it during woocommerce_rest_checkout_process_payment_with_context
        if ( ! $token ) {
            $token = $order->get_meta( '_my_gateway_token' );
        }

        // ... charge and return result
        return [ 'result' => 'success', 'redirect' => $this->get_return_url( $order ) ];
    }
}
```

For block context, hook into the Store API payment action:

```php
add_action( 'woocommerce_rest_checkout_process_payment_with_context', function( \Automattic\WooCommerce\StoreApi\Routes\V1\Checkout $context ) {
    if ( 'my_plugin_gateway' !== $context->payment_method ) return;

    $token = sanitize_text_field( $context->payment_data['my_gateway_token'] ?? '' );
    if ( $token ) {
        $context->order->update_meta_data( '_my_gateway_token', $token );
        $context->order->save();
    }
} );
```

## Supported Features List

```php
$this->supports = [
    'products',
    'refunds',
    'subscriptions',                   // WC Subscriptions
    'subscription_cancellation',
    'subscription_suspension',
    'subscription_reactivation',
    'subscription_amount_changes',
    'subscription_date_changes',
    'multiple_subscriptions',
    'pre-orders',                      // WC Pre-Orders
    'tokenization',                    // saved payment methods
    'add_payment_method',
];
```
