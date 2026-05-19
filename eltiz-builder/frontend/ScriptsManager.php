<?php
declare( strict_types=1 );

namespace EltizBuilder\Frontend;

/**
 * Manages which widget JS modules are loaded per page.
 */
final class ScriptsManager {

	/** Widgets that require extra JS — keyed by widget name. */
	private array $widget_scripts = [
		'video'      => 'eltiz-video',
		'accordion'  => 'eltiz-accordion',
		'tabs'       => 'eltiz-tabs',
		'counter'    => 'eltiz-counter',
		'countdown'  => 'eltiz-countdown',
		'slider'     => 'eltiz-slider',
		'image-gallery' => 'eltiz-lightbox',
	];

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	public function register_scripts(): void {
		foreach ( $this->widget_scripts as $widget => $handle ) {
			wp_register_script(
				$handle,
				ELTIZ_ASSETS_URL . "js/widgets/{$handle}.min.js",
				[ 'eltiz-frontend' ],
				ELTIZ_VERSION,
				[ 'strategy' => 'defer', 'in_footer' => true ]
			);
		}
	}

	public function enqueue_for_post( int $post_id ): void {
		$raw  = get_post_meta( $post_id, '_eltiz_data', true );
		$data = $raw ? json_decode( $raw, true ) : null;

		if ( ! is_array( $data ) ) {
			return;
		}

		$used_widgets = [];
		$this->collect_widgets( $data, $used_widgets );

		foreach ( $used_widgets as $widget_name ) {
			$handle = $this->widget_scripts[ $widget_name ] ?? null;
			if ( $handle ) {
				wp_enqueue_script( $handle );
			}
		}
	}

	private function collect_widgets( array $elements, array &$used ): void {
		foreach ( $elements as $element ) {
			if ( 'widget' === ( $element['type'] ?? '' ) ) {
				$used[ $element['widgetType'] ?? '' ] = true;
			}
			if ( ! empty( $element['children'] ) ) {
				$this->collect_widgets( $element['children'], $used );
			}
		}
	}
}
