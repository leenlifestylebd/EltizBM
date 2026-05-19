<?php
declare( strict_types=1 );

namespace EltizBuilder\Modules\Performance;

/**
 * Performance optimization — lazy loading, conditional asset loading, critical CSS.
 */
final class PerformanceManager {

	public function init(): void {
		$mode = get_option( 'eltiz_performance_mode', 'standard' );

		if ( 'advanced' === $mode ) {
			$this->enable_advanced_mode();
		}

		add_filter( 'eltiz/frontend/widget_render_html', [ $this, 'add_lazy_attributes' ], 10, 2 );
		add_action( 'wp_head', [ $this, 'print_critical_css' ], 1 );

		do_action( 'eltiz/performance/init', $this );
	}

	private function enable_advanced_mode(): void {
		// Defer non-critical scripts.
		add_filter( 'script_loader_tag', [ $this, 'add_defer_attribute' ], 10, 2 );

		// Remove unused widget styles.
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_unused' ], 100 );
	}

	public function add_defer_attribute( string $tag, string $handle ): string {
		if ( str_starts_with( $handle, 'eltiz-' ) && ! str_contains( $tag, 'defer' ) ) {
			return str_replace( '<script ', '<script defer ', $tag );
		}
		return $tag;
	}

	public function dequeue_unused(): void {
		// Dequeue Font Awesome if disabled.
		if ( get_option( 'eltiz_disable_google_fonts' ) ) {
			wp_deregister_style( 'eltiz-fonts' );
		}
	}

	public function add_lazy_attributes( string $html, array $settings ): string {
		// Add loading="lazy" to any img tags that don't already have it.
		return preg_replace(
			'/<img(?![^>]*\bloading\b)([^>]*)>/i',
			'<img loading="lazy"$1>',
			$html
		);
	}

	public function print_critical_css(): void {
		if ( ! is_singular() ) return;

		$post_id = get_the_ID();
		$critical = get_post_meta( $post_id, '_eltiz_critical_css', true );

		if ( $critical ) {
			echo '<style id="eltiz-critical-css">' . wp_strip_all_tags( $critical ) . '</style>';
		}
	}

	public function generate_critical_css( int $post_id ): string {
		// Critical CSS generation hook — can be extended.
		return apply_filters( 'eltiz/performance/critical_css', '', $post_id );
	}
}
