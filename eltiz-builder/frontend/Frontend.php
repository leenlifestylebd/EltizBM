<?php
declare( strict_types=1 );

namespace EltizBuilder\Frontend;

use EltizBuilder\Core\Plugin;
use EltizBuilder\Includes\Elements\Container;

/**
 * Renders Eltiz Builder content on the frontend.
 */
final class Frontend {

	private StylesManager  $styles_manager;
	private ScriptsManager $scripts_manager;

	public function __construct() {
		$this->styles_manager  = new StylesManager();
		$this->scripts_manager = new ScriptsManager();
	}

	public function init(): void {
		add_filter( 'the_content', [ $this, 'render_content' ], 10 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_head', [ $this, 'print_global_css' ], 1000 );
	}

	public function render_content( string $content ): string {
		$post_id = get_the_ID();

		if ( ! $post_id || get_post_meta( $post_id, '_eltiz_built_with', true ) !== 'eltiz' ) {
			return $content;
		}

		$raw = get_post_meta( $post_id, '_eltiz_data', true );

		if ( empty( $raw ) ) {
			return $content;
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return $content;
		}

		ob_start();
		$this->render_elements( $data );
		return ob_get_clean();
	}

	public function render_elements( array $elements ): void {
		$container_renderer = new Container();
		foreach ( $elements as $element ) {
			echo $container_renderer->render( $element ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function enqueue(): void {
		if ( ! $this->is_builder_page() ) {
			return;
		}

		$post_id = get_the_ID();
		$this->styles_manager->print_post_styles( $post_id );
	}

	public function print_global_css(): void {
		$global_css = $this->styles_manager->get_global_css();
		if ( $global_css ) {
			echo '<style id="eltiz-global-css">' . wp_strip_all_tags( $global_css ) . '</style>';
		}
	}

	private function is_builder_page(): bool {
		return is_singular() && get_post_meta( get_the_ID(), '_eltiz_built_with', true ) === 'eltiz';
	}
}
