# Multisite Development Patterns

## Detection

```php
is_multisite()                          // true if WP Multisite enabled
is_network_admin()                      // true if viewing /wp-admin/network/
is_main_site()                          // true if current site is the main site
is_main_site( $blog_id )               // check specific site
get_main_site_id()                      // ID of main site (replaces BLOG_ID_CURRENT_SITE)
get_current_blog_id()                   // current site ID
is_super_admin()                        // true if current user is network admin
is_super_admin( $user_id )             // check specific user
is_plugin_active_for_network( $plugin ) // network-activated?
```

## Site Switching

```php
// Always use try/finally to guarantee restore
function my_plugin_loop_sites( callable $callback ): void {
    $sites = get_sites( [ 'number' => 0, 'fields' => 'ids', 'deleted' => 0, 'archived' => 0 ] );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        try {
            $callback( $site_id );
        } catch ( \Throwable $e ) {
            error_log( "my-plugin: error on site {$site_id}: " . $e->getMessage() );
        } finally {
            restore_current_blog(); // ALWAYS runs
        }
    }
}
```

## Options

```php
// Per-site (stored in each site's options table)
get_option( 'key', $default )
update_option( 'key', $value )
delete_option( 'key' )

// Network-wide (stored in sitemeta)
get_network_option( null, 'key', $default )  // null = current network
update_network_option( null, 'key', $value )
delete_network_option( null, 'key' )

// Site metadata (per WP_Site record, WP 5.1+)
get_site_meta( $site_id, 'key', true )
update_site_meta( $site_id, 'key', $value )
delete_site_meta( $site_id, 'key' )
```

## get_sites() Query Parameters

```php
$sites = get_sites( [
    'network_id'  => get_current_network_id(),
    'number'      => 100,          // sites per page (0 = no limit — avoid on large networks)
    'offset'      => 0,
    'fields'      => 'ids',        // 'ids' (faster) or 'all' (WP_Site objects)
    'site__in'    => [ 1, 2, 3 ], // specific site IDs
    'site__not_in'=> [ 5 ],
    'domain'      => 'example.com',
    'path'        => '/',
    'archived'    => 0,            // 0 = not archived
    'deleted'     => 0,
    'spam'        => 0,
    'public'      => 1,
    'search'      => 'keyword',    // searches domain and path
    'orderby'     => 'registered', // 'id', 'blogname', 'domain', 'path', 'registered'
    'order'       => 'ASC',
] );
```

## Table Prefix Handling

```php
global $wpdb;

$wpdb->prefix        // current site's prefix (e.g. 'wp_2_' for site 2)
$wpdb->base_prefix   // main site prefix (e.g. 'wp_')

// Custom table per site
$table = $wpdb->prefix . 'my_plugin_data';         // e.g. wp_2_my_plugin_data

// Shared network table (one for all sites)
$table = $wpdb->base_prefix . 'my_plugin_network'; // e.g. wp_my_plugin_network
```

## Capabilities

```php
// Network-level caps (require super admin)
'manage_network'          // access network admin
'manage_sites'            // create/delete sites
'manage_network_users'    // manage all users
'manage_network_plugins'  // activate plugins network-wide
'manage_network_themes'   // manage themes network-wide
'manage_network_options'  // network settings

// Site-level caps (standard WP roles)
'manage_options'          // site administrator
'publish_posts'
// etc.

// Add custom network capability
add_filter( 'user_has_cap', function( $caps, $cap, $args, $user ) {
    if ( 'my_plugin_network_feature' === $cap ) {
        $caps['my_plugin_network_feature'] = is_super_admin( $user->ID );
    }
    return $caps;
}, 10, 4 );
```

## New Site Setup Hook

```php
// Fires when a new site is created (WP 5.1+, replaces wpmu_new_blog)
add_action( 'wp_initialize_site', function( WP_Site $new_site ) {
    if ( ! is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
        return; // Only run when network-activated
    }
    switch_to_blog( $new_site->blog_id );
    try {
        my_plugin_setup_site(); // Create tables, default options, etc.
    } finally {
        restore_current_blog();
    }
} );

// Fires when a site is deleted
add_action( 'wp_delete_site', function( WP_Site $old_site ) {
    switch_to_blog( $old_site->blog_id );
    try {
        my_plugin_teardown_site(); // Clean up tables, options
    } finally {
        restore_current_blog();
    }
} );
```

## Network Admin Menu

```php
// Add to network admin menu
add_action( 'network_admin_menu', function() {
    add_menu_page(
        __( 'My Plugin', 'my-plugin' ),
        __( 'My Plugin', 'my-plugin' ),
        'manage_network',
        'my-plugin-network',
        'my_plugin_network_page',
        'dashicons-admin-plugins',
        80
    );
} );

// Network admin notices
add_action( 'network_admin_notices', function() {
    if ( get_network_option( null, 'my_plugin_notice' ) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'Network notice.', 'my-plugin' ) . '</p></div>';
    }
} );
```

## User Roles on Multisite

```php
// User added to a specific site
add_action( 'add_user_to_blog', function( int $user_id, string $role, int $blog_id ) {
    // User $user_id added as $role to site $blog_id
}, 10, 3 );

// User removed from site
add_action( 'remove_user_from_blog', function( int $user_id, int $blog_id ) {}, 10, 2 );

// Check if user belongs to a site
is_user_member_of_blog( $user_id, $blog_id )

// Get all sites a user belongs to
$sites = get_blogs_of_user( $user_id );
```

## Activation Pattern (Network-Aware)

```php
register_activation_hook( __FILE__, function( bool $network_wide ) {
    if ( $network_wide && is_multisite() ) {
        $sites = get_sites( [ 'number' => 0, 'fields' => 'ids', 'deleted' => 0 ] );
        foreach ( $sites as $site_id ) {
            switch_to_blog( $site_id );
            try { my_plugin_activate_site(); } finally { restore_current_blog(); }
        }
    } else {
        my_plugin_activate_site();
    }
} );

register_deactivation_hook( __FILE__, function( bool $network_wide ) {
    if ( $network_wide && is_multisite() ) {
        $sites = get_sites( [ 'number' => 0, 'fields' => 'ids' ] );
        foreach ( $sites as $site_id ) {
            switch_to_blog( $site_id );
            try { my_plugin_deactivate_site(); } finally { restore_current_blog(); }
        }
    } else {
        my_plugin_deactivate_site();
    }
} );
```
