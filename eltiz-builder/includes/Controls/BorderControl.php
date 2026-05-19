<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class BorderControl extends ControlBase {
	public function get_type(): string { return 'border'; }
	public function get_default_value(): mixed {
		return [ 'width' => [ 'top'=>'','right'=>'','bottom'=>'','left'=>'' ], 'color' => '', 'style' => '' ];
	}
	protected function is_responsive(): bool { return true; }
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		$styles = [ 'none','solid','dashed','dotted','double','groove','ridge','inset','outset' ];
		return [
			'width' => [
				'top'    => is_numeric( $value['width']['top']    ?? '' ) ? (float) $value['width']['top']    : '',
				'right'  => is_numeric( $value['width']['right']  ?? '' ) ? (float) $value['width']['right']  : '',
				'bottom' => is_numeric( $value['width']['bottom'] ?? '' ) ? (float) $value['width']['bottom'] : '',
				'left'   => is_numeric( $value['width']['left']   ?? '' ) ? (float) $value['width']['left']   : '',
			],
			'color' => sanitize_hex_color( $value['color'] ?? '' ) ?? '',
			'style' => in_array( $value['style'] ?? '', $styles, true ) ? $value['style'] : '',
		];
	}
}
