<?php
declare( strict_types=1 );

namespace EltizBuilder\Frontend;

/**
 * Compiles and manages CSS from element/widget settings.
 */
final class StylesManager {

	private array $global_colors = [];
	private array $global_fonts  = [];

	public function __construct() {
		$this->global_colors = (array) get_option( 'eltiz_global_colors', [] );
		$this->global_fonts  = (array) get_option( 'eltiz_global_fonts', [] );
	}

	public function print_post_styles( int $post_id ): void {
		$css = $this->generate_post_css( $post_id );
		if ( $css ) {
			echo '<style id="eltiz-post-css-' . $post_id . '">' . wp_strip_all_tags( $css ) . '</style>';
		}
	}

	public function generate_post_css( int $post_id ): string {
		$raw  = get_post_meta( $post_id, '_eltiz_data', true );
		$data = $raw ? json_decode( $raw, true ) : null;

		if ( ! is_array( $data ) ) {
			return '';
		}

		$css = '';
		foreach ( $data as $element ) {
			$css .= $this->generate_element_css( $element );
		}

		return $css;
	}

	private function generate_element_css( array $element ): string {
		$css = '';
		$id  = $element['id'] ?? '';

		if ( ! $id ) {
			return '';
		}

		$selector = ".eltiz-element[data-eltiz-id=\"{$id}\"]";
		$settings = $element['settings'] ?? [];

		$css .= $this->compile_selectors( $selector, $settings );

		foreach ( ( $element['children'] ?? [] ) as $child ) {
			$css .= $this->generate_element_css( $child );
		}

		return $css;
	}

	private function compile_selectors( string $selector, array $settings ): string {
		$css = '';

		// Background color.
		if ( ! empty( $settings['background_color'] ) ) {
			$css .= $selector . '{background-color:' . sanitize_hex_color( $settings['background_color'] ) . ';}';
		}

		// Text color.
		if ( ! empty( $settings['color'] ) ) {
			$css .= $selector . '{color:' . sanitize_hex_color( $settings['color'] ) . ';}';
		}

		// Padding.
		if ( ! empty( $settings['padding'] ) ) {
			$css .= $selector . '{' . $this->dimensions_to_css( 'padding', $settings['padding'] ) . '}';
		}

		// Margin.
		if ( ! empty( $settings['margin'] ) ) {
			$css .= $selector . '{' . $this->dimensions_to_css( 'margin', $settings['margin'] ) . '}';
		}

		// Border radius.
		if ( ! empty( $settings['border_radius'] ) ) {
			$css .= $selector . '{' . $this->dimensions_to_css( 'border-radius', $settings['border_radius'] ) . '}';
		}

		// Typography.
		if ( ! empty( $settings['typography'] ) ) {
			$css .= $selector . '{' . $this->typography_to_css( $settings['typography'] ) . '}';
		}

		// Custom CSS.
		if ( ! empty( $settings['custom_css'] ) ) {
			$custom = str_replace( 'selector', $selector, wp_strip_all_tags( $settings['custom_css'] ) );
			$css .= $custom;
		}

		// Responsive overrides.
		$breakpoints = [
			'tablet' => '(max-width: 1024px)',
			'mobile' => '(max-width: 767px)',
		];

		foreach ( $breakpoints as $device => $query ) {
			$device_css = '';

			if ( ! empty( $settings[ "padding_{$device}" ] ) ) {
				$device_css .= $this->dimensions_to_css( 'padding', $settings[ "padding_{$device}" ] );
			}
			if ( ! empty( $settings[ "margin_{$device}" ] ) ) {
				$device_css .= $this->dimensions_to_css( 'margin', $settings[ "margin_{$device}" ] );
			}

			if ( $device_css ) {
				$css .= "@media {$query}{{$selector}{{$device_css}}}";
			}
		}

		return $css;
	}

	private function dimensions_to_css( string $property, array $dims ): string {
		$unit = $dims['unit'] ?? 'px';
		$t    = is_numeric( $dims['top']    ?? '' ) ? $dims['top']    . $unit : '0';
		$r    = is_numeric( $dims['right']  ?? '' ) ? $dims['right']  . $unit : '0';
		$b    = is_numeric( $dims['bottom'] ?? '' ) ? $dims['bottom'] . $unit : '0';
		$l    = is_numeric( $dims['left']   ?? '' ) ? $dims['left']   . $unit : '0';
		return "{$property}:{$t} {$r} {$b} {$l};";
	}

	private function typography_to_css( array $typo ): string {
		$css = '';
		if ( ! empty( $typo['family'] ) )         $css .= 'font-family:' . esc_attr( $typo['family'] ) . ';';
		if ( ! empty( $typo['size']['size'] ) )    $css .= 'font-size:' . $typo['size']['size'] . ( $typo['size']['unit'] ?? 'px' ) . ';';
		if ( ! empty( $typo['weight'] ) )          $css .= 'font-weight:' . esc_attr( $typo['weight'] ) . ';';
		if ( ! empty( $typo['style'] ) )           $css .= 'font-style:' . esc_attr( $typo['style'] ) . ';';
		if ( ! empty( $typo['transform'] ) )       $css .= 'text-transform:' . esc_attr( $typo['transform'] ) . ';';
		if ( ! empty( $typo['decoration'] ) )      $css .= 'text-decoration:' . esc_attr( $typo['decoration'] ) . ';';
		if ( ! empty( $typo['line_height']['size'] ) ) $css .= 'line-height:' . $typo['line_height']['size'] . ( $typo['line_height']['unit'] ?? 'em' ) . ';';
		if ( ! empty( $typo['letter_spacing']['size'] ) ) $css .= 'letter-spacing:' . $typo['letter_spacing']['size'] . ( $typo['letter_spacing']['unit'] ?? 'px' ) . ';';
		return $css;
	}

	public function get_global_css(): string {
		$css = '';

		// CSS variables for global colors.
		if ( $this->global_colors ) {
			$css .= ':root{';
			foreach ( $this->global_colors as $color ) {
				$id  = sanitize_key( $color['id'] ?? '' );
				$val = sanitize_hex_color( $color['value'] ?? '' ) ?? '';
				if ( $id && $val ) {
					$css .= "--eltiz-color-{$id}:{$val};";
				}
			}
			$css .= '}';
		}

		// CSS variables for global fonts.
		if ( $this->global_fonts ) {
			$css .= ':root{';
			foreach ( $this->global_fonts as $font ) {
				$id  = sanitize_key( $font['id'] ?? '' );
				$val = sanitize_text_field( $font['family'] ?? '' );
				if ( $id && $val ) {
					$css .= "--eltiz-font-{$id}:{$val};";
				}
			}
			$css .= '}';
		}

		return apply_filters( 'eltiz/frontend/global_css', $css );
	}
}
