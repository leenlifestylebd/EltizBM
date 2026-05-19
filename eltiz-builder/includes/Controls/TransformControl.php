<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class TransformControl extends ControlBase {
	public function get_type(): string { return 'transform'; }
	public function get_default_value(): mixed {
		return [
			'translateX' => [ 'size'=>0,'unit'=>'px' ],
			'translateY' => [ 'size'=>0,'unit'=>'px' ],
			'scaleX'     => 1.0,
			'scaleY'     => 1.0,
			'rotate'     => 0.0,
			'skewX'      => 0.0,
			'skewY'      => 0.0,
		];
	}
	protected function is_responsive(): bool { return true; }
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		return [
			'translateX' => [ 'size' => (float)($value['translateX']['size']??0), 'unit' => sanitize_key($value['translateX']['unit']??'px') ],
			'translateY' => [ 'size' => (float)($value['translateY']['size']??0), 'unit' => sanitize_key($value['translateY']['unit']??'px') ],
			'scaleX'     => (float)($value['scaleX']??1),
			'scaleY'     => (float)($value['scaleY']??1),
			'rotate'     => (float)($value['rotate']??0),
			'skewX'      => (float)($value['skewX']??0),
			'skewY'      => (float)($value['skewY']??0),
		];
	}
}
