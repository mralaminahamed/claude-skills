# dbDelta Rules and Patterns

`dbDelta()` is the only WP-safe way to create or alter tables. It diffs the current schema against the desired schema and applies only necessary changes. Strict formatting is required.

## Formatting Rules (violations cause silent failure)

| Rule | Correct | Wrong |
|---|---|---|
| Spaces after PRIMARY KEY | `PRIMARY KEY  (id)` (2 spaces) | `PRIMARY KEY (id)` (1 space) |
| No trailing comma after last field | `last_field varchar(100)` | `last_field varchar(100),` |
| INDEX keyword | Use `KEY` | Use `INDEX` |
| Column type uppercase | `VARCHAR(255)` or `varchar(255)` | Inconsistent casing (caution) |
| Charset at end | `{$charset_collate}` | Missing charset |
| Each definition on its own line | One field per line | Multiple fields per line |
| No blank lines inside CREATE TABLE | — | Blank lines between fields |

## Correct Template

```php
function my_plugin_get_schema(): string {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    return "
CREATE TABLE {$wpdb->prefix}my_plugin_items (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  content longtext NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  meta longtext,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY parent_id (parent_id),
  KEY user_id (user_id),
  KEY status (status),
  KEY created_at (created_at)
) {$charset_collate};

CREATE TABLE {$wpdb->prefix}my_plugin_log (
  log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  item_id bigint(20) unsigned NOT NULL,
  action varchar(100) NOT NULL DEFAULT '',
  message longtext NOT NULL,
  logged_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (log_id),
  KEY item_id (item_id)
) {$charset_collate};";
}

function my_plugin_create_tables(): void {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( my_plugin_get_schema() );
}
```

## What dbDelta CAN do

- Create tables that don't exist ✅
- Add columns to existing tables ✅
- Add indexes to existing tables ✅
- Change column definition if type/length differs ✅ (with caveats)

## What dbDelta CANNOT do

- Remove columns ❌ (use `ALTER TABLE DROP COLUMN` manually)
- Remove indexes ❌ (use `ALTER TABLE DROP INDEX` manually)
- Rename columns ❌ (drop + add + migrate data)
- Change column order ❌

## Manual ALTER TABLE for removals

```php
function my_plugin_migrate_1_3_0(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'my_plugin_items';

    // Check column exists before dropping
    $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );
    if ( in_array( 'old_column', $columns, true ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "ALTER TABLE {$table} DROP COLUMN old_column" );
    }

    // Check index exists before dropping
    $indexes = $wpdb->get_col( "SHOW INDEX FROM {$table}", 2 ); // Key_name column
    if ( in_array( 'old_index', $indexes, true ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "ALTER TABLE {$table} DROP INDEX old_index" );
    }
}
```

## Column Types Quick Reference

| Use case | Column type |
|---|---|
| Primary key ID | `bigint(20) unsigned NOT NULL AUTO_INCREMENT` |
| Foreign key / user ID / post ID | `bigint(20) unsigned NOT NULL DEFAULT 0` |
| Short text (slug, status, type) | `varchar(100) NOT NULL DEFAULT ''` |
| URL | `varchar(2083) NOT NULL DEFAULT ''` |
| Long string (title) | `varchar(255) NOT NULL DEFAULT ''` |
| Serialised array / JSON | `longtext` |
| Rich content | `longtext NOT NULL` |
| Decimal price | `decimal(19,4) NOT NULL DEFAULT '0.0000'` |
| Boolean flag | `tinyint(1) NOT NULL DEFAULT 0` |
| Timestamp | `datetime NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| Float percentage | `float NOT NULL DEFAULT '0'` |

## Character Set

Always use `$wpdb->get_charset_collate()`. For full 4-byte Unicode support (emoji):

```php
// Standard (set by WP config, usually utf8mb4)
$charset_collate = $wpdb->get_charset_collate();
// Result: "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci"

// Explicit utf8mb4 override (if WP configured for utf8)
$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
```

**Note for indexes:** `varchar(255)` columns with utf8mb4 encoding exceed the 767-byte index limit on MySQL < 5.7. Use `varchar(191)` for indexed columns or enable `innodb_large_prefix`.

```php
// Safe: index on varchar(191)
meta_key varchar(191) NOT NULL DEFAULT '',
// ...
KEY meta_key (meta_key)

// Unsafe on older MySQL: index on varchar(255) with utf8mb4
```

## Verify Table Existence

```php
function my_plugin_tables_installed(): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'my_plugin_items';
    return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
}
```

## Debug dbDelta

```php
// dbDelta returns an array of queries run
$queries = dbDelta( $sql );
if ( ! empty( $queries ) ) {
    foreach ( $queries as $query => $result ) {
        error_log( "dbDelta: {$query} → {$result}" );
    }
}

// Check for errors after
global $EZSQL_ERROR;
if ( ! empty( $EZSQL_ERROR ) ) {
    error_log( print_r( $EZSQL_ERROR, true ) );
}
```
