# WP Cron Intervals & Debugging

## Built-in Intervals

| Key | Interval |
|---|---|
| `hourly` | Every hour |
| `twicedaily` | Twice a day |
| `daily` | Once a day |
| `weekly` | Once a week (WP 5.4+) |

## Register Custom Intervals

```php
add_filter( 'cron_schedules', function( array $schedules ) {
    $schedules['every_5_minutes'] = [
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display'  => __( 'Every 5 Minutes', 'my-plugin' ),
    ];
    $schedules['every_15_minutes'] = [
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => __( 'Every 15 Minutes', 'my-plugin' ),
    ];
    $schedules['every_30_minutes'] = [
        'interval' => 30 * MINUTE_IN_SECONDS,
        'display'  => __( 'Every 30 Minutes', 'my-plugin' ),
    ];
    $schedules['twice_weekly'] = [
        'interval' => 3.5 * DAY_IN_SECONDS,
        'display'  => __( 'Twice Weekly', 'my-plugin' ),
    ];
    return $schedules;
} );
```

## Register / Deregister Events

```php
// Schedule on activation
register_activation_hook( __FILE__, function() {
    if ( ! wp_next_scheduled( 'my_plugin_cron_hook' ) ) {
        wp_schedule_event( time(), 'hourly', 'my_plugin_cron_hook' );
    }
} );

// Clear on deactivation (not uninstall — keeps data)
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'my_plugin_cron_hook' );
} );

// Clear with args (only clears events with matching args)
wp_clear_scheduled_hook( 'my_plugin_cron_hook', [ 'arg1', 'arg2' ] );

// Single event (one-shot, not recurring)
wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'my_plugin_send_report', [ $user_id ] );

// Remove a specific single event
$timestamp = wp_next_scheduled( 'my_plugin_send_report', [ $user_id ] );
if ( $timestamp ) {
    wp_unschedule_event( $timestamp, 'my_plugin_send_report', [ $user_id ] );
}
```

## Cron Callback

```php
add_action( 'my_plugin_cron_hook', function() {
    // Check not already running (simple mutex via transient)
    if ( get_transient( 'my_plugin_cron_running' ) ) {
        return; // Previous run still in progress
    }

    set_transient( 'my_plugin_cron_running', true, 5 * MINUTE_IN_SECONDS );

    try {
        my_plugin_do_scheduled_work();
    } catch ( \Throwable $e ) {
        error_log( 'my-plugin cron error: ' . $e->getMessage() );
    } finally {
        delete_transient( 'my_plugin_cron_running' );
    }
} );
```

## Query Scheduled Events

```php
// Next run time (Unix timestamp or false)
$next = wp_next_scheduled( 'my_plugin_cron_hook' );
if ( $next ) {
    echo 'Next run: ' . date( 'Y-m-d H:i:s', $next );
}

// Next run with args
$next = wp_next_scheduled( 'my_plugin_cron_hook', [ 'arg1' ] );

// All scheduled events
$events = wp_get_scheduled_event( 'my_plugin_cron_hook' );
// Returns: (object){ hook, args, timestamp, schedule, interval } or false

// List all cron jobs
$crons = _get_cron_array();
foreach ( $crons as $timestamp => $hooks ) {
    foreach ( $hooks as $hook => $events ) {
        foreach ( $events as $event ) {
            echo "{$hook} @ " . date( 'Y-m-d H:i:s', $timestamp ) . "\n";
        }
    }
}
```

## WP-CLI Cron Commands

```bash
# List all scheduled events
wp cron event list

# List with next run time and interval
wp cron event list --fields=hook,next_run_relative,schedule

# Run a specific event now (bypasses schedule)
wp cron event run my_plugin_cron_hook

# Run ALL due events
wp cron event run --due-now

# Delete a scheduled event
wp cron event delete my_plugin_cron_hook

# Test cron system
wp cron test

# List cron schedules
wp cron schedule list
```

## Debugging Late or Missed Crons

WP Cron is pseudo-cron — only fires when someone visits the site. If the site has low traffic, events run late.

**Symptoms:**
- `wp_next_scheduled()` returns a past timestamp but event didn't run
- Events consistently fire late
- `wp cron event list` shows many overdue events

**Diagnosis:**

```bash
# Check for blocked cron
wp cron test
# "Success: WP-Cron spawning is working as expected."

# Check DISABLE_WP_CRON
wp eval "echo defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'disabled' : 'enabled';"

# Check ALTERNATE_WP_CRON
wp eval "echo defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON ? 'yes' : 'no';"
```

**Fix 1: Real cron via server cron tab**

```bash
# crontab -e
*/5 * * * * wget -q -O - https://example.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
# or
*/5 * * * * php /var/www/html/wp-cron.php > /dev/null 2>&1
# or (preferred)
*/5 * * * * wp --path=/var/www/html cron event run --due-now > /dev/null 2>&1
```

Then disable built-in spawn:
```php
// wp-config.php
define( 'DISABLE_WP_CRON', true );
```

**Fix 2: Alternate cron (defers to redirect instead of loopback HTTP)**

```php
// wp-config.php
define( 'ALTERNATE_WP_CRON', true );
```

Use when the server blocks loopback HTTP requests (common on some hosting).

## Time Constants

```php
MINUTE_IN_SECONDS  // 60
HOUR_IN_SECONDS    // 3600
DAY_IN_SECONDS     // 86400
WEEK_IN_SECONDS    // 604800
MONTH_IN_SECONDS   // 2592000
YEAR_IN_SECONDS    // 31536000
```
