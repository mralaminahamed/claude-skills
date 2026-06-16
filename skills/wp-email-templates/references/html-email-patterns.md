# HTML Email Patterns

## Base Template Structure

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Email Subject</title>
<style>
/* Only embed critical resets here — most CSS must be inline */
body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; }
img  { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
table { border-collapse: collapse !important; }

/* Dark mode support (email clients that support @media) */
@media (prefers-color-scheme: dark) {
    .email-bg   { background-color: #1a1a1a !important; }
    .email-card { background-color: #2d2d2d !important; }
    .email-text { color: #e0e0e0 !important; }
}
</style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;">

<!-- Preheader (hidden preview text) -->
<span style="display:none;font-size:1px;color:#ffffff;max-height:0;max-width:0;opacity:0;overflow:hidden;">
    Preview text shown in inbox before opening.&nbsp;&zwnj;&zwnj;
</span>

<!-- Outer wrapper -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
       class="email-bg" style="background-color:#f4f4f4;">
  <tr>
    <td align="center" style="padding:40px 10px;">

      <!-- Content card (max 600px) -->
      <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
             class="email-card" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">

        <!-- Header -->
        <tr>
          <td style="background-color:#0073aa;padding:30px 40px;text-align:center;">
            <img src="https://example.com/logo.png" alt="My Plugin" width="120" height="auto"
                 style="display:block;margin:0 auto;">
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td class="email-text" style="padding:40px;color:#333333;font-family:Arial,sans-serif;font-size:16px;line-height:1.6;">
            <h1 style="margin:0 0 20px;font-size:24px;font-weight:bold;color:#0073aa;">
                Hello, {{first_name}}!
            </h1>
            <p style="margin:0 0 20px;">Your main message goes here.</p>

            <!-- CTA Button -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:30px auto;">
              <tr>
                <td style="border-radius:4px;background-color:#0073aa;">
                  <a href="{{action_url}}"
                     style="display:inline-block;padding:14px 30px;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:4px;">
                    Take Action
                  </a>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 20px;color:#666666;font-size:14px;">
                Or copy this link: <a href="{{action_url}}" style="color:#0073aa;">{{action_url}}</a>
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:20px 40px;background-color:#f8f8f8;border-top:1px solid #e5e5e5;
                     font-family:Arial,sans-serif;font-size:12px;color:#999999;text-align:center;">
            <p style="margin:0 0 10px;">
                © <?php echo esc_html( gmdate( 'Y' ) ); ?> {{site_name}}. All rights reserved.
            </p>
            <p style="margin:0;">
                <a href="{{unsubscribe_url}}" style="color:#999999;">Unsubscribe</a>
                &nbsp;|&nbsp;
                <a href="{{privacy_url}}" style="color:#999999;">Privacy Policy</a>
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
```

## PHP Email Builder Pattern

```php
class My_Plugin_Email {

    private string $template;
    private array  $vars = [];

    public function __construct( string $template_path ) {
        $this->template = file_get_contents( $template_path );
    }

    public function set( string $key, string $value ): self {
        $this->vars[ "{{$key}}" ] = $value;
        return $this;
    }

    public function render(): string {
        return str_replace(
            array_keys( $this->vars ),
            array_values( $this->vars ),
            $this->template
        );
    }

    public function send( string $to, string $subject ): bool {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <no-reply@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
        ];

        add_filter( 'wp_mail_content_type', fn() => 'text/html' );
        $ok = wp_mail( $to, $subject, $this->render(), $headers );
        remove_filter( 'wp_mail_content_type', fn() => 'text/html' );

        return $ok;
    }
}

// Usage
$email = new My_Plugin_Email( plugin_dir_path( __FILE__ ) . 'emails/welcome.html' );
$email->set( 'first_name', $user->display_name )
      ->set( 'action_url', esc_url( admin_url() ) )
      ->set( 'site_name', get_bloginfo( 'name' ) )
      ->send( $user->user_email, __( 'Welcome!', 'my-plugin' ) );
```

## Inline CSS (required for Gmail/Outlook)

Use a library or do it manually. The `true_inline_css` pattern with Emogrifier:

```php
composer require pelago/emogrifier

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;

$html   = file_get_contents( 'email-template.html' );
$inline = CssInliner::fromHtml( $html )->inlineCss()->render();
```

## WooCommerce-style template override

Store templates in plugin, allow theme override:

```php
function my_plugin_get_email_template( string $template_name ): string {
    // 1. Check child/parent theme
    $theme_path = get_stylesheet_directory() . '/my-plugin/emails/' . $template_name;
    if ( file_exists( $theme_path ) ) return $theme_path;

    // 2. Filter override
    $filtered = apply_filters( 'my_plugin_email_template', '', $template_name );
    if ( $filtered && file_exists( $filtered ) ) return $filtered;

    // 3. Plugin default
    return plugin_dir_path( MY_PLUGIN_FILE ) . 'templates/emails/' . $template_name;
}
```

## Outlook VML Button (100% compatible)

```html
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
    href="{{action_url}}" style="height:46px;v-text-anchor:middle;width:200px;" arcsize="10%"
    strokecolor="#0073aa" fillcolor="#0073aa">
  <w:anchorlock/>
  <center style="color:#ffffff;font-family:sans-serif;font-size:16px;font-weight:bold;">
    Take Action
  </center>
</v:roundrect>
<![endif]-->
<!--[if !mso]><!-->
<a href="{{action_url}}" style="...normal button styles...">Take Action</a>
<!--<![endif]-->
```

## Key Rules

| Rule | Why |
|---|---|
| All CSS must be inline | Gmail strips `<style>` blocks |
| Use `role="presentation"` on tables | Screen reader accessibility |
| Max width 600px | Narrower clients (Outlook preview pane) |
| Always provide plain text fallback | Some clients block HTML |
| Images need `width`/`height` attributes | Outlook ignores CSS width on images |
| No `position:absolute` or `float` | Outlook 2007+ ignores these |
| Use `&nbsp;&zwnj;` padding in preheader | Prevents inbox showing body text as preview |
