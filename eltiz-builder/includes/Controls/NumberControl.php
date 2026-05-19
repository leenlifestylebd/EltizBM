<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class NumberControl extends ControlBase {
	public function get_type(): string { return 'number'; }
	public function get_default_value(): mixed { return ''; }
	public function sanitize( mixed $value ): mixed { return is_numeric($value) ? (float)$value : ''; }
	public function get_schema(): array {
		return array_merge( parent::get_schema(), [ 'min' => null, 'max' => null, 'step' => 1 ] );
	}
}
