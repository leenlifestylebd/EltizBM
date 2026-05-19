<?php
declare( strict_types=1 );

namespace EltizBuilder\Core;

/**
 * Dynamic asset loader — only enqueues what the current page needs.
 */
final class Assets {

	private array $registered_scripts = [];
	private array $registered_styles  = [];

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
	}

	public function enqueue_frontend(): void {
		if ( ! $this->is_builder_page() ) {
			return;
		}

		wp_enqueue_style(
			'eltiz-frontend',
			ELTIZ_ASSETS_URL . 'css/frontend.min.css',
			[],
			ELTIZ_VERSION
		);

		wp_enqueue_script(
			'eltiz-frontend',
			ELTIZ_ASSETS_URL . 'js/frontend.min.js',
			[ 'jquery' ],
			ELTIZ_VERSION,
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);

		wp_localize_script( 'eltiz-frontend', 'eltizFrontend', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'eltiz_frontend' ),
			'isRtl'   => is_rtl(),
		] );
	}

	public function enqueue_admin( string $hook ): void {
		if ( ! $this->is_editor_screen( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'eltiz-editor',
			ELTIZ_ASSETS_URL . 'css/editor.min.css',
			[],
			ELTIZ_VERSION
		);

		wp_enqueue_script(
			'eltiz-editor',
			ELTIZ_ASSETS_URL . 'js/editor.min.js',
			[ 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch' ],
			ELTIZ_VERSION,
			true
		);

		wp_localize_script( 'eltiz-editor', 'eltizEditor', $this->editor_config() );
		wp_set_script_translations( 'eltiz-editor', 'eltiz-builder', ELTIZ_PATH . 'languages/' );
	}

	private function editor_config(): array {
		global $post;

		return [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'restUrl'     => rest_url( 'eltiz/v1/' ),
			'nonce'       => wp_create_nonce( 'eltiz_editor' ),
			'restNonce'   => wp_create_nonce( 'wp_rest' ),
			'postId'      => $post->ID ?? 0,
			'postType'    => $post->post_type ?? 'page',
			'homeUrl'     => home_url(),
			'assetsUrl'   => ELTIZ_ASSETS_URL,
			'version'     => ELTIZ_VERSION,
			'isRtl'       => is_rtl(),
			'breakpoints' => $this->get_breakpoints(),
			'i18n'        => [
				'save'    => __( 'Save', 'eltiz-builder' ),
				'publish' => __( 'Publish', 'eltiz-builder' ),
				'preview' => __( 'Preview', 'eltiz-builder' ),
			],
		];
	}

	private function get_breakpoints(): array {
		return apply_filters( 'eltiz_breakpoints', [
			'desktop' => [ 'label' => __( 'Desktop', 'eltiz-builder' ), 'value' => 1025, 'direction' => 'min' ],
			'tablet'  => [ 'label' => __( 'Tablet', 'eltiz-builder' ),  'value' => 768,  'direction' => 'max' ],
			'mobile'  => [ 'label' => __( 'Mobile', 'eltiz-builder' ),  'value' => 767,  'direction' => 'max' ],
		] );
	}

	private function is_builder_page(): bool {
		return is_singular() && get_post_meta( get_the_ID(), '_eltiz_built_with', true ) === 'eltiz';
	}

	private function is_editor_screen( string $hook ): bool {
		return in_array( $hook, [ 'post.php', 'post-new.php' ], true );
	}

	/**
	 * Register a custom script for a module — dequeued until needed.
	 */
	public function register_script( string $handle, string $src, array $deps = [], bool $in_footer = true ): void {
		$this->registered_scripts[ $handle ] = compact( 'src', 'deps', 'in_footer' );
		wp_register_script( $handle, $src, $deps, ELTIZ_VERSION, $in_footer );
	}

	public function register_style( string $handle, string $src, array $deps = [] ): void {
		$this->registered_styles[ $handle ] = compact( 'src', 'deps' );
		wp_register_style( $handle, $src, $deps, ELTIZ_VERSION );
	}

	public function enqueue_script( string $handle ): void {
		wp_enqueue_script( $handle );
	}

	public function enqueue_style( string $handle ): void {
		wp_enqueue_style( $handle );
	}
}
