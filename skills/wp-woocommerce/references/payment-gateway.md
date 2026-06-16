# WooCommerce Payment Gateway

## Minimal Gateway Scaffold

```php
class My_Plugin_Payment_Gateway extends \WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'my_plugin_gateway';
        $this->has_fields         = true;          // show checkout form
        $this->method_title       = __( 'My Gateway', 'my-plugin' );
        $this->method_description = __( 'Pay via My Gateway.', 'my-plugin' );
        $this->supports           = [
            'products',
            'refunds',
        ];

        // Load saved settings
        $this->init_form_fields();
        $this->init_settings();

        // Map settings to properties
        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled     = $this->get_option( 'enabled' );
        $this->testmode    = 'yes' === $this->get_option( 'testmode' );

        // Hooks
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
        add_action( 'woocommerce_api_' . $this->id, [ $this, 'webhook_handler' ] );
    }

    public function init_form_fields(): void {
        $this->form_fields = [
            'enabled'     => [
                'title'   => __( 'Enable/Disable', 'my-plugin' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable My Gateway', 'my-plugin' ),
                'default' => 'no',
            ],
            'title'       => [
                'title'   => __( 'Title', 'my-plugin' ),
                'type'    => 'text',
                'default' => __( 'My Gateway', 'my-plugin' ),
            ],
            'description' => [
                'title'   => __( 'Description', 'my-plugin' ),
                'type'    => 'textarea',
                'default' => '',
            ],
            'testmode'    => [
                'title'   => __( 'Test Mode', 'my-plugin' ),
                'type'    => 'checkbox',
                'default' => 'yes',
            ],
            'api_key'     => [
                'title' => __( 'API Key', 'my-plugin' ),
                'type'  => 'text',
            ],
            'secret_key'  => [
                'title' => __( 'Secret Key', 'my-plugin' ),
                'type'  => 'password',
            ],
        ];
    }

    /**
     * Render payment fields at checkout.
     */
    public function payment_fields(): void {
        if ( $this->description ) {
            echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
        }
        // Render hosted payment fields JS (Stripe Elements style)
        echo '<div id="my-gateway-card-element"></div>';
        echo '<div id="my-gateway-card-errors" role="alert"></div>';
    }

    /**
     * Validate fields before order creation.
     */
    public function validate_fields(): bool {
        if ( empty( $_POST['my_gateway_token'] ) ) {
            wc_add_notice( __( 'Payment error: missing token.', 'my-plugin' ), 'error' );
            return false;
        }
        return true;
    }

    /**
     * Process payment and return result.
     */
    public function process_payment( int $order_id ): array {
        $order = wc_get_order( $order_id );
        $token = sanitize_text_field( wp_unslash( $_POST['my_gateway_token'] ?? '' ) );

        $response = $this->api_charge( [
            'token'    => $token,
            'amount'   => $order->get_total(),
            'currency' => get_woocommerce_currency(),
            'order_id' => $order_id,
        ] );

        if ( is_wp_error( $response ) ) {
            wc_add_notice( $response->get_error_message(), 'error' );
            return [ 'result' => 'fail' ];
        }

        // Mark paid
        $order->payment_complete( $response['transaction_id'] );
        $order->add_order_note(
            sprintf(
                /* translators: %s: transaction ID */
                __( 'Payment complete. Transaction ID: %s', 'my-plugin' ),
                esc_html( $response['transaction_id'] )
            )
        );

        // Clear cart
        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
    }

    /**
     * Process refund.
     */
    public function process_refund( int $order_id, ?float $amount = null, string $reason = '' ): bool|\WP_Error {
        $order          = wc_get_order( $order_id );
        $transaction_id = $order->get_transaction_id();

        if ( ! $transaction_id ) {
            return new \WP_Error( 'no_transaction', __( 'No transaction ID found.', 'my-plugin' ) );
        }

        $response = $this->api_refund( $transaction_id, $amount, $reason );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $order->add_order_note(
            sprintf(
                /* translators: 1: refund amount, 2: refund ID */
                __( 'Refunded %1$s. Refund ID: %2$s', 'my-plugin' ),
                wc_price( $amount ),
                esc_html( $response['refund_id'] )
            )
        );

        return true;
    }

    /**
     * Handle webhook from payment provider.
     */
    public function webhook_handler(): void {
        $payload   = file_get_contents( 'php://input' );
        $signature = $_SERVER['HTTP_X_MY_SIGNATURE'] ?? '';

        if ( ! $this->verify_webhook_signature( $payload, $signature ) ) {
            status_header( 400 );
            exit;
        }

        $data = json_decode( $payload, true );

        if ( 'payment.succeeded' === ( $data['event'] ?? '' ) ) {
            $order_id = absint( $data['metadata']['order_id'] ?? 0 );
            $order    = wc_get_order( $order_id );
            if ( $order && ! $order->is_paid() ) {
                $order->payment_complete( $data['charge_id'] );
            }
        }

        status_header( 200 );
        exit;
    }

    private function api_charge( array $params ): array|\WP_Error {
        $response = wp_remote_post( 'https://api.myprovider.com/v1/charge', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_option( 'api_key' ),
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $params ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) return $response;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return new \WP_Error( 'api_error', $body['message'] ?? __( 'Unknown error.', 'my-plugin' ) );
        }

        return $body;
    }

    private function api_refund( string $transaction_id, ?float $amount, string $reason ): array|\WP_Error {
        return $this->api_charge( [ 'transaction_id' => $transaction_id, 'amount' => $amount, 'reason' => $reason ] );
    }

    private function verify_webhook_signature( string $payload, string $signature ): bool {
        $expected = hash_hmac( 'sha256', $payload, $this->get_option( 'secret_key' ) );
        return hash_equals( $expected, $signature );
    }
}

// Register gateway
add_filter( 'woocommerce_payment_gateways', function( array $gateways ) {
    $gateways[] = My_Plugin_Payment_Gateway::class;
    return $gateways;
} );
```

## Webhook URL

```
https://example.com/?wc-api=my_plugin_gateway
```

WC routes `?wc-api=<id>` to the `woocommerce_api_<id>` action.
