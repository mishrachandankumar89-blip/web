<?php
/**
 * Site header.
 *
 * @package avenza
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="avz-skip" href="#main"><?php esc_html_e( 'Skip to content', 'avenza' ); ?></a>

<header class="avz-header" id="avz-header">
	<div class="avz-container avz-header__inner">
		<div class="avz-header__brand"><?php avenza_logo( 'header' ); ?></div>

		<nav class="avz-nav" aria-label="<?php esc_attr_e( 'Primary', 'avenza' ); ?>">
			<?php avenza_primary_menu( 'avz-menu' ); ?>
		</nav>

		<div class="avz-header__actions">
			<?php if ( avenza_opt( 'phone' ) ) : ?>
				<a class="avz-header__phone" href="tel:<?php echo esc_attr( avenza_phone_raw() ); ?>">
					<?php echo avenza_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( avenza_opt( 'phone' ) ); ?></span>
				</a>
			<?php endif; ?>
			<a class="avz-btn avz-btn--light avz-header__cta" href="<?php echo esc_url( avenza_cta_url() ); ?>"><?php echo esc_html( avenza_opt( 'cta_text' ) ); ?></a>
			<button class="avz-burger" type="button" aria-controls="avz-drawer" aria-expanded="false">
				<?php echo avenza_icon( 'menu', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'avenza' ); ?></span>
			</button>
		</div>
	</div>
</header>

<div class="avz-drawer" id="avz-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site menu', 'avenza' ); ?>" hidden>
	<div class="avz-drawer__top">
		<?php avenza_logo( 'drawer' ); ?>
		<button class="avz-drawer__close" type="button">
			<?php echo avenza_icon( 'close', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'avenza' ); ?></span>
		</button>
	</div>
	<nav class="avz-drawer__nav" aria-label="<?php esc_attr_e( 'Mobile', 'avenza' ); ?>">
		<?php avenza_primary_menu( 'avz-drawer-menu' ); ?>
	</nav>
	<div class="avz-drawer__foot">
		<a class="avz-btn avz-btn--light" href="<?php echo esc_url( avenza_cta_url() ); ?>"><?php echo esc_html( avenza_opt( 'cta_text' ) ); ?></a>
		<?php if ( avenza_opt( 'phone' ) ) : ?>
			<a class="avz-drawer__link" href="tel:<?php echo esc_attr( avenza_phone_raw() ); ?>"><?php echo avenza_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( avenza_opt( 'phone' ) ); ?></a>
		<?php endif; ?>
		<?php $wa = avenza_whatsapp_url(); ?>
		<?php if ( $wa ) : ?>
			<a class="avz-drawer__link" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo avenza_icon( 'whatsapp', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'WhatsApp us', 'avenza' ); ?></a>
		<?php endif; ?>
	</div>
</div>

<?php avenza_breadcrumbs(); ?>
<main id="main" class="avz-main" tabindex="-1">
