# WordPress Plugin Test Patterns

## Hook and filter tests

```php
class Test_Hooks extends WP_UnitTestCase {

    // Test that an action fires
    public function test_action_fires_on_save() {
        $fired    = false;
        $received = null;

        add_action( 'my_plugin_after_save', function( $id ) use ( &$fired, &$received ) {
            $fired    = true;
            $received = $id;
        } );

        my_plugin_save_item( 42 );

        $this->assertTrue( $fired, 'my_plugin_after_save did not fire' );
        $this->assertSame( 42, $received );
    }

    // Test that a filter changes a value
    public function test_filter_modifies_title() {
        add_filter( 'the_title', 'my_plugin_prefix_title', 10, 2 );

        $post_id = self::factory()->post->create( [ 'post_title' => 'Hello' ] );
        $title   = get_the_title( $post_id );

        $this->assertStringStartsWith( '[My Plugin]', $title );

        remove_filter( 'the_title', 'my_plugin_prefix_title', 10 );
    }

    // Test priority order
    public function test_hook_fires_at_correct_priority() {
        $order = [];
        add_action( 'my_event', function() use ( &$order ) { $order[] = 'first'; },  5  );
        add_action( 'my_event', function() use ( &$order ) { $order[] = 'plugin'; }, 10 );
        add_action( 'my_event', function() use ( &$order ) { $order[] = 'last'; },   20 );

        do_action( 'my_event' );

        $this->assertSame( [ 'first', 'plugin', 'last' ], $order );
    }
}
```

## Options and settings tests

```php
class Test_Settings extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        delete_option( 'my_plugin_settings' );
    }

    public function test_default_settings_returned_when_option_missing() {
        $settings = my_plugin_get_settings();
        $this->assertSame( 'default', $settings['mode'] );
    }

    public function test_settings_saved_and_retrieved() {
        my_plugin_save_settings( [ 'mode' => 'advanced' ] );
        $settings = my_plugin_get_settings();
        $this->assertSame( 'advanced', $settings['mode'] );
    }

    public function test_invalid_setting_rejected() {
        $result = my_plugin_save_settings( [ 'mode' => '<script>evil</script>' ] );
        $this->assertWPError( $result );
    }
}
```

## Shortcode tests

```php
class Test_Shortcode extends WP_UnitTestCase {

    public function test_shortcode_registered() {
        $this->assertTrue( shortcode_exists( 'my_plugin' ) );
    }

    public function test_shortcode_output() {
        $output = do_shortcode( '[my_plugin type="list"]' );
        $this->assertStringContainsString( '<ul', $output );
        $this->assertStringNotContainsString( 'script', $output );
    }

    public function test_shortcode_with_invalid_attr() {
        $output = do_shortcode( '[my_plugin type="<invalid>"]' );
        $this->assertStringNotContainsString( '<invalid>', $output );
    }
}
```

## REST API tests

```php
class Test_REST_Endpoint extends WP_Test_REST_TestCase {

    protected $server;
    protected $admin_id;

    public function setUp(): void {
        parent::setUp();
        global $wp_rest_server;
        $this->server   = $wp_rest_server = new WP_REST_Server();
        $this->admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
        do_action( 'rest_api_init' );
    }

    public function test_endpoint_registered() {
        $routes = $this->server->get_routes();
        $this->assertArrayHasKey( '/my-plugin/v1/items', $routes );
    }

    public function test_get_items_requires_auth() {
        $request  = new WP_REST_Request( 'GET', '/my-plugin/v1/items' );
        $response = $this->server->dispatch( $request );
        $this->assertSame( 401, $response->get_status() );
    }

    public function test_get_items_as_admin() {
        wp_set_current_user( $this->admin_id );
        $request  = new WP_REST_Request( 'GET', '/my-plugin/v1/items' );
        $response = $this->server->dispatch( $request );
        $this->assertSame( 200, $response->get_status() );
        $this->assertIsArray( $response->get_data() );
    }
}
```

## Database tests

```php
class Test_Database extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        // Table created on plugin activation — ensure it exists
        my_plugin_create_tables();
    }

    public function test_record_inserted() {
        global $wpdb;
        $id = my_plugin_insert_record( [ 'title' => 'Test' ] );
        $this->assertIsInt( $id );
        $this->assertGreaterThan( 0, $id );

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}my_plugin_items WHERE id = %d",
            $id
        ) );
        $this->assertSame( 'Test', $row->title );
    }

    public function test_deleted_record_not_found() {
        $id = my_plugin_insert_record( [ 'title' => 'To Delete' ] );
        my_plugin_delete_record( $id );
        $result = my_plugin_get_record( $id );
        $this->assertNull( $result );
    }
}
```

## Capability and nonce tests

```php
class Test_Capabilities extends WP_UnitTestCase {

    public function test_subscriber_cannot_access_admin_action() {
        $sub = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $sub );

        $result = my_plugin_admin_action();

        $this->assertWPError( $result );
        $this->assertSame( 'insufficient_permissions', $result->get_error_code() );
    }

    public function test_missing_nonce_rejected() {
        $_POST = [ 'data' => 'value' ]; // no nonce

        $result = my_plugin_handle_form_submission();

        $this->assertWPError( $result );
    }
}
```

## Data provider pattern

```php
class Test_Sanitisation extends WP_UnitTestCase {

    /**
     * @dataProvider provide_unsafe_inputs
     */
    public function test_unsafe_input_sanitised( string $input, string $expected ) {
        $result = my_plugin_sanitise( $input );
        $this->assertSame( $expected, $result );
    }

    public function provide_unsafe_inputs(): array {
        return [
            'xss script tag'     => [ '<script>alert(1)</script>', '' ],
            'sql injection'      => [ "' OR '1'='1", "&#039; OR &#039;1&#039;=&#039;1" ],
            'html entities'      => [ '<b>Bold</b>', '&lt;b&gt;Bold&lt;/b&gt;' ],
            'normal string'      => [ 'Hello World', 'Hello World' ],
            'unicode'            => [ 'Héllo', 'Héllo' ],
        ];
    }
}
```

## Useful assertions

```php
$this->assertWPError( $result );
$this->assertNotWPError( $result );
$this->assertQueryTrue( 'is_single', 'is_singular' );
$this->go_to( get_permalink( $post_id ) );
$this->assertSame( get_current_blog_id(), $blog_id );

// Check option was set
$this->assertSame( 'expected', get_option( 'my_option' ) );

// Check transient
$this->assertNotFalse( get_transient( 'my_transient' ) );

// Check post meta
$this->assertSame( 'value', get_post_meta( $post_id, '_key', true ) );
```
