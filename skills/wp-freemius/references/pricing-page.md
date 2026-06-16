# Freemius Pricing & Checkout

## Pricing Page URLs

```php
// Link to default Freemius pricing page
$pricing_url = my_plugin_fs()->get_upgrade_url();

// Link to checkout for a specific plan
$checkout_url = my_plugin_fs()->get_plan_upgrade_url( 'professional' ); // plan slug

// Link to pricing page (not direct checkout)
$pricing_url  = my_plugin_fs()->pricing_url();

// Specific billing cycle
$monthly_url = add_query_arg( 'billing_cycle', 'monthly', my_plugin_fs()->get_upgrade_url() );
$annual_url  = add_query_arg( 'billing_cycle', 'annual',  my_plugin_fs()->get_upgrade_url() );
```

## Plan Configuration in fs_dynamic_init()

```php
$my_plugin_fs = fs_dynamic_init( [
    'id'                  => 'YOUR_PLUGIN_ID',
    'slug'                => 'my-plugin',
    'type'                => 'plugin',
    'public_key'          => 'pk_YOUR_PUBLIC_KEY',
    'is_premium'          => true,
    'premium_suffix'      => 'Pro',
    'has_addons'          => false,
    'has_paid_plans'      => true,

    // Plans shown on pricing page
    'plan'                => [
        [
            'name'        => 'free',
            'title'       => 'Free',
            'description' => 'Core features for everyone.',
            'is_free'     => true,
        ],
        [
            'name'        => 'professional',
            'title'       => 'Professional',
            'description' => 'Advanced features for power users.',
            'pricing'     => [
                [
                    'licenses'      => 1,
                    'annual_price'  => 49,
                    'monthly_price' => 5.9,
                ],
                [
                    'licenses'      => 5,
                    'annual_price'  => 99,
                    'monthly_price' => 9.9,
                ],
                [
                    'licenses'      => 25,
                    'annual_price'  => 199,
                ],
                [
                    'licenses'      => null, // unlimited
                    'annual_price'  => 299,
                ],
            ],
        ],
        [
            'name'        => 'enterprise',
            'title'       => 'Enterprise',
            'description' => 'White-label + priority support.',
            'pricing'     => [
                [
                    'licenses'     => null,
                    'annual_price' => 499,
                ],
            ],
        ],
    ],

    'menu'        => [
        'slug'    => 'my-plugin',
        'pricing' => true,       // adds Pricing submenu
        'support' => false,
    ],

    'is_org_compliant'          => true,
    'anonymous_mode_enabled'    => true,
] );
```

## Customize Checkout URL

```php
// Add coupon code
$url = add_query_arg( 'coupon', 'LAUNCH30', my_plugin_fs()->get_upgrade_url() );

// Pre-select billing cycle
$url = add_query_arg( [
    'plan'          => 'professional',
    'billing_cycle' => 'annual',
    'coupon'        => 'SUMMER',
], my_plugin_fs()->get_upgrade_url() );

// Checkout in trial mode
$url = add_query_arg( 'trial', 'true', my_plugin_fs()->get_upgrade_url() );
```

## Pricing Page CTA Button

```php
function my_plugin_upgrade_button( string $plan = '', string $context = 'settings' ): string {
    $fs  = my_plugin_fs();
    $url = $plan ? $fs->get_plan_upgrade_url( $plan ) : $fs->get_upgrade_url();

    return sprintf(
        '<a href="%s" class="button button-primary my-plugin-upgrade-btn" data-context="%s" target="_blank">%s</a>',
        esc_url( $url ),
        esc_attr( $context ),
        esc_html__( 'Upgrade to Pro', 'my-plugin' )
    );
}
```

## Trial Configuration

```php
$my_plugin_fs = fs_dynamic_init( [
    // ...
    'trial'                  => [
        'days'               => 14,
        'is_require_payment' => false, // true = credit card required
    ],
    // ...
] );

// Start trial programmatically
add_action( 'my_plugin_onboard_complete', function() {
    if ( my_plugin_fs()->is_registered() && ! my_plugin_fs()->is_trial() ) {
        my_plugin_fs()->start_trial( 'professional' );
    }
} );
```

## Hooks

```php
// After upgrade
my_plugin_fs()->add_action( 'after_premium_version_activation', function() {
    // E.g. redirect to welcome page
    wp_redirect( admin_url( 'admin.php?page=my-plugin&upgraded=1' ) );
    exit;
} );

// After downgrade
my_plugin_fs()->add_action( 'after_free_version_reactivation', function() {
    // Clean up premium-only data if needed
} );

// After trial starts
my_plugin_fs()->add_action( 'after_trial_start', function() {
    update_option( 'my_plugin_trial_started', time() );
} );

// After trial ends
my_plugin_fs()->add_action( 'after_trial_end', function() {
    // Notify admin
    wp_mail( get_option( 'admin_email' ), 'Trial ended', 'Upgrade to keep Pro features.' );
} );
```

## Affiliate / Referral URLs

```php
// Add affiliate parameter (if using Freemius Affiliates)
$url = add_query_arg( 'aff', 'AFFILIATE_ID', my_plugin_fs()->get_upgrade_url() );
```

## Freemius Dashboard Reset (dev/testing)

```bash
# WP-CLI — reset Freemius SDK state (reverts to anonymous free user)
wp eval "my_plugin_fs()->reset_anonymous_mode();"

# Clear all Freemius options
wp eval "my_plugin_fs()->delete_account_entities_and_options_of_all_sites( true );"

# Deactivate license
wp eval "my_plugin_fs()->deactivate_license();"
```
