<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class RepeatControl extends ControlBase {
	public function get_type(): string { return 'repeater'; }
	public function get_default_value(): mixed { return []; }
	public function sanitize( mixed $value ): mixed {
		return is_array( $value ) ? array_values( $value ) : [];
	}
	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'title_field' => '',
			'fields'      => [],
			'max_items'   => 0,
		] );
	}
}
