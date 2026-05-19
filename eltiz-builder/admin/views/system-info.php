<?php
declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Access denied.', 'eltiz-builder' ) );

global $wpdb;

$info = [
	__( 'WordPress Version', 'eltiz-builder' )    => get_bloginfo( 'version' ),
	__( 'PHP Version', 'eltiz-builder' )           => PHP_VERSION,
	__( 'MySQL Version', 'eltiz-builder' )         => $wpdb->db_version(),
	__( 'Eltiz Builder Version', 'eltiz-builder' ) => ELTIZ_VERSION,
	__( 'WooCommerce', 'eltiz-builder' )           => class_exists( 'WooCommerce' ) ? ( WC()->version ?? __( 'Active', 'eltiz-builder' ) ) : __( 'Not Active', 'eltiz-builder' ),
	__( 'Memory Limit', 'eltiz-builder' )          => WP_MEMORY_LIMIT,
	__( 'Max Upload Size', 'eltiz-builder' )       => size_format( wp_max_upload_size() ),
	__( 'Active Theme', 'eltiz-builder' )          => wp_get_theme()->get( 'Name' ),
	__( 'Multisite', 'eltiz-builder' )             => is_multisite() ? __( 'Yes', 'eltiz-builder' ) : __( 'No', 'eltiz-builder' ),
	__( 'Debug Mode', 'eltiz-builder' )            => defined( 'WP_DEBUG' ) && WP_DEBUG ? __( 'Yes', 'eltiz-builder' ) : __( 'No', 'eltiz-builder' ),
	__( 'SSL', 'eltiz-builder' )                   => is_ssl() ? __( 'Yes', 'eltiz-builder' ) : __( 'No', 'eltiz-builder' ),
	__( 'Server Software', 'eltiz-builder' )       => $_SERVER['SERVER_SOFTWARE'] ?? '',
	__( 'PHP Time Limit', 'eltiz-builder' )        => ini_get( 'max_execution_time' ) . 's',
	__( 'PHP Max Input Vars', 'eltiz-builder' )    => ini_get( 'max_input_vars' ),
];
?>
<div class="wrap eltiz-admin-wrap">
	<h1><?php esc_html_e( 'System Information', 'eltiz-builder' ); ?></h1>
	<table class="widefat" style="max-width:800px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Field', 'eltiz-builder' ); ?></th>
				<th><?php esc_html_e( 'Value', 'eltiz-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $info as $label => $value ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $label ); ?></strong></td>
				<td><?php echo esc_html( $value ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
