# Background Processing Batch Patterns

## Chunked Processing (avoid timeouts)

```php
class My_Plugin_Import_Process extends WP_Background_Process {

    protected $action = 'my_plugin_import';

    protected function task( $item ) {
        // $item = [ 'id' => 123, 'data' => [...] ]
        if ( $this->time_exceeded() || $this->memory_exceeded() ) {
            return $item; // Re-queue to process in next batch
        }

        $result = my_plugin_process_single_item( $item );

        if ( is_wp_error( $result ) ) {
            error_log( 'my-plugin: import failed for item ' . $item['id'] . ': ' . $result->get_error_message() );
            // Store failure for reporting
            $failures   = get_option( 'my_plugin_import_failures', [] );
            $failures[] = [ 'id' => $item['id'], 'error' => $result->get_error_message() ];
            update_option( 'my_plugin_import_failures', array_slice( $failures, -100 ) ); // Keep last 100
        }

        return false; // Remove from queue
    }

    protected function complete() {
        parent::complete();
        $failures = get_option( 'my_plugin_import_failures', [] );
        update_option( 'my_plugin_import_complete', [
            'time'     => time(),
            'failures' => count( $failures ),
        ] );
        do_action( 'my_plugin_import_complete', $failures );
    }
}
```

## Fan-Out Pattern (Action Scheduler)

Queue a "dispatcher" action that creates per-item actions:

```php
// 1. Dispatcher — queued once
add_action( 'my_plugin_dispatch_import', function( $batch_size = 100 ) {
    $page  = 1;
    $total = 0;

    do {
        $items = get_posts( [
            'post_type'      => 'product',
            'posts_per_page' => $batch_size,
            'paged'          => $page,
            'fields'         => 'ids',
            'post_status'    => 'publish',
        ] );

        foreach ( $items as $item_id ) {
            as_enqueue_async_action( 'my_plugin_process_item', [ 'id' => $item_id ], 'my-plugin-import' );
        }

        $total += count( $items );
        $page++;
    } while ( count( $items ) === $batch_size );

    update_option( 'my_plugin_import_total', $total );
    update_option( 'my_plugin_import_queued', time() );
} );

// 2. Worker — runs once per item
add_action( 'my_plugin_process_item', function( $id ) {
    $result = my_plugin_sync_item( $id );
    if ( is_wp_error( $result ) ) {
        throw new \RuntimeException( $result->get_error_message() );
    }
    // Increment counter (atomic via option)
    $done = (int) get_option( 'my_plugin_import_done', 0 );
    update_option( 'my_plugin_import_done', $done + 1 );
} );

// Kick off
as_enqueue_async_action( 'my_plugin_dispatch_import', [], 'my-plugin-import' );
```

## Progress Polling via AJAX

```php
// Store progress in option
function my_plugin_get_progress(): array {
    $total = (int) get_option( 'my_plugin_import_total', 0 );
    $done  = (int) get_option( 'my_plugin_import_done', 0 );
    return [
        'total'      => $total,
        'done'       => $done,
        'percent'    => $total > 0 ? round( ( $done / $total ) * 100 ) : 0,
        'complete'   => $total > 0 && $done >= $total,
        'pending_as' => as_has_scheduled_action( 'my_plugin_process_item', null, 'my-plugin-import' ) ? 'yes' : 'no',
    ];
}

// AJAX handler
add_action( 'wp_ajax_my_plugin_import_progress', function() {
    check_ajax_referer( 'my_plugin_import' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
    wp_send_json_success( my_plugin_get_progress() );
} );

// JS polling
add_action( 'admin_footer', function() {
    if ( ! is_admin() ) return;
    ?>
    <script>
    (function() {
        const poll = setInterval(() => {
            fetch(ajaxurl + '?action=my_plugin_import_progress&_wpnonce=<?php echo wp_create_nonce( 'my_plugin_import' ); ?>')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const { total, done, percent, complete } = data.data;
                    document.getElementById('my-plugin-progress').style.width = percent + '%';
                    document.getElementById('my-plugin-count').textContent = done + ' / ' + total;
                    if (complete) {
                        clearInterval(poll);
                        document.getElementById('my-plugin-status').textContent = 'Complete!';
                    }
                });
        }, 2000);
    })();
    </script>
    <?php
} );
```

## Cancellation

```php
// Cancel all pending import actions
function my_plugin_cancel_import(): void {
    as_unschedule_all_actions( 'my_plugin_process_item', [], 'my-plugin-import' );
    as_unschedule_all_actions( 'my_plugin_dispatch_import', [], 'my-plugin-import' );
    delete_option( 'my_plugin_import_total' );
    delete_option( 'my_plugin_import_done' );
    delete_option( 'my_plugin_import_failures' );
}
```

## WP Cron Batch Pattern

For low-frequency maintenance tasks without AS:

```php
register_activation_hook( __FILE__, function() {
    wp_schedule_event( time(), 'hourly', 'my_plugin_cleanup_cron' );
} );
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'my_plugin_cleanup_cron' );
} );

add_action( 'my_plugin_cleanup_cron', function() {
    global $wpdb;
    $limit     = 500; // process max 500 rows per run
    $threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}my_plugin_log WHERE created_at < %s LIMIT %d",
            $threshold,
            $limit
        )
    );

    // If we hit the limit, there may be more — schedule immediate next run
    if ( $deleted === $limit ) {
        wp_schedule_single_event( time() + 60, 'my_plugin_cleanup_cron' );
    }
} );
```

## Memory and Time Checks (WP_Background_Process)

```php
protected function task( $item ) {
    // Built-in checks from WP_Background_Process parent class
    if ( $this->time_exceeded() ) {
        return $item; // Reschedule remaining items
    }

    if ( $this->memory_exceeded() ) {
        return $item; // Reschedule remaining items
    }

    // Manual check (if not using WP_Background_Process)
    if ( memory_get_usage() > ( ini_get( 'memory_limit' ) * 0.8 ) ) {
        return $item;
    }

    if ( ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) > 20 ) {
        return $item;
    }

    // Process item...
    return false;
}
```
