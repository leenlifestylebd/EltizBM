<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class TextareaControl extends ControlBase {
	public function get_type(): string { return 'textarea'; }
	public function get_default_value(): mixed { return ''; }
	public function sanitize( mixed $value ): mixed { return sanitize_textarea_field( (string) $value ); }
}
