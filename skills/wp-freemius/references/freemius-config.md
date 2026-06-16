# Freemius SDK Configuration Reference

Full `fs_dynamic_init()` parameter reference and common configuration examples.

## Complete Configuration Options

```php
$fs = fs_dynamic_init( [
    // --- Required ---
    'id'          => '12345',           // Plugin ID from freemius.com dashboard
    'slug'        => 'my-plugin',       // WP.org slug / directory name
    'type'        => 'plugin',          // 'plugin' or 'theme'
    'public_key'  => 'pk_abc123...',    // Public key (safe to commit)

    // --- Version type ---
    'is_premium'         => false,      // true if THIS is the premium build
    'has_premium_version'=> true,       // true if a premium version exists
    'is_premium_only'    => false,      // true if plugin is never free

    // --- Features ---
    'has_addons'     => false,          // true if selling add-ons
    'has_paid_plans' => true,           // false for purely free plugins
    'has_affiliation'=> 'selected',     // false | 'selected' | 'all'

    // --- Trial ---
    'trial' => [
        'days'               => 14,     // trial length in days
        'is_require_payment' => false,  // true = credit card required
    ],

    // --- Opt-in ---
    'is_org_compliant'           => true,  // REQUIRED for WP.org plugins
    'anonymous_mode_enabled'     => true,  // allow skipping opt-in

    // --- Menu ---
    'menu' => [
        'slug'       => 'my-plugin',    // parent menu slug
        'first-path' => 'settings',     // first admin screen (settings | pricing | contact)
        'contact'    => false,          // show Contact menu item
        'support'    => false,          // show Support menu item
        'pricing'    => true,           // show Pricing menu item
        'affiliation'=> false,          // show Affiliates menu item
        'network'    => true,           // show in network admin (multisite)
    ],

    // --- Licensing ---
    'license_key_grace_period' => 7,    // days after expiry before features lock
    'network_key_type'         => 'per-site', // 'per-site' | 'per-domain' | 'unlimited'

    // --- Updates ---
    'bundle_id'         => null,        // if part of a bundle
    'bundle_public_key' => null,

    // --- Uninstall survey ---
    'is_uninstall_url_active' => true,  // show "why are you uninstalling?" page
] );
```

## Minimal Free Plugin Config (WP.org compliant)

```php
$fs = fs_dynamic_init( [
    'id'                  => '12345',
    'slug'                => 'my-plugin',
    'type'                => 'plugin',
    'public_key'          => 'pk_abc123...',
    'is_premium'          => false,
    'has_premium_version' => false,
    'has_paid_plans'      => false,
    'is_org_compliant'    => true,
    'menu'                => [ 'slug' => 'my-plugin', 'contact' => false, 'support' => false ],
] );
```

## Freemium Plugin Config (WP.org compliant)

```php
$fs = fs_dynamic_init( [
    'id'                       => '12345',
    'slug'                     => 'my-plugin',
    'type'                     => 'plugin',
    'public_key'               => 'pk_abc123...',
    'is_premium'               => false,
    'has_premium_version'      => true,
    'has_paid_plans'           => true,
    'is_org_compliant'         => true,    // MUST be true for WP.org
    'anonymous_mode_enabled'   => true,    // MUST allow skip for WP.org
    'trial'                    => [ 'days' => 14, 'is_require_payment' => false ],
    'menu'                     => [
        'slug'       => 'my-plugin',
        'first-path' => 'settings',
        'contact'    => false,
        'support'    => false,
        'pricing'    => true,
    ],
] );
```

## Feature Gating API

```php
$fs = my_plugin_fs(); // get singleton

// Has any paid plan or active trial
$fs->can_use_premium_code()

// Has active paid license (not expired, not trial-only)
$fs->is_paying()

// Has active trial
$fs->is_trial()

// Has expired license
$fs->is_license_expired()

// Has license (active or expired)
$fs->is_licensed()

// Specific plan check
$fs->is_plan( 'professional' )        // exact match
$fs->is_plan( 'professional', true )  // professional or higher

// Plan name of active license
$fs->get_plan_name() // e.g. 'professional'

// Pricing URL (Freemius-hosted page)
$fs->get_upgrade_url()
$fs->get_upgrade_url( 'professional' ) // link to specific plan

// User info
$fs->get_user()->id
$fs->get_user()->email
$fs->get_user()->is_verified()
```

## Hooks Reference

```php
// After Freemius loaded and initialised
add_action( 'my_plugin_fs_loaded', function() {
    // Safe to call $fs->* here
} );

// Before opt-in dialog displayed
add_filter( 'my_plugin_fs_connect_url', function( string $url ): string {
    return $url;
} );

// After user opts in
add_action( 'my_plugin_fs_after_connect_with_user', function() {
    $user = my_plugin_fs()->get_user();
    // Welcome email, setup wizard, etc.
} );

// After license activated
add_action( 'my_plugin_fs_after_license_loaded', function() {
    // Unlock premium features, activate pro settings
    my_plugin_activate_premium_features();
} );

// After license deactivated
add_action( 'my_plugin_fs_after_license_deactivation', function() {
    my_plugin_deactivate_premium_features();
} );

// Uninstall
add_action( 'my_plugin_fs_before_uninstall', function() {
    // Custom cleanup before Freemius sends uninstall data
} );
```

## Reset / Debug Commands

```php
// Reset Freemius state for testing (development only)
// Add temporarily to plugin, load once, then remove
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'init', function() {
        if ( isset( $_GET['fs_reset'] ) && current_user_can( 'manage_options' ) ) {
            my_plugin_fs()->reset();
            wp_redirect( admin_url() );
            exit;
        }
    } );
}
```

```bash
# Delete all Freemius data via WP option
wp option delete fs_accounts

# Or target specific plugin
wp option get fs_accounts
# Find your plugin's key and delete just that entry
```

## Pricing Page Embed

```php
// Embedded pricing page in WP admin
function my_plugin_pricing_page() {
    echo my_plugin_fs()->get_pricing_js_tag( true ); // true = disable checkout button
}

// Or redirect to Freemius-hosted page
function my_plugin_pricing_page() {
    wp_redirect( my_plugin_fs()->get_upgrade_url() );
    exit;
}
```
