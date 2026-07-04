# Advanced Security Audit Patterns

Patterns NOT covered by `escaping-sanitization.md` or `capability-nonce.md`. Use during Dimension D checks.

> **Prevalence (Patchstack, 2025):** across disclosed plugin CVEs — XSS ~35%, CSRF ~19%, **LFI ~13%**, **broken access control ~11%**, SQLi ~7%; ~43% are exploitable **without authentication**. Escaping (XSS) and nonces (CSRF) live in the sibling refs; the two highest-value classes documented *here* are **SSRF** and **broken access control**, because a WAF cannot see them.

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
- **`phar://` deserialization** — a file op (`file_exists`/`is_dir`/`fopen`/`getimagesize`/`file_get_contents`) whose path comes from user input; a `phar://…` path triggers unserialize of the archive's metadata. Validate the wrapper/scheme (reject `phar://`, `php://`, remote wrappers) before any filesystem call on user input.

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

## Broken Access Control (IDOR / privilege escalation)

The largest real-world WordPress class after XSS/CSRF, and the one WAFs miss — the traffic looks like normal authenticated requests, with no injection payload to pattern-match. A valid nonce proves the request **wasn't forged**; it does **NOT** prove the user is **authorized**. Every state-changing handler needs **both**: a nonce (CSRF) **and** a `current_user_can()` capability check (authorization).

**Flags to raise:**
- `add_action( 'wp_ajax_nopriv_{action}', … )` (or a public REST route) wired to a handler that edits posts/options/users — an unauthenticated privileged action.
- Nonce verified but **no** `current_user_can()` — CSRF-safe, yet any logged-in Subscriber can call it (vertical privilege escalation).
- **IDOR** — an object/user/order id read straight from the request and acted on without an ownership or capability check.
- Capability too weak for the action: `is_user_logged_in()` / `'read'` guarding an admin write that needs `manage_options`.
- Arbitrary `update_user_meta()` / `update_option()` where the meta key, option name, or target user id comes from the request.

```php
// VULNERABLE — nonce present, but no authorization: any subscriber can delete any row
check_admin_referer( 'myplugin_delete' );
$id = absint( $_POST['entry_id'] );
$wpdb->delete( $table, array( 'id' => $id ) );

// SAFE — nonce (CSRF) + ownership/capability (authorization)
check_admin_referer( 'myplugin_delete' );
$id = absint( $_POST['entry_id'] );
if ( (int) get_post_field( 'post_author', $id ) !== get_current_user_id()
     && ! current_user_can( 'delete_others_posts' ) ) {
    wp_die( 'Forbidden.', 403 );
}
$wpdb->delete( $table, array( 'id' => $id ) );
```

Basic nonce/capability mechanics live in `capability-nonce.md`; this section is about the **authorization gap** those checks exist to close. When auditing, grep every `wp_ajax_`, `admin_post_`, and `register_rest_route` handler for a capability check — not just a nonce.

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

## Server-Side Request Forgery (SSRF)

Any handler that fetches a **user-supplied URL** server-side can be coerced into hitting internal targets — cloud metadata (`169.254.169.254`), `localhost` admin panels, private ranges — bypassing the network perimeter.

```php
// VULNERABLE — attacker controls the destination
$body = wp_remote_retrieve_body( wp_remote_get( $_POST['url'] ) );

// SAFER — wp_safe_remote_* validates the host and rejects unsafe URLs; no redirects
$resp = wp_safe_remote_get(
    esc_url_raw( wp_unslash( $_POST['url'] ) ),
    array( 'redirection' => 0, 'reject_unsafe_urls' => true )
);
```

**Flags to raise:**
- `wp_remote_get`/`wp_remote_post`/`wp_remote_request`/`file_get_contents`/cURL on a URL derived from `$_GET`/`$_POST`/a user-writable option — use `wp_safe_remote_*` instead.
- No allowlist of permitted hosts and no `wp_http_validate_url()` check.
- `redirection` not set to `0` (an allowed URL can 302 to an internal target).

**Correct pattern** — allowlist + safe transport + no redirects:
```php
$url  = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
$host = wp_parse_url( $url, PHP_URL_HOST );
if ( ! in_array( $host, array( 'api.example.com' ), true ) || ! wp_http_validate_url( $url ) ) {
    wp_die( 'URL not allowed.', 400 );
}
$resp = wp_safe_remote_get( $url, array( 'redirection' => 0 ) );
```

> `wp_http_validate_url()` blocks credentials-in-URL, non-80/443/8080 ports, and private IPs — but is **defeatable by DNS rebinding** (the host resolves public on validation, private on the actual request). For high-value fetchers, resolve the host and re-check the IP against private ranges (`127.0.0.0/8`, `10/8`, `172.16/12`, `192.168/16`, `169.254.0.0/16`, `::1`, `fc00::/7`) immediately before the request, or block egress to those ranges at the firewall. A global `pre_http_request` filter can enforce the allowlist even for third-party code.

---

## Path Traversal & Local File Inclusion (LFI)

LFI was ~13% of 2025 plugin disclosures — the sink is an `include`/`require` of a user-influenced path (arbitrary PHP execution), while path traversal is the same flaw against read/write/delete file ops.

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
