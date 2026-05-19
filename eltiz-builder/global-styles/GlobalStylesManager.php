<?php
declare( strict_types=1 );

namespace EltizBuilder\GlobalStyles;

/**
 * Global design system — manages colors, typography, and design tokens.
 */
final class GlobalStylesManager {

	private array $colors    = [];
	private array $fonts     = [];
	private array $tokens    = [];

	public function init(): void {
		$this->load();
		add_action( 'wp_head',    [ $this, 'print_css_variables' ], 999 );
		add_action( 'admin_head', [ $this, 'print_css_variables' ], 999 );
		do_action( 'eltiz/global_styles/init', $this );
	}

	private function load(): void {
		$this->colors = (array) get_option( 'eltiz_global_colors', $this->default_colors() );
		$this->fonts  = (array) get_option( 'eltiz_global_fonts',  $this->default_fonts() );
		$this->tokens = (array) get_option( 'eltiz_design_tokens', $this->default_tokens() );
	}

	public function print_css_variables(): void {
		$css = ':root{';

		foreach ( $this->colors as $color ) {
			$id  = sanitize_key( $color['id'] ?? '' );
			$val = $color['value'] ?? '';
			if ( $id && $val ) {
				$css .= "--eltiz-color-{$id}:" . esc_attr( $val ) . ';';
			}
		}

		foreach ( $this->fonts as $font ) {
			$id  = sanitize_key( $font['id'] ?? '' );
			$val = sanitize_text_field( $font['family'] ?? '' );
			if ( $id && $val ) {
				$css .= "--eltiz-font-{$id}:'{$val}';";
			}
		}

		foreach ( $this->tokens as $token ) {
			$id  = sanitize_key( $token['id'] ?? '' );
			$val = sanitize_text_field( $token['value'] ?? '' );
			if ( $id && $val ) {
				$css .= "--eltiz-token-{$id}:{$val};";
			}
		}

		$css .= '}';

		echo '<style id="eltiz-global-vars">' . wp_strip_all_tags( $css ) . '</style>';
	}

	// ─── Getters ──────────────────────────────────────────────────────────────

	public function get_colors(): array { return $this->colors; }
	public function get_fonts(): array  { return $this->fonts; }
	public function get_tokens(): array { return $this->tokens; }

	public function get_color( string $id ): string {
		foreach ( $this->colors as $color ) {
			if ( ( $color['id'] ?? '' ) === $id ) {
				return $color['value'] ?? '';
			}
		}
		return '';
	}

	// ─── Setters ──────────────────────────────────────────────────────────────

	public function save_colors( array $colors ): void {
		$sanitized = array_map( fn( $c ) => [
			'id'    => sanitize_key( $c['id'] ?? '' ),
			'title' => sanitize_text_field( $c['title'] ?? '' ),
			'value' => sanitize_hex_color( $c['value'] ?? '' ) ?? '',
		], $colors );

		update_option( 'eltiz_global_colors', $sanitized );
		$this->colors = $sanitized;
	}

	public function save_fonts( array $fonts ): void {
		$sanitized = array_map( fn( $f ) => [
			'id'     => sanitize_key( $f['id'] ?? '' ),
			'title'  => sanitize_text_field( $f['title'] ?? '' ),
			'family' => sanitize_text_field( $f['family'] ?? '' ),
			'weights' => array_map( 'sanitize_key', (array) ( $f['weights'] ?? [] ) ),
		], $fonts );

		update_option( 'eltiz_global_fonts', $sanitized );
		$this->fonts = $sanitized;
	}

	// ─── Defaults ─────────────────────────────────────────────────────────────

	private function default_colors(): array {
		return [
			[ 'id' => 'primary',    'title' => __( 'Primary', 'eltiz-builder' ),    'value' => '#6366f1' ],
			[ 'id' => 'secondary',  'title' => __( 'Secondary', 'eltiz-builder' ),  'value' => '#8b5cf6' ],
			[ 'id' => 'accent',     'title' => __( 'Accent', 'eltiz-builder' ),     'value' => '#f59e0b' ],
			[ 'id' => 'text',       'title' => __( 'Text', 'eltiz-builder' ),       'value' => '#1f2937' ],
			[ 'id' => 'background', 'title' => __( 'Background', 'eltiz-builder' ), 'value' => '#ffffff' ],
			[ 'id' => 'muted',      'title' => __( 'Muted', 'eltiz-builder' ),      'value' => '#6b7280' ],
		];
	}

	private function default_fonts(): array {
		return [
			[ 'id' => 'primary',   'title' => __( 'Primary', 'eltiz-builder' ),   'family' => 'Inter' ],
			[ 'id' => 'secondary', 'title' => __( 'Secondary', 'eltiz-builder' ), 'family' => 'Georgia' ],
		];
	}

	private function default_tokens(): array {
		return [
			[ 'id' => 'border-radius', 'title' => 'Border Radius', 'value' => '8px' ],
			[ 'id' => 'spacing-sm',    'title' => 'Spacing SM',    'value' => '8px' ],
			[ 'id' => 'spacing-md',    'title' => 'Spacing MD',    'value' => '16px' ],
			[ 'id' => 'spacing-lg',    'title' => 'Spacing LG',    'value' => '32px' ],
			[ 'id' => 'shadow',        'title' => 'Shadow',        'value' => '0 4px 6px -1px rgba(0,0,0,.1)' ],
		];
	}
}
