# WooCommerce REST API

## Authentication

```bash
# Application Passwords (WP 5.6+, simplest)
curl https://example.com/wp-json/wc/v3/products \
  -u "consumer_key:consumer_secret"

# Query string (HTTPS only)
curl "https://example.com/wp-json/wc/v3/products?consumer_key=ck_xxx&consumer_secret=cs_xxx"
```

Generate keys: WooCommerce → Settings → Advanced → REST API → Add key.

## Core Endpoints

```
GET    /wc/v3/products
GET    /wc/v3/products/{id}
POST   /wc/v3/products
PUT    /wc/v3/products/{id}
DELETE /wc/v3/products/{id}?force=true

GET    /wc/v3/products/categories
GET    /wc/v3/products/attributes

GET    /wc/v3/orders
GET    /wc/v3/orders/{id}
POST   /wc/v3/orders
PUT    /wc/v3/orders/{id}           # update status, meta, etc.
DELETE /wc/v3/orders/{id}?force=true

GET    /wc/v3/customers
GET    /wc/v3/customers/{id}
POST   /wc/v3/customers

GET    /wc/v3/coupons
POST   /wc/v3/coupons

GET    /wc/v3/reports/sales
GET    /wc/v3/reports/top_sellers

GET    /wc/v3/shipping/zones
GET    /wc/v3/shipping/zones/{id}/methods

GET    /wc/v3/payment_gateways
PUT    /wc/v3/payment_gateways/{id}

GET    /wc/v3/system_status
```

## Common Query Params

```bash
# Pagination
?per_page=50&page=2

# Filter by status
?status=processing
?status[]=pending&status[]=on-hold

# Date range
?after=2026-01-01T00:00:00&before=2026-12-31T23:59:59

# Search
?search=keyword

# Order by
?orderby=date&order=desc
?orderby=price&order=asc

# Include/exclude specific IDs
?include=1,2,3
?exclude=10,11
```

## Batch Operations

```bash
curl -X POST https://example.com/wp-json/wc/v3/products/batch \
  -H "Content-Type: application/json" \
  -u "ck:cs" \
  -d '{
    "create": [
      { "name": "New Product", "regular_price": "9.99", "type": "simple" }
    ],
    "update": [
      { "id": 42, "regular_price": "19.99" }
    ],
    "delete": [99]
  }'
```

## Extending: Register Custom Endpoint

```php
add_action( 'rest_api_init', function() {
    register_rest_route( 'my-plugin/v1', '/products/(?P<id>\d+)/custom', [
        [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => 'my_plugin_get_product_custom',
            'permission_callback' => function() {
                return current_user_can( 'read' );
            },
            'args' => [
                'id' => [
                    'validate_callback' => fn( $v ) => is_numeric( $v ),
                    'sanitize_callback' => 'absint',
                ],
            ],
        ],
        [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => 'my_plugin_update_product_custom',
            'permission_callback' => function() {
                return current_user_can( 'edit_products' );
            },
        ],
    ] );
} );

function my_plugin_get_product_custom( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
    $product = wc_get_product( $request['id'] );
    if ( ! $product ) {
        return new \WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'my-plugin' ), [ 'status' => 404 ] );
    }
    return rest_ensure_response( [
        'id'           => $product->get_id(),
        'custom_field' => $product->get_meta( '_my_custom_field' ),
    ] );
}
```

## Add Custom Fields to Existing WC Endpoints

```php
// Add field to product response
add_filter( 'woocommerce_rest_prepare_product_object', function( \WP_REST_Response $response, \WC_Product $product, \WP_REST_Request $request ) {
    $response->data['my_custom_field'] = $product->get_meta( '_my_custom_field' );
    return $response;
}, 10, 3 );

// Accept custom field in create/update
add_action( 'woocommerce_rest_insert_product_object', function( \WC_Product $product, \WP_REST_Request $request, bool $creating ) {
    if ( isset( $request['my_custom_field'] ) ) {
        $product->update_meta_data( '_my_custom_field', sanitize_text_field( $request['my_custom_field'] ) );
        $product->save();
    }
}, 10, 3 );

// Register field in schema
add_filter( 'woocommerce_rest_product_schema', function( array $schema ) {
    $schema['properties']['my_custom_field'] = [
        'description' => __( 'My custom field.', 'my-plugin' ),
        'type'        => 'string',
        'context'     => [ 'view', 'edit' ],
    ];
    return $schema;
} );
```

## Add Custom Fields to Order Endpoints

```php
add_filter( 'woocommerce_rest_prepare_shop_order_object', function( \WP_REST_Response $response, \WC_Order $order, \WP_REST_Request $request ) {
    $response->data['my_order_meta'] = $order->get_meta( '_my_order_meta' );
    return $response;
}, 10, 3 );

add_action( 'woocommerce_rest_insert_shop_order_object', function( \WC_Order $order, \WP_REST_Request $request, bool $creating ) {
    if ( isset( $request['my_order_meta'] ) ) {
        $order->update_meta_data( '_my_order_meta', sanitize_text_field( $request['my_order_meta'] ) );
        $order->save();
    }
}, 10, 3 );
```

## WC REST API PHP Client

```bash
composer require automattic/woocommerce
```

```php
use Automattic\WooCommerce\Client;

$wc = new Client(
    'https://example.com',
    'consumer_key',
    'consumer_secret',
    [ 'version' => 'wc/v3' ]
);

// Read
$products = $wc->get( 'products', [ 'per_page' => 20, 'status' => 'publish' ] );

// Create
$product = $wc->post( 'products', [
    'name'          => 'Test Product',
    'type'          => 'simple',
    'regular_price' => '9.99',
    'status'        => 'publish',
] );

// Update
$wc->put( 'products/' . $product->id, [ 'regular_price' => '14.99' ] );

// Delete
$wc->delete( 'products/' . $product->id, [ 'force' => true ] );

// Batch
$wc->post( 'products/batch', [
    'create' => [ ... ],
    'update' => [ ... ],
    'delete' => [ 99 ],
] );
```
