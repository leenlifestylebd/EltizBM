<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class IconControl extends ControlBase {
	public function get_type(): string { return 'icon'; }
	public function get_default_value(): mixed { return [ 'value' => '', 'library' => 'solid' ]; }
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		return [
			'value'   => sanitize_text_field( $value['value'] ?? '' ),
			'library' => sanitize_key( $value['library'] ?? 'solid' ),
		];
	}
}
