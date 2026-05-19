<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class TextShadowControl extends ControlBase {
	public function get_type(): string { return 'text_shadow'; }
	public function get_default_value(): mixed {
		return [ 'horizontal'=>0,'vertical'=>0,'blur'=>0,'color'=>'' ];
	}
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		return [
			'horizontal' => (float) ( $value['horizontal'] ?? 0 ),
			'vertical'   => (float) ( $value['vertical'] ?? 0 ),
			'blur'       => (float) ( $value['blur'] ?? 0 ),
			'color'      => sanitize_hex_color( $value['color'] ?? '' ) ?? '',
		];
	}
}
