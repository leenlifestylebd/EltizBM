<?php
declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap eltiz-admin-wrap">
	<h1><?php esc_html_e( 'Eltiz Templates', 'eltiz-builder' ); ?></h1>
	<p><?php esc_html_e( 'Saved templates are managed via the editor. Create a new template by building a page and saving it as a template block.', 'eltiz-builder' ); ?></p>
	<div id="eltiz-templates-manager" data-rest="<?php echo esc_url( rest_url( 'eltiz/v1/templates' ) ); ?>" data-nonce="<?php echo wp_create_nonce( 'wp_rest' ); ?>">
		<p><?php esc_html_e( 'Loading templates...', 'eltiz-builder' ); ?></p>
	</div>
</div>
