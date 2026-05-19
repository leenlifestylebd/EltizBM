<?php
declare( strict_types=1 );

namespace EltizBuilder\PopupBuilder;

/**
 * Popup Builder — registers post type, renders popups on the frontend.
 */
final class PopupManager {

	public const POST_TYPE = 'eltiz_popup';

	public function init(): void {
		add_action( 'init',              [ $this, 'register_post_type' ] );
		add_action( 'wp_footer',         [ $this, 'render_popups' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		do_action( 'eltiz/popup_builder/init', $this );
	}

	public function register_post_type(): void {
		register_post_type( self::POST_TYPE, [
			'label'           => __( 'Popups', 'eltiz-builder' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'eltiz-dashboard',
			'show_in_rest'    => true,
			'supports'        => [ 'title', 'custom-fields' ],
			'rewrite'         => false,
		] );
	}

	public function render_popups(): void {
		$popups = $this->get_active_popups();

		foreach ( $popups as $popup ) {
			$settings = get_post_meta( $popup->ID, '_eltiz_popup_settings', true );
			$settings = $settings ? json_decode( $settings, true ) : [];

			if ( ! $this->should_display( $settings ) ) {
				continue;
			}

			$data     = get_post_meta( $popup->ID, '_eltiz_data', true );
			$elements = $data ? json_decode( $data, true ) : [];

			if ( ! is_array( $elements ) ) {
				continue;
			}

			$this->render_popup( $popup->ID, $elements, $settings );
		}
	}

	private function render_popup( int $id, array $elements, array $settings ): void {
		$type    = sanitize_key( $settings['popup_type'] ?? 'modal' );
		$trigger = sanitize_key( $settings['trigger'] ?? 'page_load' );
		$delay   = absint( $settings['trigger_delay'] ?? 0 );

		echo '<div class="eltiz-popup eltiz-popup-' . esc_attr( $type ) . '"';
		echo ' id="eltiz-popup-' . $id . '"';
		echo ' data-popup-id="' . $id . '"';
		echo ' data-trigger="' . esc_attr( $trigger ) . '"';
		echo ' data-delay="' . $delay . '"';
		echo ' role="dialog" aria-modal="true" aria-label="' . esc_attr( get_the_title( $id ) ) . '"';
		echo ' style="display:none;">';

		echo '<div class="eltiz-popup-overlay" data-eltiz-popup-close></div>';
		echo '<div class="eltiz-popup-content">';
		echo '<button class="eltiz-popup-close" data-eltiz-popup-close aria-label="' . esc_attr__( 'Close', 'eltiz-builder' ) . '">&times;</button>';

		$frontend = \EltizBuilder\Core\Plugin::instance()->make( 'frontend' );
		$frontend->render_elements( $elements );

		echo '</div>';
		echo '</div>';
	}

	public function enqueue_assets(): void {
		$popups = $this->get_active_popups();
		if ( empty( $popups ) ) return;

		wp_enqueue_script( 'eltiz-popup', ELTIZ_ASSETS_URL . 'js/popup.min.js', [ 'eltiz-frontend' ], ELTIZ_VERSION, true );
		wp_enqueue_style( 'eltiz-popup', ELTIZ_ASSETS_URL . 'css/popup.min.css', [], ELTIZ_VERSION );
	}

	private function get_active_popups(): array {
		return get_posts( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );
	}

	private function should_display( array $settings ): bool {
		$conditions = $settings['conditions'] ?? [];

		if ( empty( $conditions ) ) {
			return true;
		}

		// Check user login status condition.
		if ( isset( $conditions['user_logged_in'] ) ) {
			if ( 'logged_in' === $conditions['user_logged_in'] && ! is_user_logged_in() ) return false;
			if ( 'logged_out' === $conditions['user_logged_in'] && is_user_logged_in() ) return false;
		}

		return apply_filters( 'eltiz/popup/should_display', true, $settings );
	}

	public function get_popup_types(): array {
		return [
			'modal'      => __( 'Modal', 'eltiz-builder' ),
			'slide-in'   => __( 'Slide-In', 'eltiz-builder' ),
			'floating-bar' => __( 'Floating Bar', 'eltiz-builder' ),
			'fullscreen' => __( 'Fullscreen', 'eltiz-builder' ),
			'bottom-bar' => __( 'Bottom Bar', 'eltiz-builder' ),
			'off-canvas' => __( 'Off-Canvas', 'eltiz-builder' ),
		];
	}
}
