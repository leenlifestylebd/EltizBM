<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class DimensionsControl extends ControlBase {

	public function get_type(): string { return 'dimensions'; }

	public function get_default_value(): mixed {
		return [
			'top'    => '',
			'right'  => '',
			'bottom' => '',
			'left'   => '',
			'unit'   => 'px',
			'isLinked' => true,
		];
	}

	protected function is_responsive(): bool { return true; }

	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) {
			return $this->get_default_value();
		}
		return [
			'top'      => is_numeric( $value['top'] ?? '' ) ? (float) $value['top'] : '',
			'right'    => is_numeric( $value['right'] ?? '' ) ? (float) $value['right'] : '',
			'bottom'   => is_numeric( $value['bottom'] ?? '' ) ? (float) $value['bottom'] : '',
			'left'     => is_numeric( $value['left'] ?? '' ) ? (float) $value['left'] : '',
			'unit'     => sanitize_key( $value['unit'] ?? 'px' ),
			'isLinked' => (bool) ( $value['isLinked'] ?? true ),
		];
	}

	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'units'       => [ 'px', '%', 'em', 'rem', 'vw', 'vh' ],
			'placeholder' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ],
		] );
	}

	public function get_style_value( mixed $value, array $args = [] ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}
		$css_prop = $args['css_property'] ?? 'margin';
		$unit     = $value['unit'] ?? 'px';
		$sides    = [];
		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$sides[] = is_numeric( $value[ $side ] ?? '' ) ? $value[ $side ] . $unit : '0';
		}
		return sprintf( '%s:%s;', $css_prop, implode( ' ', $sides ) );
	}
}
