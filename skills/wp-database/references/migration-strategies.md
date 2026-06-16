# Database Migration Strategies

## Versioned Upgrade Routine

```php
// In plugin bootstrap (not activation hook — runs on every load)
add_action( 'init', 'my_plugin_maybe_upgrade' );

function my_plugin_maybe_upgrade(): void {
    $current = get_option( 'my_plugin_db_version', '0' );
    if ( version_compare( $current, MY_PLUGIN_DB_VERSION, '>=' ) ) {
        return;
    }

    my_plugin_run_migrations( $current );
    update_option( 'my_plugin_db_version', MY_PLUGIN_DB_VERSION );
}

function my_plugin_run_migrations( string $from_version ): void {
    // Run each migration in order
    if ( version_compare( $from_version, '1.1', '<' ) ) {
        my_plugin_migrate_1_1();
    }
    if ( version_compare( $from_version, '1.2', '<' ) ) {
        my_plugin_migrate_1_2();
    }
    if ( version_compare( $from_version, '2.0', '<' ) ) {
        my_plugin_migrate_2_0();
    }
}
```

## ADD COLUMN (non-destructive)

```php
function my_plugin_migrate_1_1(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'my_plugin_items';

    // Check column doesn't already exist (idempotent)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $column_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME, $table, 'priority'
        )
    );

    if ( ! $column_exists ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `priority` TINYINT(3) NOT NULL DEFAULT 0 AFTER `status`" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "ALTER TABLE `{$table}` ADD INDEX `idx_priority` (`priority`)" );
    }
}
```

## Chunked Data Migration (avoid timeouts)

For large tables: migrate in batches, track progress in an option.

```php
function my_plugin_migrate_2_0(): void {
    global $wpdb;
    $table    = $wpdb->prefix . 'my_plugin_items';
    $batch    = 500;
    $last_id  = (int) get_option( 'my_plugin_migrate_2_0_last_id', 0 );

    do {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, old_column FROM `{$table}` WHERE id > %d ORDER BY id ASC LIMIT %d",
                $last_id, $batch
            )
        );

        if ( empty( $rows ) ) break;

        foreach ( $rows as $row ) {
            $new_value = my_plugin_transform_value( $row->old_column );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update( $table, [ 'new_column' => $new_value ], [ 'id' => (int) $row->id ], [ '%s' ], [ '%d' ] );
        }

        $last_id = (int) end( $rows )->id;
        update_option( 'my_plugin_migrate_2_0_last_id', $last_id );

        // Yield to avoid timeout on large datasets
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::line( "Migrated up to ID {$last_id}…" );
        }

    } while ( count( $rows ) === $batch );

    delete_option( 'my_plugin_migrate_2_0_last_id' );
}
```

For very large tables, trigger via Action Scheduler instead:

```php
function my_plugin_schedule_migration_2_0(): void {
    if ( ! as_has_scheduled_action( 'my_plugin_migrate_2_0_batch' ) ) {
        as_enqueue_async_action( 'my_plugin_migrate_2_0_batch', [ 'last_id' => 0 ], 'my-plugin-migrate' );
    }
}

add_action( 'my_plugin_migrate_2_0_batch', function( int $last_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'my_plugin_items';
    $batch = 1000;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, old_column FROM `{$table}` WHERE id > %d ORDER BY id ASC LIMIT %d",
        $last_id, $batch
    ) );

    foreach ( $rows as $row ) {
        $wpdb->update( $table, [ 'new_column' => my_plugin_transform_value( $row->old_column ) ], [ 'id' => $row->id ] );
    }

    if ( count( $rows ) === $batch ) {
        $next_id = (int) end( $rows )->id;
        as_enqueue_async_action( 'my_plugin_migrate_2_0_batch', [ 'last_id' => $next_id ], 'my-plugin-migrate' );
    } else {
        // Done — record completion
        update_option( 'my_plugin_db_version', '2.0' );
    }
} );
```

## RENAME COLUMN (MySQL 8.0+ only)

```sql
ALTER TABLE `wp_my_plugin_items` RENAME COLUMN `old_name` TO `new_name`;
```

For MySQL 5.7 compatibility:
```sql
ALTER TABLE `wp_my_plugin_items`
    ADD COLUMN `new_name` VARCHAR(255) NOT NULL DEFAULT '',
    UPDATE `wp_my_plugin_items` SET `new_name` = `old_name`,
    DROP COLUMN `old_name`;
```

In PHP (use dbDelta for new tables, raw query for ALTER):
```php
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "ALTER TABLE `{$wpdb->prefix}my_plugin_items` CHANGE `old_name` `new_name` VARCHAR(255) NOT NULL DEFAULT ''" );
```

## DROP TABLE (uninstall only)

```php
// uninstall.php — only runs when user clicks Delete in Plugins list
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

$tables = [
    $wpdb->prefix . 'my_plugin_items',
    $wpdb->prefix . 'my_plugin_log',
];

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

delete_option( 'my_plugin_db_version' );
delete_option( 'my_plugin_settings' );
```

## Check Table Exists

```php
function my_plugin_table_exists( string $table ): bool {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    return (bool) $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
    );
}
```

## Index Management

```php
// Add index
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "ALTER TABLE `{$wpdb->prefix}my_table` ADD INDEX `idx_user_status` (`user_id`, `status`)" );

// Drop index
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "ALTER TABLE `{$wpdb->prefix}my_table` DROP INDEX `idx_user_status`" );

// Check index exists
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$index_exists = $wpdb->get_row(
    $wpdb->prepare(
        "SHOW INDEX FROM `{$wpdb->prefix}my_table` WHERE Key_name = %s",
        'idx_user_status'
    )
);
```
