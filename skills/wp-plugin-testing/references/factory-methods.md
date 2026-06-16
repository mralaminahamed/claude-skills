# WP Test Factory Methods

`self::factory()` returns a `WP_UnitTest_Factory` object with sub-factories for each object type. All created objects are rolled back after each test.

## Post Factory

```php
// Create one post (returns post ID)
$post_id = self::factory()->post->create( [
    'post_title'   => 'My Post',
    'post_content' => 'Content here.',
    'post_status'  => 'publish',
    'post_type'    => 'post',
    'post_author'  => $user_id,
    'post_date'    => '2024-01-15 10:00:00',
    'post_excerpt' => 'Excerpt text.',
    'menu_order'   => 1,
] );

// Create and get WP_Post object
$post = self::factory()->post->create_and_get( [ 'post_title' => 'Title' ] );
// $post->ID, $post->post_title, etc.

// Create multiple posts (returns array of IDs)
$post_ids = self::factory()->post->create_many( 5 );
$post_ids = self::factory()->post->create_many( 3, [ 'post_status' => 'draft' ] );

// Custom post type
$cpt_id = self::factory()->post->create( [ 'post_type' => 'product' ] );
```

## User Factory

```php
// Create user (returns user ID)
$user_id = self::factory()->user->create( [
    'role'       => 'editor',
    'user_login' => 'testuser',
    'user_email' => 'test@example.com',
    'user_pass'  => 'password',
] );

// Create and get WP_User object
$user = self::factory()->user->create_and_get( [ 'role' => 'subscriber' ] );

// Create many
$user_ids = self::factory()->user->create_many( 3, [ 'role' => 'contributor' ] );

// Set as current user
wp_set_current_user( $user_id );

// Roles available: subscriber, contributor, author, editor, administrator
```

## Term Factory

```php
// Create term (returns term ID or WP_Error)
$term_id = self::factory()->term->create( [
    'taxonomy'    => 'category',
    'name'        => 'News',
    'slug'        => 'news',
    'description' => 'Latest news.',
    'parent'      => 0,
] );

// Create and get WP_Term object
$term = self::factory()->term->create_and_get( [ 'taxonomy' => 'post_tag', 'name' => 'WordPress' ] );

// Create many
$term_ids = self::factory()->term->create_many( 5, [ 'taxonomy' => 'category' ] );

// Assign term to post
wp_set_post_terms( $post_id, [ $term_id ], 'category' );
```

## Comment Factory

```php
// Create comment (returns comment ID)
$comment_id = self::factory()->comment->create( [
    'comment_post_ID'  => $post_id,
    'comment_content'  => 'Great post!',
    'comment_author'   => 'Test Author',
    'comment_author_email' => 'author@example.com',
    'comment_approved' => 1,
    'user_id'          => $user_id,
] );

// Create and get
$comment = self::factory()->comment->create_and_get( [ 'comment_post_ID' => $post_id ] );

// Create many comments on a post
$comment_ids = self::factory()->comment->create_many( 10, [ 'comment_post_ID' => $post_id ] );
```

## Attachment Factory

```php
// Create attachment from local file
$attachment_id = self::factory()->attachment->create_upload_object(
    DIR_TESTDATA . '/images/test-image.jpg',  // path to test image
    $post_id                                   // parent post (0 for none)
);

// Basic attachment (no actual file)
$attachment_id = self::factory()->attachment->create( [
    'post_title'     => 'My Image',
    'post_mime_type' => 'image/jpeg',
    'post_status'    => 'inherit',
    'post_parent'    => $post_id,
] );
```

## Multisite Factories

```php
// Only available when is_multisite()

// Create site
$blog_id = self::factory()->blog->create( [
    'domain' => 'example.com',
    'path'   => '/new-site/',
    'title'  => 'New Site',
] );

// Create and get WP_Site object
$site = self::factory()->blog->create_and_get( [] );

// Create many sites
$blog_ids = self::factory()->blog->create_many( 3 );

// Network factory
$network_id = self::factory()->network->create( [
    'domain' => 'mynetwork.com',
    'path'   => '/',
] );
```

## Meta Setup Patterns

```php
// Post with meta
$post_id = self::factory()->post->create( [ 'post_title' => 'With Meta' ] );
update_post_meta( $post_id, '_my_key', 'my_value' );

// User with meta
$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
update_user_meta( $user_id, 'my_preference', 'dark' );

// Term with meta
$term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
update_term_meta( $term_id, 'term_icon', 'dashicons-tag' );
```

## Complete Test Setup Pattern

```php
class Test_My_Feature extends WP_UnitTestCase {

    protected int $admin_id;
    protected int $editor_id;
    protected int $post_id;
    protected int $category_id;

    public function setUp(): void {
        parent::setUp();

        $this->admin_id    = self::factory()->user->create( [ 'role' => 'administrator' ] );
        $this->editor_id   = self::factory()->user->create( [ 'role' => 'editor' ] );
        $this->category_id = self::factory()->term->create( [ 'taxonomy' => 'category', 'name' => 'Test' ] );
        $this->post_id     = self::factory()->post->create( [
            'post_title'    => 'Test Post',
            'post_status'   => 'publish',
            'post_author'   => $this->admin_id,
            'post_category' => [ $this->category_id ],
        ] );

        wp_set_current_user( $this->admin_id );
    }

    // No tearDown needed — WP_UnitTestCase rolls back all DB changes
}
```

## Available Test Data (DIR_TESTDATA)

WP test suite ships sample files at `/tmp/wordpress-tests-lib/data/`:

```
images/
├── test-image.jpg
├── test-image.png
├── test-image.gif
└── canola.jpg
export/
├── wp-export-test.xml
└── ...
```

Reference as `DIR_TESTDATA . '/images/test-image.jpg'` in tests.
