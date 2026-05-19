<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class TextControl extends ControlBase {

	public function get_type(): string { return 'text'; }

	public function get_default_value(): mixed { return ''; }

	public function sanitize( mixed $value ): mixed {
		return sanitize_text_field( (string) $value );
	}

	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'placeholder' => '',
			'input_type'  => 'text',
		] );
	}
}
