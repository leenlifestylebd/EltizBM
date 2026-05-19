<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

/**
 * Abstract base for all editor controls.
 */
abstract class ControlBase {

	/** Returns the unique control type slug. */
	abstract public function get_type(): string;

	/** Returns the default value when none is set. */
	abstract public function get_default_value(): mixed;

	/**
	 * Returns the control schema sent to the editor.
	 * Subclasses should call parent and merge their extra keys.
	 */
	public function get_schema(): array {
		return [
			'type'        => $this->get_type(),
			'default'     => $this->get_default_value(),
			'responsive'  => $this->is_responsive(),
			'inheritable' => $this->is_inheritable(),
		];
	}

	protected function is_responsive(): bool {
		return false;
	}

	protected function is_inheritable(): bool {
		return false;
	}

	/**
	 * Sanitize a value coming from user input.
	 */
	public function sanitize( mixed $value ): mixed {
		return $value;
	}

	/**
	 * Render the CSS output for this control value, if applicable.
	 * Return empty string when the control does not produce CSS.
	 */
	public function get_style_value( mixed $value, array $args = [] ): string {
		return '';
	}
}
