# Freemius Feature Gating Patterns

## Basic Gating

```php
// Always check via the singleton getter
function my_plugin_fs(): Freemius {
    global $my_plugin_fs;
    return $my_plugin_fs;
}

// Gate a feature
if ( my_plugin_fs()->can_use_premium_code() ) {
    // Load premium class or run premium logic
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-premium.php';
    My_Plugin_Premium::init();
}
```

## Admin UI Gating

```php
// Show upgrade notice for free users
add_action( 'my_plugin_settings_section', function() {
    if ( my_plugin_fs()->can_use_premium_code() ) {
        // Show premium settings
        my_plugin_render_premium_settings();
    } else {
        // Show upgrade CTA
        $upgrade_url = my_plugin_fs()->get_upgrade_url();
        ?>
        <div class="my-plugin-upgrade-notice">
            <p><?php esc_html_e( 'This feature requires My Plugin Pro.', 'my-plugin' ); ?></p>
            <a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary" target="_blank">
                <?php esc_html_e( 'Upgrade to Pro', 'my-plugin' ); ?>
            </a>
        </div>
        <?php
    }
} );
```

## Plan-Specific Gating

```php
// Gate by plan tier
function my_plugin_can_use_advanced_feature(): bool {
    $fs = my_plugin_fs();
    if ( ! $fs->can_use_premium_code() ) return false;

    // 'professional' plan or higher
    return $fs->is_plan( 'professional', true );
}

// Display plan badge
function my_plugin_plan_badge(): string {
    $fs = my_plugin_fs();
    if ( $fs->is_plan( 'enterprise', true ) ) return 'Enterprise';
    if ( $fs->is_plan( 'professional', true ) ) return 'Pro';
    if ( $fs->is_trial() ) return 'Trial';
    return 'Free';
}
```

## __premium_only__ File Pattern

Files ending in `__premium_only__` are stripped from the free zip by Freemius SDK tools.

```
my-plugin/
├── includes/
│   ├── class-core.php                            # Always loaded
│   ├── class-analytics.php__premium_only__      # Premium only
│   └── class-export.php__premium_only__         # Premium only
└── my-plugin.php
```

```php
// In my-plugin.php — safe to include (file only exists in premium build)
if ( my_plugin_fs()->is__premium_only() ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-analytics.php__premium_only__';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-export.php__premium_only__';
}

// Or use method shorthand:
if ( my_plugin_fs()->can_use_premium_code() ) {
    // This file won't exist in free build, so require_once is safe
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-analytics.php__premium_only__';
}
```

## API/AJAX Endpoint Gating

```php
add_action( 'wp_ajax_my_plugin_export', function() {
    check_ajax_referer( 'my_plugin_export' );

    if ( ! my_plugin_fs()->can_use_premium_code() ) {
        wp_send_json_error( [
            'code'        => 'premium_required',
            'message'     => __( 'Export requires My Plugin Pro.', 'my-plugin' ),
            'upgrade_url' => my_plugin_fs()->get_upgrade_url(),
        ], 403 );
    }

    // Process export...
    wp_send_json_success( [ 'file' => $export_url ] );
} );
```

## REST API Endpoint Gating

```php
register_rest_route( 'my-plugin/v1', '/export', [
    'methods'             => 'POST',
    'callback'            => 'my_plugin_rest_export',
    'permission_callback' => function( WP_REST_Request $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'rest_forbidden', __( 'Insufficient permissions.', 'my-plugin' ), [ 'status' => 403 ] );
        }
        if ( ! my_plugin_fs()->can_use_premium_code() ) {
            return new WP_Error( 'premium_required', __( 'This endpoint requires My Plugin Pro.', 'my-plugin' ), [ 'status' => 402 ] );
        }
        return true;
    },
] );
```

## Trial Experience

```php
// Show trial-specific messaging
add_action( 'admin_notices', function() {
    $fs = my_plugin_fs();
    if ( ! $fs->is_trial() ) return;

    $expiration = $fs->get_trial_plan()->expiration_date; // WC_DateTime
    $days_left  = max( 0, ceil( ( strtotime( $expiration ) - time() ) / DAY_IN_SECONDS ) );

    if ( $days_left <= 3 ) {
        ?>
        <div class="notice notice-warning">
            <p>
                <?php
                /* translators: %d: number of days remaining in trial */
                printf( esc_html( _n( 'Your My Plugin Pro trial expires in %d day.', 'Your My Plugin Pro trial expires in %d days.', $days_left, 'my-plugin' ) ), $days_left );
                ?>
                <a href="<?php echo esc_url( $fs->get_upgrade_url() ); ?>"><?php esc_html_e( 'Upgrade now to keep Pro features.', 'my-plugin' ); ?></a>
            </p>
        </div>
        <?php
    }
} );
```

## WP.org Compliance Checklist

Per WP.org Guideline 5 (no trialware), when using Freemius on WP.org:

- [ ] `is_org_compliant => true` in `fs_dynamic_init()`
- [ ] `anonymous_mode_enabled => true` (allow skip opt-in)
- [ ] Free version fully functional without license
- [ ] No feature locked on activation without license
- [ ] Admin deactivation not blocked
- [ ] Upgrade notices are dismissible
- [ ] No persistent full-page upsell flows

```php
// Correct: features available, upgrade is optional
if ( my_plugin_fs()->can_use_premium_code() ) {
    $limit = PHP_INT_MAX; // Pro: unlimited
} else {
    $limit = 5;           // Free: 5 items (functional, not locked)
}

// Wrong: feature completely unavailable in free
if ( ! my_plugin_fs()->can_use_premium_code() ) {
    wp_die( 'Purchase required.' ); // Violates Guideline 5
}
```
