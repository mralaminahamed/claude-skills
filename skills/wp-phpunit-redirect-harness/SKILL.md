---
name: wp-phpunit-redirect-harness
description: Use when WordPress PHPUnit tests exercise code that calls wp_safe_redirect()/wp_redirect() followed by exit/die, or when the suite mysteriously stops early and prints no "Tests:" summary. Installs a throwing-filter harness so redirect+exit paths become assertable.
---

# PHPUnit Harness for `wp_safe_redirect(); exit;`

## The problem

Production code commonly does:

```php
wp_safe_redirect( $url );
exit;
```

Under PHPUnit, `add_filter( 'wp_redirect', '__return_false' )` stops the header but **does NOT stop `exit`** — the next statement runs and terminates the whole PHP process. PHPUnit dies mid-run: exit code 0, no `Tests: N` summary, and every test after the first redirect test silently never runs. Symptom: "the suite passes but prints no summary" or "stops at ~N%".

## The fix — throw instead of redirect, catch instead of exit

Throwing from the `wp_redirect` filter unwinds the stack **before** `exit;` is reached.

### 1. A shared exception (own PSR-4 file so both unit + integration tests can use it)

```php
namespace MyPlugin\Test;

class Redirect extends \Exception {
    public string $location;
    public function __construct( string $location ) {
        parent::__construct( 'redirect' );
        $this->location = $location;
    }
}
```

Put it in its own file (e.g. `tests/.../Redirect.php`) so the autoloader finds it regardless of which test file loads first — never inline in one test file.

### 2. Throwing filter in `set_up`, removed in `tear_down`

```php
$this->redirect_filter = static function ( $location ) {
    throw new Redirect( (string) $location );
};
add_filter( 'wp_redirect', $this->redirect_filter, 10, 1 );
// tear_down: remove_filter( 'wp_redirect', $this->redirect_filter, 10 );
```

### 3. A run helper that catches it

```php
private function run(): ?string {
    try {
        new Thing();              // the code that may redirect+exit
    } catch ( Redirect $e ) {
        return $e->location;      // redirected here
    }
    return null;                  // returned without redirecting
}
```

### 4. Assert on the redirect AND the side effects that preceded it

```php
$location = $this->run();
$this->assertSame( $expected_url, $location );
$this->assertSame( $user_id, get_current_user_id() );   // auth set before redirect
// + DB rows written before the redirect
```

## Gotchas

- The redirect target must pass `wp_validate_redirect()` (host allow-listed) or `wp_safe_redirect` rewrites it before the filter fires — whitelist via `allowed_redirect_hosts` exactly as production does.
- Use `run()` for redirect-path tests; non-redirect tests still call it safely (returns `null`).
- This same class of bug hides untested code: once the harness is in, expect to *add* coverage for the redirect paths that were previously unreachable.

## References

- `references/example-test.php` — complete copy-paste harness: the `Redirect` exception, `set_up`/`tear_down` with the throwing filter + `allowed_redirect_hosts`, the `run()` helper, and sample redirect / no-redirect tests.
