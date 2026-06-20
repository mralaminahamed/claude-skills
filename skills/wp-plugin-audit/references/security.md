# Advanced Security Audit Patterns

Patterns NOT covered by `escaping-sanitization.md` or `capability-nonce.md`. Use during Dimension D checks.

---

## File Upload Security

Every `$_FILES` handler must have all four guards:

```php
// 1. MIME type from content (not the browser-supplied type)
$file_type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
if ( ! $file_type['type'] ) {
    wp_die( 'Invalid file type.' );
}

// 2. Whitelist allowed extensions
$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf' );
if ( ! in_array( $file_type['ext'], $allowed, true ) ) {
    wp_die( 'File type not allowed.' );
}

// 3. Use wp_handle_upload() — moves to uploads dir, never leaves it
$uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );

// 4. Serve via wp_get_attachment_url() — never expose raw filesystem paths
```

**Flags to raise:**
- `move_uploaded_file()` used directly instead of `wp_handle_upload()`
- MIME type checked from `$_FILES['type']` (user-supplied, untrustworthy)
- Extension checked with `pathinfo()` only (double-extension bypass: `evil.php.jpg`)
- Uploaded files stored outside `wp-content/uploads/`
- Direct URL to `tmp_name` exposed anywhere

---

## Object Injection / `unserialize()`

`unserialize()` on user-supplied data allows PHP Object Injection (POP chain attacks).

**Flags to raise:**
- `unserialize( $_POST['...'] )` — critical
- `unserialize( get_option('...') )` where the option is user-writable
- `maybe_unserialize()` on any value that originated from user input

**Safe alternatives:**
```php
// Replace unserialize with JSON in new code
$data = json_decode( get_option( 'myplugin_data' ), true );
update_option( 'myplugin_data', wp_json_encode( $data ) );

// If you must unserialize a known-safe value, document the invariant:
// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- value written by this plugin only, never user input
$data = unserialize( $internal_cache );
```

---

## Secrets & API Key Storage

**Flags to raise:**
- API keys stored in `wp_options` without encryption
- Secrets hardcoded in plugin source files
- Secrets committed to version control (`.env` without `.gitignore`, `config.php` tracked)
- Secrets printed in debug output or error logs

**Correct patterns:**
```php
// Option 1 — wp-config.php constant (outside webroot, outside git)
define( 'MYPLUGIN_API_KEY', 'sk-...' );
$key = defined( 'MYPLUGIN_API_KEY' ) ? MYPLUGIN_API_KEY : get_option( 'myplugin_api_key' );

// Option 2 — environment variable (12-factor apps)
$key = getenv( 'MYPLUGIN_API_KEY' );

// Option 3 — encrypted option (when user must enter key in admin UI)
// Store encrypted via openssl_encrypt or wp_hash; never store raw
```

---

## SQL Injection Beyond `$wpdb->prepare()`

`$wpdb->prepare()` does NOT protect dynamic table names, column names, or `ORDER BY`/`LIMIT` values.

**Flags to raise:**
```php
// ORDER BY injection — user input directly in ORDER BY
$order = $_GET['order']; // 'ASC' or 'DESC' — must be whitelisted
$wpdb->get_results( "SELECT * FROM $table ORDER BY col $order" ); // VULNERABLE

// Table name injection
$table = $_GET['table'];
$wpdb->get_results( "SELECT * FROM $table" ); // VULNERABLE

// LIMIT injection — prepare() doesn't help here either
$wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table LIMIT %d", $limit ) );
// ✅ LIMIT is fine with prepare(); table name is not
```

**Safe patterns:**
```php
// Whitelist ORDER BY direction
$order = in_array( strtoupper( $_GET['order'] ?? '' ), array( 'ASC', 'DESC' ), true )
    ? strtoupper( $_GET['order'] )
    : 'ASC';

// Whitelist column names
$allowed_cols = array( 'id', 'name', 'date' );
$col = in_array( $_GET['col'] ?? '', $allowed_cols, true ) ? $_GET['col'] : 'id';

// Table names — always use $wpdb->prefix and your own constant
$table = $wpdb->prefix . 'myplugin_items'; // never from user input
```

---

## REST API Auth Hardening

Beyond `permission_callback` returning `true/false`:

**Flags to raise:**
- `permission_callback: '__return_true'` on endpoints that write data
- No authentication check on endpoints that read private data
- Nonce used for REST authentication instead of application passwords or cookie+nonce

**Correct patterns:**
```php
// Reading private data — require login
'permission_callback' => 'is_user_logged_in',

// Writing data — require specific capability
'permission_callback' => function() {
    return current_user_can( 'edit_posts' );
},

// REST nonce (for logged-in browser requests via wp.apiFetch)
// wp_create_nonce( 'wp_rest' ) — NOT a regular action nonce
// Application Passwords — for external clients (no nonce needed)

// Public webhook (HMAC-verified) — correct to have no user check:
'permission_callback' => '__return_true', // intentional — HMAC verified in handler
// Document this with a comment so the audit doesn't flag it as a false positive
```

---

## Dependency CVE Scanning

Run after any `composer update` or before a release:

```bash
# Check for known CVEs in installed packages
composer audit

# Show what's outdated (context for triage)
composer outdated --direct
```

**In CI (`validate.yml` or separate job):**
```yaml
- name: Composer security audit
  run: composer audit --no-dev
```

Findings from `composer audit` are a 🔴 functional/security severity in the audit report. Link the CVE advisory URL in the finding.

---

## Open Redirect Prevention

Flags to raise in redirect handlers:

```php
// VULNERABLE — user controls the destination
wp_redirect( $_GET['redirect_to'] );

// SAFE — validate against allowed origins
$redirect = isset( $_GET['redirect_to'] ) ? wp_sanitize_redirect( $_GET['redirect_to'] ) : admin_url();
$redirect = wp_validate_redirect( $redirect, admin_url() ); // second arg = fallback
wp_redirect( $redirect );
wp_exit();
```

`wp_validate_redirect()` blocks off-site redirects unless the host is in the allowed list. Always pair with `wp_sanitize_redirect()` first.

---

## Path Traversal

Flags to raise when constructing filesystem paths from user input:

```php
// VULNERABLE
$file = $_GET['file'];
include WP_PLUGIN_DIR . '/myplugin/templates/' . $file;

// SAFE — realpath + prefix check
$requested = realpath( WP_PLUGIN_DIR . '/myplugin/templates/' . $file );
$base      = realpath( WP_PLUGIN_DIR . '/myplugin/templates/' );
if ( $requested === false || strpos( $requested, $base . DIRECTORY_SEPARATOR ) !== 0 ) {
    wp_die( 'Invalid template.' );
}
include $requested;
```

Also flag: `..` not stripped from user-supplied filenames before filesystem operations.
