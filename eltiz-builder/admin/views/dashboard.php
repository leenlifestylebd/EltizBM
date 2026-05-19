<?php
declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$templates_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}eltiz_templates WHERE status = 'publish'" );
$submissions_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}eltiz_form_submissions WHERE status = 'unread'" );
$builder_pages     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_eltiz_built_with' AND meta_value = 'eltiz'" );
?>
<div class="wrap eltiz-admin-wrap">
	<div class="eltiz-admin-header">
		<h1><?php esc_html_e( 'Eltiz Builder', 'eltiz-builder' ); ?> <span class="eltiz-version">v<?php echo esc_html( ELTIZ_VERSION ); ?></span></h1>
		<p><?php esc_html_e( 'Advanced drag-and-drop visual website builder.', 'eltiz-builder' ); ?></p>
	</div>

	<div class="eltiz-admin-stats">
		<div class="eltiz-stat-card">
			<div class="eltiz-stat-number"><?php echo $builder_pages; ?></div>
			<div class="eltiz-stat-label"><?php esc_html_e( 'Pages Built', 'eltiz-builder' ); ?></div>
		</div>
		<div class="eltiz-stat-card">
			<div class="eltiz-stat-number"><?php echo $templates_count; ?></div>
			<div class="eltiz-stat-label"><?php esc_html_e( 'Templates', 'eltiz-builder' ); ?></div>
		</div>
		<div class="eltiz-stat-card eltiz-stat-card--attention">
			<div class="eltiz-stat-number"><?php echo $submissions_count; ?></div>
			<div class="eltiz-stat-label"><?php esc_html_e( 'Unread Submissions', 'eltiz-builder' ); ?></div>
		</div>
	</div>

	<div class="eltiz-admin-quick-actions">
		<h2><?php esc_html_e( 'Quick Actions', 'eltiz-builder' ); ?></h2>
		<div class="eltiz-quick-actions-grid">
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="eltiz-quick-action-card">
				<span class="dashicons dashicons-plus-alt2"></span>
				<span><?php esc_html_e( 'New Page', 'eltiz-builder' ); ?></span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eltiz-templates' ) ); ?>" class="eltiz-quick-action-card">
				<span class="dashicons dashicons-layout"></span>
				<span><?php esc_html_e( 'Templates', 'eltiz-builder' ); ?></span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eltiz-global-styles' ) ); ?>" class="eltiz-quick-action-card">
				<span class="dashicons dashicons-art"></span>
				<span><?php esc_html_e( 'Global Styles', 'eltiz-builder' ); ?></span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eltiz-settings' ) ); ?>" class="eltiz-quick-action-card">
				<span class="dashicons dashicons-admin-settings"></span>
				<span><?php esc_html_e( 'Settings', 'eltiz-builder' ); ?></span>
			</a>
		</div>
	</div>

	<div class="eltiz-admin-modules">
		<h2><?php esc_html_e( 'Active Modules', 'eltiz-builder' ); ?></h2>
		<?php
		$active_modules = (array) get_option( 'eltiz_active_modules', [] );
		$all_modules = [
			'theme-builder'  => __( 'Theme Builder', 'eltiz-builder' ),
			'popup-builder'  => __( 'Popup Builder', 'eltiz-builder' ),
			'forms'          => __( 'Form Builder', 'eltiz-builder' ),
			'woocommerce'    => __( 'WooCommerce Builder', 'eltiz-builder' ),
			'loop-builder'   => __( 'Loop Builder', 'eltiz-builder' ),
			'motion-effects' => __( 'Motion Effects', 'eltiz-builder' ),
			'global-styles'  => __( 'Global Styles', 'eltiz-builder' ),
			'dynamic-tags'   => __( 'Dynamic Tags', 'eltiz-builder' ),
		];
		?>
		<div class="eltiz-modules-grid">
			<?php foreach ( $all_modules as $slug => $label ) : ?>
				<div class="eltiz-module-card <?php echo ! empty( $active_modules[ $slug ] ) ? 'eltiz-module-active' : 'eltiz-module-inactive'; ?>">
					<span class="dashicons <?php echo ! empty( $active_modules[ $slug ] ) ? 'dashicons-yes-alt' : 'dashicons-minus-alt2'; ?>"></span>
					<?php echo esc_html( $label ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
