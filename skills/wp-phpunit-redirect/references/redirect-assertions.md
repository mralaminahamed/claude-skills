# Redirect Assertions in WP Tests

## The Problem

WP's `wp_redirect()` calls PHP's `header()`, which fails in test context:
```
Cannot modify header information - headers already sent
```

## WP_UnitTestCase Built-in: `go_to()`

Simulate a request to a URL and test what WP sets up:

```php
class Test_My_Redirects extends WP_UnitTestCase {

    public function test_unauthenticated_redirect(): void {
        $this->go_to( home_url( '/wp-admin/' ) );
        // Asserts WP_Query state, not headers
        $this->assertTrue( is_home() ); // example
    }
}
```

`go_to()` does NOT capture redirects — use the filter approach below.

## Capture Redirect via Filter

```php
class Test_Redirects extends WP_UnitTestCase {

    /** @var array{url:string,status:int}|null */
    private ?array $captured_redirect = null;

    public function setUp(): void {
        parent::setUp();
        $this->captured_redirect = null;

        // Intercept wp_redirect() before it calls header()
        add_filter( 'wp_redirect', function( string $location, int $status ) {
            $this->captured_redirect = [ 'url' => $location, 'status' => $status ];
            return false; // Returning false prevents actual redirect
        }, 10, 2 );
    }

    public function tearDown(): void {
        parent::tearDown();
        remove_all_filters( 'wp_redirect' );
    }

    private function assertRedirectedTo( string $expected_url, int $expected_status = 302 ): void {
        $this->assertNotNull( $this->captured_redirect, 'Expected a redirect but none occurred.' );
        $this->assertSame( $expected_url, $this->captured_redirect['url'],
            "Expected redirect to {$expected_url}, got {$this->captured_redirect['url']}" );
        $this->assertSame( $expected_status, $this->captured_redirect['status'] );
    }

    private function assertNoRedirect(): void {
        $this->assertNull( $this->captured_redirect, 'Expected no redirect but one occurred.' );
    }

    // Tests
    public function test_login_redirect(): void {
        wp_set_current_user( 0 ); // Logged out

        do_action( 'template_redirect' ); // Or call your function directly

        $this->assertRedirectedTo( wp_login_url( home_url( '/members/' ) ) );
    }

    public function test_admin_redirect_after_save(): void {
        $admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $admin );

        // Simulate form submission that triggers redirect
        $_POST['my_plugin_nonce'] = wp_create_nonce( 'my_plugin_save' );
        $_POST['my_plugin_title'] = 'New Title';

        do_action( 'admin_post_my_plugin_save' );

        $this->assertRedirectedTo( admin_url( 'admin.php?page=my-plugin&updated=1' ) );
    }
}
```

## Capture wp_safe_redirect()

`wp_safe_redirect()` calls `wp_redirect()` internally — same filter works:

```php
add_filter( 'wp_redirect', function( string $url, int $status ) {
    $this->captured = compact( 'url', 'status' );
    return false;
}, 10, 2 );
```

## Expect wp_die()

For tests where invalid access should die rather than redirect:

```php
class Test_Permission_Check extends WP_UnitTestCase {

    public function test_non_admin_cannot_access(): void {
        $subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber );

        $this->expectException( \WPDieException::class );

        // Call code that should wp_die() for non-admins
        my_plugin_admin_only_action();
    }
}
```

WP replaces `wp_die()` with an exception in test context — `WPDieException`.

## Capture Output Before Redirect

Some code echoes then redirects. Use output buffering:

```php
public function test_output_then_redirect(): void {
    ob_start();
    try {
        my_plugin_process_form();
    } catch ( \WPDieException $e ) {
        // swallow
    }
    $output = ob_get_clean();

    $this->assertStringContainsString( 'success', $output );
    $this->assertRedirectedTo( admin_url( 'admin.php?page=my-plugin' ) );
}
```

## AJAX Action Tests (no redirect, but die)

```php
class Test_Ajax extends WP_Ajax_UnitTestCase {

    public function test_ajax_save_success(): void {
        $admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $admin );

        $_POST['_ajax_nonce'] = wp_create_nonce( 'my_plugin_ajax' );
        $_POST['id']          = 42;

        try {
            $this->_handleAjax( 'my_plugin_ajax_handler' );
        } catch ( \WPAjaxDieContinueException $e ) {
            // wp_send_json_success() was called
        }

        $response = json_decode( $this->_last_response, true );
        $this->assertTrue( $response['success'] );
        $this->assertSame( 42, $response['data']['id'] );
    }

    public function test_ajax_save_error_without_nonce(): void {
        wp_set_current_user( 0 );

        try {
            $this->_handleAjax( 'my_plugin_ajax_handler' );
        } catch ( \WPAjaxDieStopException $e ) {
            // wp_die() was called (check_ajax_referer failed)
        }

        $this->assertStringContainsString( '-1', $this->_last_response );
    }
}
```

## REST API Tests (no redirect needed)

```php
class Test_REST extends WP_UnitTestCase {

    private \WP_REST_Server $server;

    public function setUp(): void {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        $this->server   = $wp_rest_server;
        do_action( 'rest_api_init' );
    }

    public function test_rest_endpoint(): void {
        $admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $admin );

        $request  = new \WP_REST_Request( 'GET', '/my-plugin/v1/items' );
        $response = $this->server->dispatch( $request );

        $this->assertSame( 200, $response->get_status() );
        $data = $response->get_data();
        $this->assertIsArray( $data );
    }
}
```
