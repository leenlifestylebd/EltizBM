<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class CounterWidget extends WidgetBase {

	public function get_name(): string  { return 'counter'; }
	public function get_title(): string { return __( 'Counter', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-counter'; }
	public function get_keywords(): array { return [ 'counter', 'number', 'stats' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_counter', [ 'label' => __( 'Counter', 'eltiz-builder' ) ] );

		$this->add_control( 'starting_number', [ 'label'=>__('Starting Number','eltiz-builder'),'type'=>'number','default'=>0,'min'=>0 ] );
		$this->add_control( 'ending_number',   [ 'label'=>__('Ending Number','eltiz-builder'),  'type'=>'number','default'=>100,'min'=>0 ] );
		$this->add_control( 'prefix',          [ 'label'=>__('Number Prefix','eltiz-builder'),   'type'=>'text','default'=>'' ] );
		$this->add_control( 'suffix',          [ 'label'=>__('Number Suffix','eltiz-builder'),   'type'=>'text','default'=>__('Plus','eltiz-builder') ] );
		$this->add_control( 'duration',        [ 'label'=>__('Animation Duration','eltiz-builder'),'type'=>'number','default'=>2000,'min'=>100,'step'=>100 ] );
		$this->add_control( 'title',           [ 'label'=>__('Title','eltiz-builder'),'type'=>'text','default'=>__('Cool Number','eltiz-builder') ] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		echo '<div class="eltiz-counter-wrapper">';
		echo '<div class="eltiz-counter" data-duration="' . absint( $s['duration'] ?? 2000 ) . '" data-to-value="' . absint( $s['ending_number'] ?? 100 ) . '" data-from-value="' . absint( $s['starting_number'] ?? 0 ) . '">';
		echo '<div class="eltiz-counter-number-wrapper">';
		echo '<span class="eltiz-counter-number-prefix">' . esc_html( $s['prefix'] ?? '' ) . '</span>';
		echo '<span class="eltiz-counter-number">' . absint( $s['starting_number'] ?? 0 ) . '</span>';
		echo '<span class="eltiz-counter-number-suffix">' . esc_html( $s['suffix'] ?? '' ) . '</span>';
		echo '</div>';
		if ( ! empty( $s['title'] ) ) {
			echo '<span class="eltiz-counter-title">' . esc_html( $s['title'] ) . '</span>';
		}
		echo '</div>';
		echo '</div>';
	}
}
