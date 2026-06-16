# WP_Mock Unit Testing Patterns

WP_Mock lets you unit-test code that calls WP functions without loading WordPress.

## Setup

```bash
composer require --dev 10up/wp_mock mockery/mockery
```

```php
// tests/bootstrap.php (for unit tests only — no WP loaded)
<?php
require_once __DIR__ . '/../vendor/autoload.php';

WP_Mock::bootstrap();
```

```xml
<!-- phpunit.xml.dist — Unit suite uses separate bootstrap -->
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
</testsuites>
<php>
    <env name="WP_MOCK" value="true" />
</php>
```

## Base Test Class

```php
namespace My_Plugin\Tests\Unit;

use WP_Mock\Tools\TestCase;

abstract class Unit_Test_Case extends TestCase {

    public function setUp(): void {
        parent::setUp(); // calls WP_Mock::setUp()
    }

    public function tearDown(): void {
        parent::tearDown(); // calls WP_Mock::tearDown() — asserts all expectations met
    }
}
```

## Mocking Functions

```php
class Test_Settings extends Unit_Test_Case {

    public function test_get_setting_returns_option(): void {
        WP_Mock::userFunction( 'get_option', [
            'args'   => [ 'my_plugin_key', false ],
            'return' => 'stored_value',
            'times'  => 1,   // assert called exactly once
        ] );

        $result = my_plugin_get_setting( 'key' );

        $this->assertSame( 'stored_value', $result );
    }

    public function test_get_setting_with_callback_return(): void {
        WP_Mock::userFunction( 'get_option', [
            'return_arg' => 1,  // return the second argument (the default)
        ] );

        $result = my_plugin_get_setting( 'nonexistent', 'default' );

        $this->assertSame( 'default', $result );
    }

    public function test_save_setting(): void {
        WP_Mock::userFunction( 'update_option', [
            'args'   => [ 'my_plugin_key', 'new_value' ],
            'return' => true,
            'times'  => 1,
        ] );

        my_plugin_save_setting( 'key', 'new_value' );
    }
}
```

## Mocking Filters

```php
public function test_value_passes_through_filter(): void {
    WP_Mock::onFilter( 'my_plugin_value' )
        ->with( 'original' )
        ->reply( 'filtered' );

    $result = apply_filters( 'my_plugin_value', 'original' );

    $this->assertSame( 'filtered', $result );
}

public function test_filter_not_applied(): void {
    // Expect filter is applied but no listeners — returns original
    WP_Mock::passThruFunction( 'apply_filters' );

    $result = apply_filters( 'my_plugin_value', 'original' );

    $this->assertSame( 'original', $result );
}
```

## Mocking Actions

```php
public function test_action_fired(): void {
    WP_Mock::expectAction( 'my_plugin_after_save', [ 42, 'post' ] );

    my_plugin_save_item( 42, 'post' ); // should call do_action( 'my_plugin_after_save', 42, 'post' )
}

public function test_action_not_fired(): void {
    // If action fires unexpectedly, WP_Mock::tearDown() will fail the test
    my_plugin_save_item_no_action();
}
```

## Mocking add_filter / add_action Registration

```php
public function test_hooks_registered(): void {
    WP_Mock::expectFilterAdded( 'the_content', [ $this->getMockInstance(), 'filter_content' ], 10, 1 );
    WP_Mock::expectActionAdded( 'init', [ $this->getMockInstance(), 'init' ] );

    // Instantiate the class under test — it should register hooks in __construct
    new My_Plugin_Class();
}
```

## Mockery for Objects

```php
use Mockery;

public function test_uses_wpdb(): void {
    $wpdb = Mockery::mock( 'wpdb' );
    $wpdb->prefix = 'wp_';

    $wpdb->shouldReceive( 'prepare' )
         ->once()
         ->with( "SELECT * FROM wp_my_table WHERE id = %d", 42 )
         ->andReturn( 'SELECT * FROM wp_my_table WHERE id = 42' );

    $wpdb->shouldReceive( 'get_row' )
         ->once()
         ->andReturn( (object) [ 'id' => 42, 'title' => 'Test' ] );

    // Inject mock into code under test
    $repo   = new My_Plugin_Repository( $wpdb );
    $result = $repo->find( 42 );

    $this->assertSame( 'Test', $result->title );
}

public function tearDown(): void {
    parent::tearDown();
    Mockery::close(); // always close Mockery after each test
}
```

## wp_remote_get / HTTP Mocking

```php
public function test_api_call(): void {
    WP_Mock::userFunction( 'wp_remote_get', [
        'args'   => [ 'https://api.example.com/data', Mockery::type( 'array' ) ],
        'return' => [
            'response' => [ 'code' => 200 ],
            'body'     => '{"status":"ok"}',
        ],
    ] );

    WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [
        'return' => 200,
    ] );

    WP_Mock::userFunction( 'wp_remote_retrieve_body', [
        'return' => '{"status":"ok"}',
    ] );

    WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );

    $result = my_plugin_call_api();

    $this->assertSame( 'ok', $result['status'] );
}
```

## passThruFunction (escape hatch)

```php
// Don't care about this function, let it return its first arg
WP_Mock::passThruFunction( 'esc_html' );
WP_Mock::passThruFunction( 'esc_attr' );
WP_Mock::passThruFunction( 'sanitize_text_field' );
WP_Mock::passThruFunction( '__' ); // return the string as-is
```

## WP_Mock Strict Mode

By default, WP_Mock warns when WP functions are called that weren't mocked. Enable strict to fail instead:

```php
WP_Mock::setUp();
WP_Mock::userFunction( 'get_option', [ ... ] );
// If code calls wp_nonce_field() without a mock, test FAILS — not just warns
```
