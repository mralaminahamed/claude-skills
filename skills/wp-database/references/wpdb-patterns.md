# $wpdb Patterns Reference

## Prepared Statements

Always use `$wpdb->prepare()` for any value not hardcoded in the source.

### Format placeholders
| Placeholder | Type | Example |
|---|---|---|
| `%d` | Integer | `42`, `-1`, `0` |
| `%s` | String | `'hello'`, `''` |
| `%f` | Float | `3.14`, `0.0` |
| `%%` | Literal `%` | — |

```php
// Single value
$wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $post_id )

// Multiple values
$wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s", $type, $status )

// IN clause — build placeholders dynamically
$ids           = [ 1, 2, 3, 4 ];
$placeholders  = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
$sql           = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE ID IN ({$placeholders})",
    ...$ids
);
```

## Read Methods

```php
// Single row → stdClass or null
$row = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}my_table WHERE id = %d", $id )
);
// $row->column_name

// Single row as array
$row = $wpdb->get_row( $sql, ARRAY_A ); // associative array
$row = $wpdb->get_row( $sql, ARRAY_N ); // numeric array

// Multiple rows → array of stdClass
$rows = $wpdb->get_results(
    $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}my_table WHERE status = %s ORDER BY id DESC LIMIT %d", 'active', 50 )
);
foreach ( $rows as $row ) { $row->id; $row->status; }

// Multiple rows as associative arrays
$rows = $wpdb->get_results( $sql, ARRAY_A );

// Single value → string or null
$count = (int) $wpdb->get_var(
    $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}my_table WHERE user_id = %d", $user_id )
);

// Single column → indexed array of values
$ids = $wpdb->get_col(
    $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}my_table WHERE status = %s", 'active' )
);
// $ids = ['1', '2', '3'] — always strings, cast as needed
$ids = array_map( 'intval', $ids );
```

## Write Methods

```php
// INSERT
$rows_affected = $wpdb->insert(
    $wpdb->prefix . 'my_table',     // table
    [                                // data
        'title'      => $title,
        'user_id'    => $user_id,
        'created_at' => current_time( 'mysql' ),
    ],
    [ '%s', '%d', '%s' ]            // formats (match data order)
);
$new_id = $wpdb->insert_id;

// UPDATE
$rows_affected = $wpdb->update(
    $wpdb->prefix . 'my_table',
    [ 'title' => $new_title ],      // data to update
    [ 'id'    => $record_id ],      // WHERE clause
    [ '%s' ],                       // data formats
    [ '%d' ]                        // WHERE formats
);

// DELETE
$rows_affected = $wpdb->delete(
    $wpdb->prefix . 'my_table',
    [ 'user_id' => $user_id, 'status' => 'inactive' ],
    [ '%d', '%s' ]
);

// Error check: returns false on failure, int (rows affected) on success
if ( false === $rows_affected ) {
    $error = $wpdb->last_error;
}
```

## Raw Queries (DDL, complex DML)

```php
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$result = $wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->prefix}my_table SET processed = 1 WHERE created_at < %s AND status = %s",
        $threshold_date,
        'pending'
    )
);
// Returns int (rows affected) or false
```

## INSERT ... ON DUPLICATE KEY UPDATE

```php
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
    $wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}my_cache (cache_key, cache_value, expires_at)
         VALUES (%s, %s, %s)
         ON DUPLICATE KEY UPDATE cache_value = VALUES(cache_value), expires_at = VALUES(expires_at)",
        $key,
        $value,
        gmdate( 'Y-m-d H:i:s', time() + 3600 )
    )
);
```

## Caching Queries

```php
function my_plugin_get_items( int $user_id, string $status ): array {
    $cache_key   = "my_plugin_items_{$user_id}_{$status}";
    $cache_group = 'my_plugin';

    $items = wp_cache_get( $cache_key, $cache_group );
    if ( false !== $items ) {
        return $items;
    }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}my_plugin_items WHERE user_id = %d AND status = %s ORDER BY created_at DESC",
            $user_id,
            $status
        )
    );

    wp_cache_set( $cache_key, $items, $cache_group, 300 ); // 5 minutes
    return $items ?: [];
}

// Invalidate when data changes
function my_plugin_invalidate_cache( int $user_id ): void {
    wp_cache_delete( "my_plugin_items_{$user_id}_active", 'my_plugin' );
    wp_cache_delete( "my_plugin_items_{$user_id}_inactive", 'my_plugin' );
}
```

## WP Core Table References

```php
$wpdb->posts          // {prefix}posts
$wpdb->postmeta       // {prefix}postmeta
$wpdb->users          // {prefix}users
$wpdb->usermeta       // {prefix}usermeta
$wpdb->terms          // {prefix}terms
$wpdb->term_taxonomy  // {prefix}term_taxonomy
$wpdb->term_relationships // {prefix}term_relationships
$wpdb->termmeta       // {prefix}termmeta
$wpdb->options        // {prefix}options
$wpdb->comments       // {prefix}comments
$wpdb->commentmeta    // {prefix}commentmeta
$wpdb->links          // {prefix}links

// Multisite tables
$wpdb->blogs          // {prefix}blogs
$wpdb->sitemeta       // {prefix}sitemeta
$wpdb->site           // {prefix}site
$wpdb->blogmeta       // {prefix}blogmeta (WP 5.1+)
```

## Debug Queries

```php
// Log last query (development only)
global $wpdb;
error_log( 'Last query: ' . $wpdb->last_query );
error_log( 'Last result: ' . print_r( $wpdb->last_result, true ) );
error_log( 'Last error: ' . $wpdb->last_error );

// Enable full query log
define( 'SAVEQUERIES', true );
// Then inspect:
error_log( print_r( $wpdb->queries, true ) );
// Each entry: [ query, execution_time, caller, start_time ]

// Count queries this request
echo 'Total queries: ' . get_num_queries();
```

## Transactions

```php
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( 'START TRANSACTION' );
try {
    $wpdb->insert( ... );
    $wpdb->update( ... );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query( 'COMMIT' );
} catch ( \Throwable $e ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query( 'ROLLBACK' );
    throw $e;
}
```
