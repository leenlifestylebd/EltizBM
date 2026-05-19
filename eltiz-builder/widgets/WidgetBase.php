<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

use EltizBuilder\Includes\Controls\ControlsManager;

/**
 * Abstract base class all widgets must extend.
 */
abstract class WidgetBase {

	protected array $settings = [];
	protected ?string $current_skin = null;

	/** Unique widget slug. */
	abstract public function get_name(): string;

	/** Human-readable widget title. */
	abstract public function get_title(): string;

	/** Widget panel category. */
	public function get_categories(): array {
		return [ 'general' ];
	}

	/** Icon shown in the panel (Font Awesome class or inline SVG). */
	public function get_icon(): string {
		return 'eicon-plug';
	}

	/** Register controls in this method using add_control(). */
	abstract protected function register_controls(): void;

	/** Render the widget HTML on the frontend. */
	abstract protected function render(): void;

	/** Render the widget for the live editor (JS template). */
	protected function content_template(): void {}

	/** Widget keywords for search. */
	public function get_keywords(): array {
		return [];
	}

	/** Whether the widget is available in the free tier. */
	public function is_free(): bool {
		return true;
	}

	/** Minimum required capabilities to use this widget. */
	public function get_required_capability(): string {
		return 'edit_posts';
	}

	// ─── Control registration helpers ─────────────────────────────────────────

	private array $controls_stack = [];
	private ?string $current_section = null;
	private ?string $current_tab     = null;

	public function start_controls_section( string $id, array $args = [] ): void {
		$this->current_section = $id;
		$this->controls_stack[ $id ] = [
			'type'     => 'section',
			'id'       => $id,
			'label'    => $args['label'] ?? '',
			'tab'      => $args['tab'] ?? 'content',
			'controls' => [],
		];
	}

	public function end_controls_section(): void {
		$this->current_section = null;
	}

	public function start_controls_tab( string $id, array $args = [] ): void {
		$this->current_tab = $id;
	}

	public function end_controls_tab(): void {
		$this->current_tab = null;
	}

	public function start_controls_tabs( string $id ): void {}

	public function end_controls_tabs(): void {}

	public function add_control( string $id, array $args ): void {
		$control = [
			'id'   => $id,
			'tab'  => $this->current_tab,
		] + $args;

		if ( $this->current_section ) {
			$this->controls_stack[ $this->current_section ]['controls'][ $id ] = $control;
		}
	}

	public function add_responsive_control( string $id, array $args ): void {
		$args['responsive'] = true;
		$this->add_control( $id, $args );
	}

	public function add_group_control( string $type, array $args = [] ): void {
		$this->add_control( $args['name'] ?? $type, array_merge( $args, [ 'type' => $type, 'is_group' => true ] ) );
	}

	// ─── Settings API ─────────────────────────────────────────────────────────

	public function set_settings( array $settings ): void {
		$this->settings = $settings;
	}

	public function get_settings( string $key = '' ): mixed {
		if ( '' === $key ) {
			return $this->settings;
		}
		return $this->settings[ $key ] ?? null;
	}

	public function get_settings_for_display( string $key = '' ): mixed {
		return apply_filters( 'eltiz/widget/settings_for_display', $this->get_settings( $key ), $key, $this );
	}

	// ─── Rendering ────────────────────────────────────────────────────────────

	public function render_widget( array $settings ): void {
		$this->set_settings( $settings );
		$this->before_render();
		$this->render();
		$this->after_render();
	}

	protected function before_render(): void {
		$classes = $this->get_wrapper_classes();
		$attrs   = $this->get_wrapper_attributes();
		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $attrs . '>';
	}

	protected function after_render(): void {
		echo '</div>';
	}

	protected function get_wrapper_classes(): array {
		return [
			'eltiz-widget',
			'eltiz-widget-' . $this->get_name(),
		];
	}

	protected function get_wrapper_attributes(): string {
		return '';
	}

	// ─── Schema ───────────────────────────────────────────────────────────────

	public function get_schema(): array {
		$this->register_controls();
		return [
			'name'       => $this->get_name(),
			'title'      => $this->get_title(),
			'icon'       => $this->get_icon(),
			'categories' => $this->get_categories(),
			'keywords'   => $this->get_keywords(),
			'is_free'    => $this->is_free(),
			'controls'   => $this->controls_stack,
		];
	}

	// ─── Print / escape helpers ───────────────────────────────────────────────

	protected function print_text_editor( string $content ): void {
		echo wp_kses_post( $content );
	}

	protected function get_render_attribute_string( string $element ): string {
		$attrs = $this->render_attributes[ $element ] ?? [];
		$str   = '';
		foreach ( $attrs as $key => $value ) {
			$value = is_array( $value ) ? implode( ' ', $value ) : $value;
			$str  .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}
		return $str;
	}

	private array $render_attributes = [];

	protected function add_render_attribute( string $element, string|array $key, mixed $value = null, bool $overwrite = false ): void {
		if ( is_array( $key ) ) {
			foreach ( $key as $k => $v ) {
				$this->add_render_attribute( $element, $k, $v, $overwrite );
			}
			return;
		}

		if ( $overwrite ) {
			$this->render_attributes[ $element ][ $key ] = (array) $value;
		} else {
			$existing = $this->render_attributes[ $element ][ $key ] ?? [];
			$this->render_attributes[ $element ][ $key ] = array_merge( $existing, (array) $value );
		}
	}
}
