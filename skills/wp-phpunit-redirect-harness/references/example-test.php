<?php
/**
 * Complete, copy-paste-ready harness for testing code that ends in
 * `wp_safe_redirect(); exit;`.
 *
 * Three pieces:
 *   1. Redirect exception      — its OWN PSR-4 file (shown inline here for
 *                                reference; in a real project put it in
 *                                tests/.../Redirect.php so the autoloader finds
 *                                it no matter which test loads first).
 *   2. set_up / tear_down      — install/remove the throwing wp_redirect filter.
 *   3. run() + a sample test   — catch the redirect, assert target + side effects.
 */

namespace MyPlugin\Test;

// ── 1. Shared exception (own file: tests/.../Redirect.php) ──────────────────
class Redirect extends \Exception {
	public string $location;
	public function __construct( string $location ) {
		parent::__construct( 'redirect' );
		$this->location = $location;
	}
}

// ── 2 + 3. Test case ────────────────────────────────────────────────────────
class ExampleRedirectTest extends \WP_UnitTestCase {

	/** @var callable */
	private $redirect_filter;

	public function set_up(): void {
		parent::set_up();

		// Production code may redirect off-site; whitelist exactly as prod does
		// so wp_safe_redirect() doesn't rewrite the location before the filter.
		add_filter( 'allowed_redirect_hosts', static function ( $hosts ) {
			$hosts[] = 'dashboard.example.com';
			return $hosts;
		} );

		// Turn wp_safe_redirect()'s trailing exit; into a catchable exception.
		$this->redirect_filter = static function ( $location ) {
			throw new Redirect( (string) $location );
		};
		add_filter( 'wp_redirect', $this->redirect_filter, 10, 1 );
	}

	public function tear_down(): void {
		remove_filter( 'wp_redirect', $this->redirect_filter, 10 );
		parent::tear_down();
	}

	/** Run the code under test; return the redirect target, or null if none. */
	private function run(): ?string {
		try {
			new \MyPlugin\Http\Thing();   // the class whose constructor may redirect+exit
		} catch ( Redirect $e ) {
			return $e->location;
		}
		return null;
	}

	public function test_happy_path_redirects_and_sets_session(): void {
		// ...arrange state so the code takes its redirect branch...

		$location = $this->run();

		// Assert the redirect target AND every side effect that ran before exit.
		$this->assertSame( 'https://dashboard.example.com/', $location );
		$this->assertSame( 1, get_current_user_id() );
		// + assert DB rows written before the redirect
	}

	public function test_error_path_does_not_redirect(): void {
		// ...arrange state that should NOT redirect...
		$this->assertNull( $this->run(), 'Error path must not redirect.' );
	}
}
