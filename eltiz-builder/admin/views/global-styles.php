<?php
declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Access denied.', 'eltiz-builder' ) );
?>
<div class="wrap eltiz-admin-wrap">
	<h1><?php esc_html_e( 'Global Styles', 'eltiz-builder' ); ?></h1>
	<p><?php esc_html_e( 'Manage global colors and typography that can be reused across all pages.', 'eltiz-builder' ); ?></p>
	<div id="eltiz-global-styles-manager"
		data-rest="<?php echo esc_url( rest_url( 'eltiz/v1/global-styles' ) ); ?>"
		data-nonce="<?php echo wp_create_nonce( 'wp_rest' ); ?>">
		<p><?php esc_html_e( 'Loading...', 'eltiz-builder' ); ?></p>
	</div>
</div>
