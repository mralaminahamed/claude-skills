# Brain\Monkey Unit Testing Patterns

Brain\Monkey mocks WP functions without loading WordPress. More powerful than WP_Mock: supports `when()` (aliases, return values) and `expect()` (call assertions), plus full Mockery integration.

## Install

```bash
composer require --dev brain/monkey mockery/mockery yoast/phpunit-polyfills
```

## Base Unit Test Case

The pattern used in squad-modules-for-divi — a base class that wires up Brain\Monkey and pre-stubs all common WP functions:

```php
<?php
namespace MyPlugin\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

abstract class Unit_Test_Case extends TestCase {

    use MockeryPHPUnitIntegration; // auto-closes Mockery, verifies expectations

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Stub i18n + escaping — return first arg unchanged
        Functions\stubEscapeFunctions();
        Functions\stubTranslationFunctions();

        $this->stubWordPressFunctions();
    }

    protected function tearDown(): void {
        Monkey\tearDown(); // verifies all expect() calls were satisfied
        parent::tearDown();
    }

    protected function stubWordPressFunctions(): void {
        // Options
        Functions\when( 'get_option' )->justReturn( false );
        Functions\when( 'update_option' )->justReturn( true );
        Functions\when( 'delete_option' )->justReturn( true );

        // Transients
        Functions\when( 'get_transient' )->justReturn( false );
        Functions\when( 'set_transient' )->justReturn( true );
        Functions\when( 'delete_transient' )->justReturn( true );

        // Hooks (return values no one cares about)
        Functions\when( 'add_action' )->justReturn( null );
        Functions\when( 'add_filter' )->justReturn( null );
        Functions\when( 'do_action' )->justReturn( null );
        Functions\when( 'apply_filters' )->returnArg( 2 ); // return filtered value arg

        // Common utilities
        Functions\when( 'is_wp_error' )->alias( fn( $t ) => $t instanceof \WP_Error );
        Functions\when( 'absint' )->alias( fn( $v ) => abs( (int) $v ) );
        Functions\when( 'wp_json_encode' )->alias( fn( $d, $o = 0 ) => json_encode( $d, $o ) );
        Functions\when( 'wp_unslash' )->alias( fn( $v ) => is_string( $v ) ? stripslashes( $v ) : $v );
        Functions\when( 'trailingslashit' )->alias( fn( $s ) => rtrim( $s, '/\\' ) . '/' );

        // URLs
        Functions\when( 'home_url' )->justReturn( 'https://example.com' );
        Functions\when( 'admin_url' )->justReturn( 'https://example.com/wp-admin/' );
        Functions\when( 'plugins_url' )->justReturn( 'https://example.com/wp-content/plugins/' );
        Functions\when( 'plugin_dir_path' )->alias( fn( $f ) => rtrim( dirname( $f ), '/' ) . '/' );
        Functions\when( 'plugin_basename' )->alias( fn( $f ) => basename( $f ) );

        // Sanitization (alias to PHP equivalents)
        Functions\when( 'sanitize_text_field' )->alias( fn( $s ) => trim( strip_tags( (string) $s ) ) );
        Functions\when( 'sanitize_key' )->alias( fn( $k ) => strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $k ) ) );
        Functions\when( 'esc_html' )->alias( fn( $t ) => htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ) );
        Functions\when( 'esc_attr' )->alias( fn( $t ) => htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ) );
        Functions\when( 'esc_url' )->alias( fn( $u ) => (string) $u );

        // Caps
        Functions\when( 'current_user_can' )->justReturn( true );
        Functions\when( 'is_admin' )->justReturn( false );
        Functions\when( 'is_multisite' )->justReturn( false );

        // Cache
        Functions\when( 'wp_cache_get' )->justReturn( false );
        Functions\when( 'wp_cache_set' )->justReturn( true );
        Functions\when( 'wp_cache_delete' )->justReturn( true );
    }
}
```

## Functions\when() vs Functions\expect()

| Method | Use | Verifies call? |
|---|---|---|
| `when()` | Stub — just returns a value | No |
| `expect()` | Assertion — must be called | Yes (checked in tearDown) |

```php
// when() — silent stub, don't care if called
Functions\when( 'get_option' )->justReturn( false );
Functions\when( 'get_option' )->returnArg();          // return 1st arg
Functions\when( 'get_option' )->returnArg( 2 );       // return 2nd arg
Functions\when( 'get_option' )->alias( fn( $k ) => "value_of_{$k}" ); // callback

// expect() — asserted, causes failure if not called
Functions\expect( 'get_option' )
    ->once()
    ->with( 'my_plugin_key', false )
    ->andReturn( 'stored_value' );

Functions\expect( 'update_option' )
    ->twice()
    ->with( \Mockery::type( 'string' ), \Mockery::any() )
    ->andReturn( true );

Functions\expect( 'wp_mail' )
    ->never();   // assert NOT called
```

## Hook Expectations

