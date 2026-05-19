<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Elements;

/**
 * Container element — the primary layout wrapper (Flexbox/Grid).
 */
final class Container {

	public function get_type(): string { return 'container'; }

	public function render( array $element, string $mode = 'frontend' ): string {
		$settings = $element['settings'] ?? [];
		$children = $element['children'] ?? [];

		$classes  = $this->get_classes( $settings );
		$styles   = $this->get_inline_styles( $settings );
		$attrs    = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';

		if ( $styles ) {
			$attrs .= ' style="' . esc_attr( $styles ) . '"';
		}

		// Data attributes for editor.
		if ( 'editor' === $mode ) {
			$attrs .= ' data-eltiz-type="container" data-eltiz-id="' . esc_attr( $element['id'] ?? '' ) . '"';
		}

		$inner_html = '';
		foreach ( $children as $child ) {
			$inner_html .= $this->render_child( $child, $mode );
		}

		return '<div ' . $attrs . '>' . $inner_html . '</div>';
	}

	private function render_child( array $child, string $mode ): string {
		$type = $child['type'] ?? 'widget';

		if ( 'container' === $type ) {
			return $this->render( $child, $mode );
		}

		// Widget.
		$widget_manager = \EltizBuilder\Core\Plugin::instance()->make( 'widgets' );
		$widget         = $widget_manager->get( $child['widgetType'] ?? '' );

		if ( ! $widget ) {
			return '';
		}

		ob_start();
		$widget->render_widget( $child['settings'] ?? [] );
		return ob_get_clean();
	}

	private function get_classes( array $settings ): array {
		$classes = [ 'eltiz-container', 'eltiz-element' ];

		$layout = $settings['container_type'] ?? 'flexbox';
		$classes[] = 'eltiz-container--' . $layout;

		if ( 'yes' === ( $settings['content_width'] ?? '' ) ) {
			$classes[] = 'eltiz-container--stretched';
		}

		return apply_filters( 'eltiz/container/classes', $classes, $settings );
	}

	private function get_inline_styles( array $settings ): string {
		$styles = [];

		$flex_map = [
			'direction'    => 'flex-direction',
			'wrap'         => 'flex-wrap',
			'align_items'  => 'align-items',
			'justify_content' => 'justify-content',
		];

		foreach ( $flex_map as $key => $prop ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$styles[] = $prop . ':' . sanitize_key( $settings[ $key ] );
			}
		}

		if ( ! empty( $settings['gap']['row']['size'] ) ) {
			$gap_unit    = $settings['gap']['row']['unit'] ?? 'px';
			$gap_col     = $settings['gap']['column']['size'] ?? $settings['gap']['row']['size'];
			$gap_col_unit = $settings['gap']['column']['unit'] ?? 'px';
			$styles[] = 'gap:' . $settings['gap']['row']['size'] . $gap_unit . ' ' . $gap_col . $gap_col_unit;
		}

		if ( ! empty( $settings['padding'] ) ) {
			$p = $settings['padding'];
			$u = $p['unit'] ?? 'px';
			$styles[] = "padding:{$p['top']}{$u} {$p['right']}{$u} {$p['bottom']}{$u} {$p['left']}{$u}";
		}

		if ( ! empty( $settings['background_color'] ) ) {
			$styles[] = 'background-color:' . sanitize_hex_color( $settings['background_color'] );
		}

		return implode( ';', array_filter( $styles ) );
	}
}
