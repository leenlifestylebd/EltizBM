<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class DateTimeControl extends ControlBase {
	public function get_type(): string { return 'date_time'; }
	public function get_default_value(): mixed { return ''; }
	public function sanitize( mixed $value ): mixed {
		$timestamp = strtotime( (string) $value );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : '';
	}
	public function get_schema(): array {
		return array_merge( parent::get_schema(), [ 'pickers' => [ 'date', 'time' ] ] );
	}
}