```php
// Assert action is registered
Monkey\Actions\expectAdded( 'init' )
    ->once()
    ->with( \Mockery::type( 'callable' ) );

// Assert filter is registered
Monkey\Filters\expectAdded( 'the_content' )
    ->once()
    ->with( \Mockery::type( 'callable' ), 10, 1 );

// Assert action is fired
Monkey\Actions\expectDone( 'my_plugin_after_save' )
    ->once()
    ->with( 42 );

// Assert filter is applied with expected value
Monkey\Filters\expectApplied( 'my_plugin_value' )
    ->once()
    ->with( 'raw_value' )
    ->andReturn( 'filtered_value' );
```

## Mockery for Object Mocking

Brain\Monkey ships Mockery — use it freely in the same test:

```php
use Mockery;

public function test_repository_get_calls_wpdb(): void {
    $wpdb         = Mockery::mock( 'wpdb' );
    $wpdb->prefix = 'wp_';

    $wpdb->shouldReceive( 'prepare' )
         ->once()
         ->with( "SELECT * FROM wp_my_table WHERE id = %d", 1 )
         ->andReturn( 'SELECT * FROM wp_my_table WHERE id = 1' );

    $wpdb->shouldReceive( 'get_row' )
         ->once()
         ->andReturn( (object) [ 'id' => 1, 'title' => 'Test' ] );

    $repo   = new My_Plugin_Repository( $wpdb );
    $result = $repo->find( 1 );

    $this->assertSame( 'Test', $result->title );
}
// Mockery::close() called automatically by MockeryPHPUnitIntegration trait
```

## ReflectsObjects — Test Private/Protected Members

Put this trait in `tests/Shared/Concerns/ReflectsObjects.php`:

```php
<?php
namespace MyPlugin\Tests\Shared\Concerns;

use ReflectionMethod;
use ReflectionProperty;

trait ReflectsObjects {

    protected function get_protected_property( object|string $target, string $property ): mixed {
        $ref = new ReflectionProperty( is_object( $target ) ? get_class( $target ) : $target, $property );
        return $ref->getValue( is_object( $target ) ? $target : null );
    }

    protected function set_protected_property( object|string $target, string $property, mixed $value ): void {
        $ref = new ReflectionProperty( is_object( $target ) ? get_class( $target ) : $target, $property );
        if ( is_object( $target ) ) {
            $ref->setValue( $target, $value );
        } else {
            $ref->setValue( null, $value );
        }
    }

    protected function invoke_protected_method( object $target, string $method, array $args = [] ): mixed {
        $ref = new ReflectionMethod( get_class( $target ), $method );
        return $ref->invokeArgs( $target, $args );
    }
}
```

Usage in test:

```php
class Test_My_Service extends Unit_Test_Case {
    use \MyPlugin\Tests\Shared\Concerns\ReflectsObjects;

    public function test_private_method(): void {
        $service = new My_Service();
        $result  = $this->invoke_protected_method( $service, 'build_query', [ 'term', 10 ] );
        $this->assertStringContainsString( 'term', $result );
    }

    public function test_private_property(): void {
        $service = new My_Service();
        $this->set_protected_property( $service, 'cache_ttl', 0 );
        $this->assertSame( 0, $this->get_protected_property( $service, 'cache_ttl' ) );
    }
}
```

## Base Integration Test Case (with REST + Users)

```php
<?php
namespace MyPlugin\Tests\Integration;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WP_REST_Server;
use WP_UnitTestCase;

abstract class Integration_Test_Case extends WP_UnitTestCase {

    use MockeryPHPUnitIntegration;

    protected WP_REST_Server $server;
    protected int $admin_id;
    protected int $editor_id;

    public function setUp(): void {
        parent::setUp();

        global $wp_rest_server;
        $wp_rest_server = new WP_REST_Server();
        $this->server   = $wp_rest_server;
        do_action( 'rest_api_init' );

        $this->admin_id  = self::factory()->user->create( [ 'role' => 'administrator' ] );
        $this->editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
        wp_set_current_user( $this->admin_id );
    }

    protected function rest_get( string $route, array $params = [] ): \WP_REST_Response {
        $request = new \WP_REST_Request( 'GET', $route );
        $request->set_query_params( $params );
        return $this->server->dispatch( $request );
    }

    protected function rest_post( string $route, array $body = [] ): \WP_REST_Response {
        $request = new \WP_REST_Request( 'POST', $route );
        $request->set_body_params( $body );
        return $this->server->dispatch( $request );
    }
}
```

## composer.json (Brain\Monkey stack)

```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "brain/monkey": "^2.6",
        "mockery/mockery": "^1.6",
        "yoast/phpunit-polyfills": "^2.0"
    },
    "scripts": {
        "test":       "phpunit",
        "test:unit":  "phpunit --testsuite=unit",
        "test:int":   "phpunit --testsuite=integration"
    }
}
```

## WP_Mock vs Brain\Monkey

| Feature | WP_Mock (10up) | Brain\Monkey |
|---|---|---|
| Function mocking | `userFunction()` | `when()` / `expect()` |
| Hook expectations | `expectAction()` / `expectFilterAdded()` | `Actions\expectDone()` / `Actions\expectAdded()` |
| Object mocking | Manual Mockery | Built-in Mockery |
| Escape stubs | Manual | `stubEscapeFunctions()` |
| i18n stubs | Manual | `stubTranslationFunctions()` |
| PHP 8.x support | Good | Excellent |
| Activity | Maintained | Actively maintained |
