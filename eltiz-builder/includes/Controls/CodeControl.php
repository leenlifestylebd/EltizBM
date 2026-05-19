<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class CodeControl extends ControlBase {
	public function get_type(): string { return 'code'; }
	public function get_default_value(): mixed { return ''; }
	public function sanitize( mixed $value ): mixed {
		return wp_check_invalid_utf8( (string) $value );
	}
	public function get_schema(): array {
		return array_merge( parent::get_schema(), [ 'language' => 'css', 'rows' => 10 ] );
	}
}
