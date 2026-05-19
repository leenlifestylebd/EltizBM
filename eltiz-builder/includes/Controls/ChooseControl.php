<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class ChooseControl extends ControlBase {
	public function get_type(): string { return 'choose'; }
	public function get_default_value(): mixed { return ''; }
	public function sanitize( mixed $value ): mixed { return sanitize_key( (string) $value ); }
	public function get_schema(): array {
		return array_merge( parent::get_schema(), [ 'options' => [], 'toggle' => true ] );
	}
}
