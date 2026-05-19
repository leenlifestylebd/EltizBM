<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class ColorControl extends ControlBase {

	public function get_type(): string { return 'color'; }

	public function get_default_value(): mixed { return ''; }

	public function sanitize( mixed $value ): mixed {
		return sanitize_hex_color( (string) $value ) ?? '';
	}

	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'alpha'       => true,
			'global_link' => true,
		] );
	}

	public function get_style_value( mixed $value, array $args = [] ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$css_prop = $args['css_property'] ?? '';
		if ( empty( $css_prop ) ) {
			return '';
		}
		return sprintf( '%s:%s;', $css_prop, esc_attr( $value ) );
	}
}
