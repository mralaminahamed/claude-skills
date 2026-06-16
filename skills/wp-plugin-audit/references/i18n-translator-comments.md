# i18n & Translator Comments Reference

## Function Signatures

| Function | Returns | Use when |
|---|---|---|
| `__( $text, $domain )` | string | Need the translated string |
| `_e( $text, $domain )` | void | Echo the translated string |
| `_x( $text, $context, $domain )` | string | Same word, different meanings |
| `_ex( $text, $context, $domain )` | void | Echo with context |
| `_n( $single, $plural, $count, $domain )` | string | Singular / plural |
| `_nx( $single, $plural, $count, $context, $domain )` | string | Plural + context |
| `_n_noop( $single, $plural, $domain )` | array | Defer plural resolution |
| `_nx_noop( $single, $plural, $ctx, $domain )` | array | Defer plural + context |
| `translate_nooped_plural( $nooped, $count )` | string | Resolve deferred plural |
| `esc_html__( $text, $domain )` | string | Translate + HTML-escape |
| `esc_html_e( $text, $domain )` | void | Echo + translate + HTML-escape |
| `esc_html_x( $text, $ctx, $domain )` | string | Translate + context + HTML-escape |
| `esc_attr__( $text, $domain )` | string | Translate + attr-escape |
| `esc_attr_e( $text, $domain )` | void | Echo + translate + attr-escape |
| `esc_attr_x( $text, $ctx, $domain )` | string | Translate + context + attr-escape |

---

## Translator Comments

Required when a translated string contains **format placeholders** (`%s`, `%d`, `%1$s`, etc.) so translators understand what each placeholder represents.

### Format

```php
/* translators: %s: post title */
printf( __( 'Deleted "%s" permanently.', 'my-plugin' ), $post_title );
```

- Must appear on the line **immediately before** the `i18n` call (or `printf`/`sprintf` wrapping it).
- Must start with `translators:` (lowercase, colon).
- Multi-line OK for complex explanations.

### Examples

```php
/* translators: 1: plugin name, 2: version number */
printf( __( '%1$s version %2$s is active.', 'my-plugin' ), $name, $version );

/* translators: %d: number of items deleted */
printf( _n( 'Deleted %d item.', 'Deleted %d items.', $count, 'my-plugin' ), $count );

/* translators: draft saved date format, see https://php.net/date */
$format = __( 'g:i:s a', 'my-plugin' );
```

---

## Variable Handling Rules

**Never embed variables directly in translatable strings:**

```php
// Wrong — untranslatable
_e( "Your city is $city.", 'my-plugin' );

// Wrong — concatenation breaks context
echo __( 'Hello ', 'my-plugin' ) . $name;

// Correct — placeholder
printf( __( 'Your city is %s.', 'my-plugin' ), esc_html( $city ) );
```

**Multiple variables → numbered placeholders** (allow reordering in target languages):

```php
printf(
    __( 'Your city is %1$s, zip: %2$s.', 'my-plugin' ),
    esc_html( $city ),
    esc_html( $zipcode )
);
```

---

## Context Strings (`_x`)

Use when the same source word has different meanings in different UI locations:

```php
_x( 'Post', 'noun — menu item label', 'my-plugin' );
_x( 'Post', 'verb — button label', 'my-plugin' );
```

---

## Common Audit Flags

- `sprintf`/`printf` with a translatable string and `%s`/`%d` but **no translator comment** → 🟠
- Variable embedded directly in `__()` / `_e()` string → 🟠
- Missing `$domain` argument on any `__()` / `_e()` call → 🟠
- Multiple variables using `%s %s` instead of `%1$s %2$s` → 🟡
- `__()` on a string that is pure HTML (`<strong>text</strong>`) → 🟡 use `esc_html__` and wrap HTML outside
- `_e()` inside an HTML attribute → 🟠 use `esc_attr_e()` instead

---

## Checklist Greps

```bash
# All translation calls — verify domain arg is present and consistent
grep -rn "__(\|_e(\|_x(\|_n(\|_nx(\|esc_html__(\|esc_attr__(" --include=*.php includes/ src/ templates/

# Find sprintf/printf with translatable string (need translator comment)
grep -rn "printf\s*(.*__()\|sprintf\s*(.*__()" --include=*.php .

# Missing translator comments: line before printf/__() should contain 'translators:'
# Run this and manually check each hit
grep -n "printf\|sprintf" --include=*.php -r . | grep -v "translators"

# Variables directly in translatable strings
grep -rn '__(\s*"[^"]*\$\|__(\s*'"'"'[^'"'"']*\$' --include=*.php .

# Calls missing text domain (second arg absent)
grep -rn "__(\s*['\"][^'\"]*['\"][^,)]*)" --include=*.php . | grep -v ","
```
