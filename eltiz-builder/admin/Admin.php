<?php
declare( strict_types=1 );

namespace EltizBuilder\Admin;

/**
 * Admin panel — menus, dashboard, settings pages.
 */
final class Admin {

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_menus(): void {
		add_menu_page(
			__( 'Eltiz Builder', 'eltiz-builder' ),
			__( 'Eltiz Builder', 'eltiz-builder' ),
			'manage_options',
			'eltiz-dashboard',
			[ $this, 'render_dashboard' ],
			'data:image/svg+xml;base64,' . base64_encode( $this->get_menu_icon() ),
			58
		);

		add_submenu_page(
			'eltiz-dashboard',
			__( 'Dashboard', 'eltiz-builder' ),
			__( 'Dashboard', 'eltiz-builder' ),
			'manage_options',
			'eltiz-dashboard',
			[ $this, 'render_dashboard' ]
		);

		add_submenu_page(
			'eltiz-dashboard',
			__( 'Templates', 'eltiz-builder' ),
			__( 'Templates', 'eltiz-builder' ),
			'edit_posts',
			'eltiz-templates',
			[ $this, 'render_templates' ]
		);

		add_submenu_page(
			'eltiz-dashboard',
			__( 'Global Styles', 'eltiz-builder' ),
			__( 'Global Styles', 'eltiz-builder' ),
			'manage_options',
			'eltiz-global-styles',
			[ $this, 'render_global_styles' ]
		);

		add_submenu_page(
			'eltiz-dashboard',
			__( 'Form Submissions', 'eltiz-builder' ),
			__( 'Form Submissions', 'eltiz-builder' ),
			'edit_posts',
			'eltiz-form-submissions',
			[ $this, 'render_form_submissions' ]
		);

		add_submenu_page(
			'eltiz-dashboard',
			__( 'Settings', 'eltiz-builder' ),
			__( 'Settings', 'eltiz-builder' ),
			'manage_options',
			'eltiz-settings',
			[ $this, 'render_settings' ]
		);

		add_submenu_page(
			'eltiz-dashboard',
			__( 'System Info', 'eltiz-builder' ),
			__( 'System Info', 'eltiz-builder' ),
			'manage_options',
			'eltiz-system-info',
			[ $this, 'render_system_info' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'eltiz' ) ) {
			return;
		}

		wp_enqueue_style( 'eltiz-admin', ELTIZ_ASSETS_URL . 'css/admin.min.css', [], ELTIZ_VERSION );
		wp_enqueue_script( 'eltiz-admin', ELTIZ_ASSETS_URL . 'js/admin.min.js', [ 'jquery', 'wp-api-fetch' ], ELTIZ_VERSION, true );

		wp_localize_script( 'eltiz-admin', 'eltizAdmin', [
			'restUrl'   => rest_url( 'eltiz/v1/' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'version'   => ELTIZ_VERSION,
			'i18n'      => [
				'confirm_delete' => __( 'Are you sure you want to delete this?', 'eltiz-builder' ),
				'saved'          => __( 'Settings saved.', 'eltiz-builder' ),
				'error'          => __( 'An error occurred.', 'eltiz-builder' ),
			],
		] );
	}

	public function register_settings(): void {
		register_setting( 'eltiz_settings', 'eltiz_active_modules' );
		register_setting( 'eltiz_settings', 'eltiz_performance_mode', [ 'sanitize_callback' => 'sanitize_key' ] );
		register_setting( 'eltiz_settings', 'eltiz_remove_data_on_uninstall', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'eltiz_settings', 'eltiz_disable_google_fonts', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'eltiz_settings', 'eltiz_css_print_method', [ 'sanitize_callback' => 'sanitize_key' ] );
	}

	// ─── Page renders ─────────────────────────────────────────────────────────

	public function render_dashboard(): void {
		include ELTIZ_PATH . 'admin/views/dashboard.php';
	}

	public function render_templates(): void {
		include ELTIZ_PATH . 'admin/views/templates.php';
	}

	public function render_global_styles(): void {
		include ELTIZ_PATH . 'admin/views/global-styles.php';
	}

	public function render_form_submissions(): void {
		include ELTIZ_PATH . 'admin/views/form-submissions.php';
	}

	public function render_settings(): void {
		include ELTIZ_PATH . 'admin/views/settings.php';
	}

	public function render_system_info(): void {
		include ELTIZ_PATH . 'admin/views/system-info.php';
	}

	private function get_menu_icon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>';
	}
}
