<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class GapControl extends ControlBase {
	public function get_type(): string { return 'gap'; }
	public function get_default_value(): mixed { return [ 'row'=>['size'=>'','unit'=>'px'], 'column'=>['size'=>'','unit'=>'px'] ]; }
	protected function is_responsive(): bool { return true; }
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		$s = fn($v) => [ 'size' => is_numeric($v['size']??'') ? (float)$v['size'] : '', 'unit' => sanitize_key($v['unit']??'px') ];
		return [ 'row' => $s($value['row']??[]), 'column' => $s($value['column']??[]) ];
	}
}
