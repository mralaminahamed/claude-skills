# Escaping & Sanitization Reference

## Rule: Escape late, sanitize early

- **Input** (saving to DB, using in logic) → **sanitize**
- **Output** (rendering to browser/JS/SQL) → **escape**
- Escape as close to the `echo` as possible (late escaping), not when storing.
- Early escape only when the variable is reused far from output; suffix name `_escaped` / `_safe` / `_clean`.

---

## Escaping by Context

| Context | Function | Notes |
|---|---|---|
| HTML element content | `esc_html()` | Encodes `<`, `>`, `&`, `"`, `'` |
| HTML attribute value | `esc_attr()` | Same encoding; use inside quotes |
| `href`, `src`, `action` | `esc_url()` | Strips bad protocols; use for output |
| URL stored in DB | `esc_url_raw()` | No protocol strip for display |
| Inline `<script>` | `esc_js()` | Escapes for JS string literals |
| `<textarea>` | `esc_textarea()` | Encodes for textarea content |
| XML blocks | `esc_xml()` | For XML output |
| Arbitrary HTML with allowlist | `wp_kses()` | Pass allowed tags array |
| Post-content HTML | `wp_kses_post()` | Uses `wp_kses` with post-allowed tags |
| Comment HTML | `wp_kses_data()` | Stricter than post |
| Integer | `(int)` / `absint()` | Cast; do not use `esc_*` for numbers |
| Float | `(float)` | Cast directly |

## Translation + Escape Combined

```php
esc_html__( 'String', 'text-domain' );   // return
esc_html_e( 'String', 'text-domain' );   // echo
esc_html_x( 'String', 'context', 'td' ); // return with context
esc_attr__( 'String', 'text-domain' );   // return
esc_attr_e( 'String', 'text-domain' );   // echo
esc_attr_x( 'String', 'context', 'td' ); // return with context
```

---

## Sanitization by Context

| Input type | Function |
|---|---|
| Plain text | `sanitize_text_field()` |
| Textarea (no HTML) | `sanitize_textarea_field()` |
| Email | `sanitize_email()` |
| URL | `esc_url_raw()` |
| Integer | `absint()` / `(int)` |
| Slug / key | `sanitize_key()` |
| HTML class | `sanitize_html_class()` |
| File name | `sanitize_file_name()` |
| Post content | `wp_kses_post()` |
| SQL | `$wpdb->prepare()` with `%s`, `%d`, `%f` placeholders |

---

## Common Audit Flags

- Raw `echo $_GET[...]` / `echo $_POST[...]` → 🔴 XSS
- `$wpdb->query("... $var ...")` without `$wpdb->prepare()` → 🔴 SQLi
- `echo get_option(...)` without `esc_html()` → 🟠 XSS risk
- `esc_html()` on a URL in `href=` → 🟠 wrong function (use `esc_url()`)
- `sanitize_text_field()` on post content → 🟡 strips valid HTML

## Checklist Greps

```bash
# Raw output of user-supplied vars
grep -rn "echo \$_GET\|echo \$_POST\|echo \$_REQUEST" --include=*.php .
# Unescaped option output
grep -rn "echo get_option\|echo get_post_meta" --include=*.php .
# Raw wpdb queries (potential SQLi)
grep -rn '\$wpdb->query\s*(\s*"' --include=*.php .
grep -rn "\$wpdb->get_results\s*(\s*\"" --include=*.php .
# esc_html on URLs (wrong context)
grep -rn 'href=.*esc_html\|src=.*esc_html' --include=*.php .
# Missing esc_url on href/src
grep -rn 'href="<?php echo\|src="<?php echo' --include=*.php . | grep -v esc_url
```
