# Action Scheduler API Reference

Action Scheduler ships with WooCommerce (4.0+) and as a standalone Composer package.

## Scheduling Functions

```php
// Async (runs ASAP, once)
$action_id = as_enqueue_async_action(
    'hook_name',          // string: the hook to fire
    [ 'arg1', 'arg2' ],  // array: arguments passed to the hook
    'my-plugin'           // string: group name (for filtering in dashboard)
);

// Single future action
$action_id = as_schedule_single_action(
    time() + HOUR_IN_SECONDS,  // int: timestamp to run
    'hook_name',
    [ 'key' => 'value' ],
    'my-plugin'
);

// Recurring action
$action_id = as_schedule_recurring_action(
    time(),              // int: first run timestamp
    DAY_IN_SECONDS,      // int: interval in seconds
    'hook_name',
    [],
    'my-plugin'
);

// Cron-expression action
$action_id = as_schedule_cron_action(
    time(),
    '0 8 * * 1',        // cron expression: every Monday at 08:00
    'hook_name',
    [],
    'my-plugin'
);
```

## Query Functions

```php
// Check if action is scheduled (returns bool)
$scheduled = as_has_scheduled_action( 'hook_name', [ 'arg' ], 'my-plugin' );

// Get next scheduled timestamp
$timestamp = as_next_scheduled_action( 'hook_name', [ 'arg' ], 'my-plugin' );

// Get all scheduled actions
$actions = as_get_scheduled_actions( [
    'hook'     => 'hook_name',
    'group'    => 'my-plugin',
    'status'   => ActionScheduler_Store::STATUS_PENDING, // PENDING, COMPLETE, FAILED, CANCELED, IN_PROGRESS
    'per_page' => 20,
    'offset'   => 0,
    'orderby'  => 'date',
    'order'    => 'ASC',
] );
```

## Cancel Functions

```php
// Cancel next scheduled occurrence
as_unschedule_action( 'hook_name', [ 'arg' ], 'my-plugin' );

// Cancel ALL scheduled occurrences (including recurring)
as_unschedule_all_actions( 'hook_name', [ 'arg' ], 'my-plugin' );

// Cancel all actions in a group
as_unschedule_all_actions( '', [], 'my-plugin' );
```

## Status Constants

```php
ActionScheduler_Store::STATUS_PENDING    // = 'pending'
ActionScheduler_Store::STATUS_COMPLETE   // = 'complete'
ActionScheduler_Store::STATUS_FAILED     // = 'failed'
ActionScheduler_Store::STATUS_CANCELED   // = 'canceled'
ActionScheduler_Store::STATUS_IN_PROGRESS // = 'in-progress'
```

## Exception Handling and Retry

Action Scheduler retries failed actions. Defaults: 3 attempts, 1 minute between retries.

```php
add_action( 'my_plugin_process_item', function( $item_id ) {
    $response = wp_remote_get( "https://api.example.com/item/{$item_id}" );

    if ( is_wp_error( $response ) ) {
        // Throw to trigger retry
        throw new \RuntimeException( 'API error: ' . $response->get_error_message() );
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        // Permanent failure — don't retry
        throw new \ActionScheduler_InvalidActionException( 'Bad response: ' . wp_remote_retrieve_response_code( $response ) );
    }

    // Process...
} );
```

**Configure retries:**
```php
add_filter( 'action_scheduler_failure_period', fn() => 5 * MINUTE_IN_SECONDS );
add_filter( 'action_scheduler_retry_attempts', fn() => 5 );
```

## Logging

```php
add_action( 'action_scheduler_after_execute', function( $action_id, $action ) {
    $logger = ActionScheduler_Logger::instance();
    $logger->log( $action_id, 'Custom log message' );
}, 10, 2 );
```

## WP-CLI Commands

```bash
# List scheduled actions
wp action-scheduler list --group=my-plugin --status=pending --format=table

# Run pending actions immediately (useful in tests/CI)
wp action-scheduler run --group=my-plugin
wp action-scheduler run --hooks=my_plugin_process_item

# Run all pending actions
wp action-scheduler run --force

# Show action details
wp action-scheduler get <action_id>

# Cancel actions
wp action-scheduler cancel --hook=my_plugin_old_hook
```

## Admin Dashboard

WooCommerce → Status → Scheduled Actions

Columns: Hook | Group | Arguments | Status | Next Run | Run Count

Filter by group (`my-plugin`) to see only your plugin's actions.

## Standalone (without WooCommerce)

```php
// composer.json
// "woocommerce/action-scheduler": "^3.7"

// Main plugin file
add_action( 'plugins_loaded', function() {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
} );
```

AS initialises itself — no further setup needed.
