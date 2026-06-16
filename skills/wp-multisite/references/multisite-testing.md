# Multisite Testing Patterns

## PHPUnit Bootstrap for Multisite

```php
// bin/install-wp-tests.sh — add --multisite flag
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest true
#                                                                         ^^^^

// tests/bootstrap.php
define( 'WP_TESTS_MULTISITE', true );   // force multisite test mode
```

Or via phpunit.xml.dist:
```xml
<php>
  <env name="WP_TESTS_MULTISITE" value="1"/>
</php>
```

---

## Test Base Class — Multisite-Aware

```php
class Multisite_Test_Case extends WP_UnitTestCase {

    protected int $site_a;
    protected int $site_b;

    public function set_up(): void {
        parent::set_up();

        // Create two test sites (auto-deleted after each test)
        $this->site_a = self::factory()->blog->create( [ 'domain' => 'site-a.example.com' ] );
        $this->site_b = self::factory()->blog->create( [ 'domain' => 'site-b.example.com' ] );
    }
}
```

---

## Testing Data Isolation Between Sites

```php
public function test_options_do_not_leak_between_sites(): void {
    switch_to_blog( $this->site_a );
    update_option( 'my_plugin_setting', 'site-a-value' );
    restore_current_blog();

    switch_to_blog( $this->site_b );
    $val = get_option( 'my_plugin_setting' );
    restore_current_blog();

    $this->assertNotEquals( 'site-a-value', $val );
}

public function test_network_option_shared_across_sites(): void {
    update_network_option( null, 'my_plugin_global', 'shared-value' );

    switch_to_blog( $this->site_a );
    $from_a = get_network_option( null, 'my_plugin_global' );
    restore_current_blog();

    switch_to_blog( $this->site_b );
    $from_b = get_network_option( null, 'my_plugin_global' );
    restore_current_blog();

    $this->assertEquals( 'shared-value', $from_a );
    $this->assertEquals( 'shared-value', $from_b );
}
```

---

## Testing Network Activation Setup

```php
public function test_tables_created_on_all_sites_during_network_activation(): void {
    global $wpdb;

    // Simulate network activation
    my_plugin_activate( true );

    foreach ( [ $this->site_a, $this->site_b ] as $blog_id ) {
        switch_to_blog( $blog_id );
        $table = $wpdb->prefix . 'my_plugin_log';
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        restore_current_blog();

        $this->assertEquals( $table, $exists, "Table missing on site {$blog_id}" );
    }
}
```

---

## Testing New Site Hook

```php
public function test_new_site_gets_plugin_setup(): void {
    global $wpdb;

    // wp_initialize_site fires on blog creation
    $new_site_id = self::factory()->blog->create();

    switch_to_blog( $new_site_id );
    $table = $wpdb->prefix . 'my_plugin_log';
    $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
    restore_current_blog();

    $this->assertEquals( $table, $exists );
}
```

---

## Testing Super Admin Capability

```php
public function test_super_admin_can_access_network_page(): void {
    $super = self::factory()->user->create( [ 'role' => 'administrator' ] );
    grant_super_admin( $super );
    wp_set_current_user( $super );

    $this->assertTrue( current_user_can( 'manage_network' ) );
    $this->assertTrue( current_user_can( 'my_plugin_network_feature' ) );
}

public function test_site_admin_cannot_access_network_page(): void {
    $admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
    wp_set_current_user( $admin );

    $this->assertFalse( current_user_can( 'manage_network' ) );
}
```

---

## Gotchas

| Issue | Cause | Fix |
|-------|-------|-----|
| `switch_to_blog` not restored on test failure | `restore_current_blog()` in `tear_down` only — not in `finally` | Call `restore_current_blog()` in `tear_down()` unconditionally |
| `$wpdb->prefix` returns main site prefix | Test ran without `switch_to_blog` | Always switch before reading `$wpdb->prefix` |
| Factory-created sites not cleaned up | WP_UnitTestCase only cleans posts/users | Subclass and `wpmu_delete_blog( $id, true )` in `tear_down` |
| Network options persist between tests | `WP_UnitTestCase` doesn't reset sitemeta | `delete_network_option( null, 'key' )` in `tear_down` |
