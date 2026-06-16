# Multisite Network Admin Patterns

## Network Activation vs Per-Site Activation

```php
// Detect network activation
register_activation_hook( __FILE__, function( bool $network_wide ) {
    if ( $network_wide && is_multisite() ) {
        // Network-activated: run setup on all current sites
        $sites = get_sites( [ 'number' => 0, 'fields' => 'ids' ] );
        foreach ( $sites as $blog_id ) {
            switch_to_blog( $blog_id );
            my_plugin_create_tables();
            restore_current_blog();
        }
    } else {
        // Single-site activation
        my_plugin_create_tables();
    }
} );

// Also run setup when a new site is created
add_action( 'wp_initialize_site', function( WP_Site $site ) {
    if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
        switch_to_blog( (int) $site->blog_id );
        my_plugin_create_tables();
        restore_current_blog();
    }
} );

// Cleanup when site is deleted
add_action( 'wp_delete_site', function( WP_Site $site ) {
    switch_to_blog( (int) $site->blog_id );
    my_plugin_drop_tables();
    restore_current_blog();
} );
```

## Network Admin Menu

```php
// Add menu page under Network Admin > Plugins
add_action( 'network_admin_menu', function() {
    add_menu_page(
        __( 'My Plugin Network', 'my-plugin' ),
        __( 'My Plugin', 'my-plugin' ),
        'manage_network_options',       // capability
        'my-plugin-network',
        'my_plugin_network_admin_page',
        'dashicons-admin-plugins',
        30
    );

    add_submenu_page(
        'my-plugin-network',
        __( 'Network Settings', 'my-plugin' ),
        __( 'Settings', 'my-plugin' ),
        'manage_network_options',
        'my-plugin-network-settings',
        'my_plugin_network_settings_page'
    );
} );

// Network admin settings save (uses different nonce action)
add_action( 'network_admin_edit_my_plugin_network_settings', function() {
    check_admin_referer( 'my_plugin_network_settings-options' );
    if ( ! current_user_can( 'manage_network_options' ) ) wp_die( -1 );

    update_site_option( 'my_plugin_network_setting', sanitize_text_field( wp_unslash( $_POST['my_plugin_network_setting'] ?? '' ) ) );

    // Redirect back with updated flag
    wp_redirect( add_query_arg( 'updated', 'true', network_admin_url( 'admin.php?page=my-plugin-network-settings' ) ) );
    exit;
} );
```

## Network Settings API

```php
// Network options (stored in wp_sitemeta)
get_site_option( 'my_plugin_key', 'default_value' );
update_site_option( 'my_plugin_key', $value );
delete_site_option( 'my_plugin_key' );

// Per-site options (stored in wp_N_options)
// Use inside switch_to_blog() or blog-specific context
get_option( 'my_plugin_key', 'default_value' );
update_option( 'my_plugin_key', $value );

// Allow per-site override of network setting
function my_plugin_get_setting( string $key, mixed $default = '' ): mixed {
    // Per-site value takes precedence if it exists
    $per_site = get_option( "my_plugin_{$key}", null );
    if ( null !== $per_site ) {
        return $per_site;
    }
    return get_site_option( "my_plugin_{$key}", $default );
}
```

## Network Admin Action Links

```php
// Add links on Network Admin > Plugins page
add_filter( 'network_admin_plugin_action_links_' . plugin_basename( __FILE__ ), function( array $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( network_admin_url( 'admin.php?page=my-plugin-network-settings' ) ),
        esc_html__( 'Network Settings', 'my-plugin' )
    );
    array_unshift( $links, $settings_link );
    return $links;
} );
```

## Bulk Site Operations

```php
function my_plugin_run_on_all_sites( callable $callback ): array {
    $results = [];
    $paged   = 1;

    do {
        $sites = get_sites( [
            'number'  => 100,
            'paged'   => $paged,
            'fields'  => 'ids',
            'deleted' => 0,
            'spam'    => 0,
            'archived'=> 0,
        ] );

        foreach ( $sites as $blog_id ) {
            switch_to_blog( $blog_id );
            try {
                $results[ $blog_id ] = $callback( $blog_id );
            } catch ( \Throwable $e ) {
                $results[ $blog_id ] = new \WP_Error( 'callback_failed', $e->getMessage() );
            } finally {
                restore_current_blog();
            }
        }

        $paged++;
    } while ( count( $sites ) === 100 );

    return $results;
}

// Example: clear a transient on every site
my_plugin_run_on_all_sites( function( int $blog_id ) {
    delete_transient( 'my_plugin_cache' );
} );
```

## Super Admin Checks

```php
// Check if user is super admin
if ( is_super_admin() ) { /* full network access */ }
if ( is_super_admin( $user_id ) ) { /* check specific user */ }

// Check capability on current site
current_user_can( 'manage_network_options' )  // network-wide settings
current_user_can( 'manage_network_plugins' )  // activate/deactivate network-wide
current_user_can( 'manage_sites' )            // create/delete sites

// Grant capability only to super admins
add_filter( 'map_meta_cap', function( array $caps, string $cap, int $user_id ) {
    if ( 'my_plugin_network_manage' === $cap && ! is_super_admin( $user_id ) ) {
        $caps[] = 'do_not_allow';
    }
    return $caps;
}, 10, 3 );
```

## Network-aware Cron

```php
// Schedule once on main site; work iterates over all sites
add_action( 'init', function() {
    if ( is_main_site() && ! wp_next_scheduled( 'my_plugin_network_sync' ) ) {
        wp_schedule_event( time(), 'daily', 'my_plugin_network_sync' );
    }
} );

add_action( 'my_plugin_network_sync', function() {
    $sites = get_sites( [ 'number' => 0, 'fields' => 'ids' ] );
    foreach ( $sites as $blog_id ) {
        switch_to_blog( $blog_id );
        try {
            my_plugin_sync_site( $blog_id );
        } finally {
            restore_current_blog();
        }
    }
} );
```
