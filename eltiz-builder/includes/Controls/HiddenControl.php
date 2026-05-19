<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class HiddenControl extends ControlBase {
	public function get_type(): string { return 'hidden'; }
	public function get_default_value(): mixed { return ''; }
	public function sanitize( mixed $value ): mixed { return sanitize_text_field( (string) $value ); }
}
