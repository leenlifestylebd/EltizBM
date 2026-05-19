<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

/**
 * Central registry for all editor controls.
 */
final class ControlsManager {

	private array $controls = [];

	public function __construct() {
		$this->register_core_controls();
	}

	private function register_core_controls(): void {
		$core = [
			new TextControl(),
			new TextareaControl(),
			new SelectControl(),
			new ColorControl(),
			new SliderControl(),
			new DimensionsControl(),
			new TypographyControl(),
			new MediaControl(),
			new SwitcherControl(),
			new IconControl(),
			new RepeatControl(),
			new CodeControl(),
			new HiddenControl(),
			new ChooseControl(),
			new BorderControl(),
			new BoxShadowControl(),
			new TextShadowControl(),
			new BackgroundControl(),
			new TransformControl(),
			new AnimationControl(),
			new NumberControl(),
			new DateTimeControl(),
			new URLControl(),
			new GapControl(),
			new GridControl(),
		];

		foreach ( $core as $control ) {
			$this->register( $control );
		}

		do_action( 'eltiz/controls/register', $this );
	}

	public function register( ControlBase $control ): void {
		$this->controls[ $control->get_type() ] = $control;
	}

	public function get( string $type ): ?ControlBase {
		return $this->controls[ $type ] ?? null;
	}

	public function get_all(): array {
		return $this->controls;
	}

	public function get_schemas(): array {
		$schemas = [];
		foreach ( $this->controls as $type => $control ) {
			$schemas[ $type ] = $control->get_schema();
		}
		return $schemas;
	}
}
