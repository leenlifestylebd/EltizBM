<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class BoxShadowControl extends ControlBase {
	public function get_type(): string { return 'box_shadow'; }
	public function get_default_value(): mixed {
		return [ 'horizontal'=>0,'vertical'=>0,'blur'=>0,'spread'=>0,'color'=>'','position'=>'' ];
	}
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		return [
			'horizontal' => (float) ( $value['horizontal'] ?? 0 ),
			'vertical'   => (float) ( $value['vertical'] ?? 0 ),
			'blur'       => (float) ( $value['blur'] ?? 0 ),
			'spread'     => (float) ( $value['spread'] ?? 0 ),
			'color'      => sanitize_hex_color( $value['color'] ?? '' ) ?? '',
			'position'   => in_array( $value['position'] ?? '', [ '', 'inset' ], true ) ? $value['position'] : '',
		];
	}
}
