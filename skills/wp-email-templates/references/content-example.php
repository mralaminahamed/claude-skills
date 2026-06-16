<?php
/**
 * Example content template. Copy to templates/emails/<name>.php and adapt.
 *
 * Rendered into base.php via Helpers::render_email('<name>', $vars). Escape
 * every variable; wrap copy in the text domain; guard each $var.
 *
 * @var string $user_name      Recipient display name.
 * @var string $site_name      Site name.
 * @var string $action_url     CTA URL (e.g. confirm/dashboard link).
 * @var int    $expiry_minutes Optional expiry for time-limited links.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_name      = $user_name ?? '';
$site_name      = $site_name ?? get_bloginfo( 'name' );
$action_url     = $action_url ?? '';
$expiry_minutes = isset( $expiry_minutes ) ? (int) $expiry_minutes : 0;
?>
<h1 style="margin:0 0 24px; color:#111827; font-size:22px; font-weight:700; line-height:1.3;">
	<?php esc_html_e( 'Confirm your request', 'your-textdomain' ); ?>
</h1>

<p style="margin:0 0 16px;">
	<?php
	printf(
		/* translators: %s: recipient name */
		esc_html__( 'Hi %s,', 'your-textdomain' ),
		esc_html( $user_name )
	);
	?>
</p>

<p style="margin:0 0 28px;">
	<?php
	printf(
		/* translators: %s: site name */
		esc_html__( 'Click the button below to continue on %s.', 'your-textdomain' ),
		'<strong style="color:#111827;">' . esc_html( $site_name ) . '</strong>'
	);
	?>
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
	<tr><td style="border-radius:8px; background-color:#16a34a;">
		<a href="<?php echo esc_url( $action_url ); ?>" style="display:inline-block; padding:14px 32px; color:#ffffff; font-size:16px; font-weight:600; text-decoration:none; border-radius:8px;">
			<?php esc_html_e( 'Continue', 'your-textdomain' ); ?>
		</a>
	</td></tr>
</table>

<?php if ( $expiry_minutes > 0 ) : ?>
	<p style="margin:0 0 16px; color:#6b7280; font-size:14px;">
		<?php
		printf(
			/* translators: %d: number of minutes */
			esc_html( _n( 'This link expires in %d minute.', 'This link expires in %d minutes.', $expiry_minutes, 'your-textdomain' ) ),
			(int) $expiry_minutes
		);
		?>
	</p>
<?php endif; ?>

<p style="margin:24px 0 0; color:#9ca3af; font-size:13px; word-break:break-all;">
	<?php esc_html_e( 'Button not working? Copy and paste this link:', 'your-textdomain' ); ?><br />
	<a href="<?php echo esc_url( $action_url ); ?>" style="color:#16a34a;"><?php echo esc_url( $action_url ); ?></a>
</p>
