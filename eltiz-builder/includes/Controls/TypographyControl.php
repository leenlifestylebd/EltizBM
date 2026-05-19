<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class TypographyControl extends ControlBase {

	public function get_type(): string { return 'typography'; }

	public function get_default_value(): mixed {
		return [
			'family'         => '',
			'size'           => [ 'size' => '', 'unit' => 'px' ],
			'weight'         => '',
			'style'          => '',
			'transform'      => '',
			'decoration'     => '',
			'line_height'    => [ 'size' => '', 'unit' => 'em' ],
			'letter_spacing' => [ 'size' => '', 'unit' => 'px' ],
		];
	}

	protected function is_responsive(): bool { return true; }

	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) {
			return $this->get_default_value();
		}

		$allowed_weights    = [ '100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold' ];
		$allowed_styles     = [ 'normal', 'italic', 'oblique' ];
		$allowed_transforms = [ 'none', 'capitalize', 'uppercase', 'lowercase' ];
		$allowed_decorations = [ 'none', 'underline', 'overline', 'line-through' ];

		return [
			'family'         => sanitize_text_field( $value['family'] ?? '' ),
			'size'           => $this->sanitize_size( $value['size'] ?? [] ),
			'weight'         => in_array( $value['weight'] ?? '', $allowed_weights, true ) ? $value['weight'] : '',
			'style'          => in_array( $value['style'] ?? '', $allowed_styles, true ) ? $value['style'] : '',
			'transform'      => in_array( $value['transform'] ?? '', $allowed_transforms, true ) ? $value['transform'] : '',
			'decoration'     => in_array( $value['decoration'] ?? '', $allowed_decorations, true ) ? $value['decoration'] : '',
			'line_height'    => $this->sanitize_size( $value['line_height'] ?? [], 'em' ),
			'letter_spacing' => $this->sanitize_size( $value['letter_spacing'] ?? [], 'px' ),
		];
	}

	private function sanitize_size( mixed $size, string $default_unit = 'px' ): array {
		if ( ! is_array( $size ) ) {
			return [ 'size' => '', 'unit' => $default_unit ];
		}
		return [
			'size' => is_numeric( $size['size'] ?? '' ) ? (float) $size['size'] : '',
			'unit' => sanitize_key( $size['unit'] ?? $default_unit ),
		];
	}

	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'global_link' => true,
			'fields'      => [
				'family', 'size', 'weight', 'style', 'transform',
				'decoration', 'line_height', 'letter_spacing',
			],
		] );
	}
}
