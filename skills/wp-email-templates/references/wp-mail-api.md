# WordPress Mail API Reference

## wp_mail()

```php
wp_mail(
    string|string[] $to,        // recipient email(s)
    string          $subject,   // email subject
    string          $message,   // email body
    string|string[] $headers,   // optional headers
    string|string[] $attachments // optional file paths
): bool
```

### Basic usage

```php
// Plain text
wp_mail( 'user@example.com', 'Hello', 'Plain text body.' );

// HTML email
add_filter( 'wp_mail_content_type', fn() => 'text/html' );
$result = wp_mail( 'user@example.com', 'Hello', '<p>HTML body.</p>' );
remove_filter( 'wp_mail_content_type', fn() => 'text/html' ); // always remove
```

### Headers

```php
$headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: My Plugin <no-reply@example.com>',
    'Reply-To: Support <support@example.com>',
    'Cc: manager@example.com',
    'Bcc: archive@example.com',
];

wp_mail( $to, $subject, $message, $headers );
```

### Attachments

```php
$attachments = [
    WP_CONTENT_DIR . '/uploads/report.pdf',
    WP_CONTENT_DIR . '/uploads/data.csv',
];

wp_mail( $to, 'Monthly Report', $message, [], $attachments );
```

### Multiple recipients

```php
// Array of addresses
wp_mail( [ 'a@example.com', 'b@example.com' ], $subject, $message );

// Comma-separated string
wp_mail( 'a@example.com, b@example.com', $subject, $message );
```

## Filters

```php
// Override From name
add_filter( 'wp_mail_from_name', fn() => 'My Plugin' );

// Override From address
add_filter( 'wp_mail_from', fn() => 'no-reply@example.com' );

// Override content type for a single send, then remove
add_filter( 'wp_mail_content_type', fn() => 'text/html' );
wp_mail( ... );
remove_filter( 'wp_mail_content_type', fn() => 'text/html' );

// Modify all wp_mail() args before send
add_filter( 'wp_mail', function( array $args ) {
    // $args keys: to, subject, message, headers, attachments
    $args['headers'][] = 'Bcc: audit@example.com';
    return $args;
} );

// Hook into failed delivery
add_action( 'wp_mail_failed', function( WP_Error $error ) {
    error_log( 'wp_mail failed: ' . $error->get_error_message() );
} );
```

## PHPMailer (SMTP / advanced config)

```php
add_action( 'phpmailer_init', function( PHPMailer\PHPMailer\PHPMailer $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.example.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 587;
    $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $phpmailer->Username   = 'username@example.com';
    $phpmailer->Password   = 'password';
} );
```

## Logging failures

```php
add_action( 'wp_mail_failed', function( WP_Error $error ) {
    $log   = get_option( 'my_plugin_mail_failures', [] );
    $log[] = [
        'time'    => current_time( 'mysql' ),
        'code'    => $error->get_error_code(),
        'message' => $error->get_error_message(),
        'data'    => $error->get_error_data(),
    ];
    update_option( 'my_plugin_mail_failures', array_slice( $log, -50 ) );
} );
```

## Email queue pattern (avoid blocking requests)

```php
// Schedule via Action Scheduler instead of sending inline
function my_plugin_queue_email( string $to, string $subject, string $message ): void {
    as_enqueue_async_action( 'my_plugin_send_email', [
        'to'      => $to,
        'subject' => $subject,
        'message' => $message,
    ], 'my-plugin-email' );
}

add_action( 'my_plugin_send_email', function( string $to, string $subject, string $message ) {
    add_filter( 'wp_mail_content_type', fn() => 'text/html' );
    $ok = wp_mail( $to, $subject, $message, [
        'Content-Type: text/html; charset=UTF-8',
        'From: My Plugin <no-reply@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
    ] );
    remove_filter( 'wp_mail_content_type', fn() => 'text/html' );
    if ( ! $ok ) {
        throw new \RuntimeException( "wp_mail failed for {$to}" ); // AS will retry
    }
}, 10, 3 );
```

## Unsubscribe header (best practice)

```php
$site_name = get_bloginfo( 'name' );
$unsub_url = add_query_arg( [
    'action' => 'my_plugin_unsub',
    'email'  => rawurlencode( $to ),
    'token'  => wp_hash( $to . get_option( 'auth_key' ) ),
], home_url() );

$headers = [
    'Content-Type: text/html; charset=UTF-8',
    "List-Unsubscribe: <{$unsub_url}>",
    'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
];
```
