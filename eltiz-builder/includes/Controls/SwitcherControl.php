<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class SwitcherControl extends ControlBase {

	public function get_type(): string { return 'switcher'; }

	public function get_default_value(): mixed { return ''; }

	public function sanitize( mixed $value ): mixed {
		return $value ? 'yes' : '';
	}

	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'label_on'  => __( 'Yes', 'eltiz-builder' ),
			'label_off' => __( 'No', 'eltiz-builder' ),
			'return_value' => 'yes',
		] );
	}
}
