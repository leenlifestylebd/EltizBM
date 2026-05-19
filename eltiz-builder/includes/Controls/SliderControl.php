<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class SliderControl extends ControlBase {

	public function get_type(): string { return 'slider'; }

	public function get_default_value(): mixed {
		return [ 'size' => 0, 'unit' => 'px' ];
	}

	protected function is_responsive(): bool { return true; }

	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) {
			return $this->get_default_value();
		}
		return [
			'size' => (float) ( $value['size'] ?? 0 ),
			'unit' => sanitize_key( $value['unit'] ?? 'px' ),
		];
	}

	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'min'   => 0,
			'max'   => 100,
			'step'  => 1,
			'units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh' ],
		] );
	}

	public function get_style_value( mixed $value, array $args = [] ): string {
		if ( ! is_array( $value ) || ! isset( $value['size'], $value['unit'] ) ) {
			return '';
		}
		$css_prop = $args['css_property'] ?? '';
		if ( empty( $css_prop ) ) {
			return '';
		}
		return sprintf( '%s:%s%s;', $css_prop, $value['size'], $value['unit'] );
	}
}
