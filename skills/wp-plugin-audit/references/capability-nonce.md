# Capability & Nonce Patterns Reference

## Critical Rule

> Nonces protect against CSRF. They do **not** prove identity.
> Always pair nonce verification with `current_user_can()`.

---

## Nonce Creation

| Function | Use when |
|---|---|
| `wp_nonce_field( $action )` | Inside an HTML `<form>` |
| `wp_create_nonce( $action )` | AJAX — pass via `wp_localize_script` or inline `<script>` |
| `wp_nonce_url( $url, $action )` | Action link / URL-based admin action |

Action string convention: `'verb-noun_id'` → e.g. `'delete-post_' . $post_id`.

```php
// Form
wp_nonce_field( 'delete-post_' . $post_id, '_wpnonce' );

// AJAX (JS side receives `my_plugin.nonce`)
wp_localize_script( 'my-script', 'my_plugin', [
    'nonce' => wp_create_nonce( 'my_plugin_ajax' ),
] );
```

---

## Nonce Verification

| Function | Use when | On failure |
|---|---|---|
| `check_admin_referer( $action )` | Admin form submissions | `wp_die()` |
| `check_ajax_referer( $action, $query_arg )` | AJAX handlers | `wp_send_json_error()` by default |
| `wp_verify_nonce( $nonce, $action )` | REST / custom — must handle failure yourself | returns `false` |

```php
// Admin form handler
check_admin_referer( 'delete-post_' . $post_id );
if ( ! current_user_can( 'delete_post', $post_id ) ) {
    wp_die( esc_html__( 'You are not allowed to do this.', 'td' ) );
}

// AJAX handler
check_ajax_referer( 'my_plugin_ajax', 'nonce' );
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_send_json_error( [ 'message' => 'Forbidden' ], 403 );
}

// REST permission_callback
'permission_callback' => function() {
    return current_user_can( 'manage_options' );
},
```

---

## Capability Map (common)

| Action | Capability |
|---|---|
| Read any post | `read` |
| Edit own posts | `edit_posts` |
| Edit others' posts | `edit_others_posts` |
| Delete posts | `delete_posts` |
| Publish posts | `publish_posts` |
| Manage plugin settings | `manage_options` |
| Install plugins | `install_plugins` |
| Activate plugins | `activate_plugins` |
| Edit theme files | `edit_theme_options` |
| Network-wide admin | `manage_network` |

Post-type-specific: `edit_post` (singular, takes `$post_id` as second arg).

---

## Public Webhook Exception

An endpoint that is **HMAC-verified** (shared secret in header) does not need `current_user_can()` — the HMAC *is* the auth mechanism. Document this explicitly in the **"Checked, NOT bugs"** section of the audit report.

```php
// Legitimate: no cap check — HMAC verified
$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if ( ! hash_equals( 'sha256=' . hash_hmac( 'sha256', $raw_body, SECRET ), $sig ) ) {
    wp_die( 'Invalid signature', 403 );
}
```

---

## Checklist Greps

```bash
# AJAX handlers — check both nonce and cap present
grep -rn "wp_ajax_\|wp_ajax_nopriv_" --include=*.php includes/ src/
# check_ajax_referer presence in those handlers
grep -rn "check_ajax_referer\|check_admin_referer\|wp_verify_nonce" --include=*.php .
# current_user_can in AJAX/admin handlers
grep -rn "current_user_can" --include=*.php .
# REST routes — permission_callback must not be __return_true
grep -rn "register_rest_route" --include=*.php .
grep -rn "permission_callback.*__return_true\|permission_callback.*true" --include=*.php .
# nonce fields in forms
grep -rn "wp_nonce_field\|nonce_action" --include=*.php .
```

### False-positive patterns to drop

- `wp_ajax_nopriv_` handler with no `current_user_can()` → only a bug if it **writes data**; read-only public AJAX is fine.
- `permission_callback` returning `true` on a **truly public** GET endpoint → intentional; document it.
- HMAC-verified webhook endpoint with no `current_user_can()` → intentional; document it.
