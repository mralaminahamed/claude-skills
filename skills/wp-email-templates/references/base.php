<?php
/**
 * Reusable HTML email wrapper. Copy into templates/emails/base.php.
 *
 * Replace the 'your-textdomain' text domain. Every email renders its body into
 * $content; this shell frames it with a branded header + footer.
 *
 * @var string $subject   Subject / hidden preheader fallback.
 * @var string $preheader Inbox preview text (optional).
 * @var string $content   Inner body HTML (escaped by the content template).
 * @var string $site_name Header/footer brand.
 * @var string $site_url  Absolute site URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subject   = $subject ?? '';
$preheader = $preheader ?? $subject;
$content   = $content ?? '';
$site_name = $site_name ?? get_bloginfo( 'name' );
$site_url  = $site_url ?? home_url( '/' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title><?php echo esc_html( $subject ); ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; -webkit-font-smoothing:antialiased; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
	<div style="display:none; max-height:0; overflow:hidden; opacity:0;"><?php echo esc_html( $preheader ); ?></div>
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
		<tr><td align="center">
			<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
				<tr><td style="background-color:#111827; padding:28px 40px;">
					<a href="<?php echo esc_url( $site_url ); ?>" style="color:#ffffff; font-size:18px; font-weight:700; text-decoration:none;"><?php echo esc_html( $site_name ); ?></a>
				</td></tr>
				<tr><td style="padding:40px; color:#374151; font-size:16px; line-height:1.6;">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content templates escape their own values. ?>
				</td></tr>
				<tr><td style="padding:24px 40px 32px; border-top:1px solid #e5e7eb; color:#9ca3af; font-size:13px; line-height:1.5;">
					<?php
					printf(
						/* translators: %s: site name */
						esc_html__( 'This is an automated message from %s. Please do not reply to this email.', 'your-textdomain' ),
						'<strong style="color:#6b7280;">' . esc_html( $site_name ) . '</strong>'
					);
					?>
					<br />
					<?php
					printf(
						/* translators: 1: year, 2: site name */
						esc_html__( '© %1$s %2$s. All rights reserved.', 'your-textdomain' ),
						esc_html( gmdate( 'Y' ) ),   // inline, NOT $year (global-override sniff)
						esc_html( $site_name )
					);
					?>
				</td></tr>
			</table>
		</td></tr>
	</table>
</body>
</html>
