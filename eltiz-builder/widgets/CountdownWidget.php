<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class CountdownWidget extends WidgetBase {

	public function get_name(): string  { return 'countdown'; }
	public function get_title(): string { return __( 'Countdown', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-countdown'; }
	public function get_keywords(): array { return [ 'countdown', 'timer', 'time', 'clock' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_countdown', [ 'label' => __( 'Countdown', 'eltiz-builder' ) ] );

		$this->add_control( 'due_date', [
			'label'       => __( 'Due Date', 'eltiz-builder' ),
			'type'        => 'date_time',
			'default'     => gmdate( 'Y-m-d H:i:s', strtotime( '+30 days' ) ),
			'description' => __( 'Set the time to count down to.', 'eltiz-builder' ),
		] );

		$this->add_control( 'show_days',    [ 'label'=>__('Days','eltiz-builder'),    'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'show_hours',   [ 'label'=>__('Hours','eltiz-builder'),   'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'show_minutes', [ 'label'=>__('Minutes','eltiz-builder'), 'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'show_seconds', [ 'label'=>__('Seconds','eltiz-builder'), 'type'=>'switcher','default'=>'yes' ] );

		$this->add_control( 'label_days',    [ 'label'=>__('Days Label','eltiz-builder'),    'type'=>'text','default'=>__('Days','eltiz-builder') ] );
		$this->add_control( 'label_hours',   [ 'label'=>__('Hours Label','eltiz-builder'),   'type'=>'text','default'=>__('Hours','eltiz-builder') ] );
		$this->add_control( 'label_minutes', [ 'label'=>__('Minutes Label','eltiz-builder'), 'type'=>'text','default'=>__('Minutes','eltiz-builder') ] );
		$this->add_control( 'label_seconds', [ 'label'=>__('Seconds Label','eltiz-builder'), 'type'=>'text','default'=>__('Seconds','eltiz-builder') ] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$due_date = esc_attr( $s['due_date'] ?? '' );

		echo '<div class="eltiz-countdown-wrapper eltiz-countdown-items" data-date="' . $due_date . '">';

		$units = [
			'days'    => $s['label_days'] ?? __( 'Days', 'eltiz-builder' ),
			'hours'   => $s['label_hours'] ?? __( 'Hours', 'eltiz-builder' ),
			'minutes' => $s['label_minutes'] ?? __( 'Minutes', 'eltiz-builder' ),
			'seconds' => $s['label_seconds'] ?? __( 'Seconds', 'eltiz-builder' ),
		];

		foreach ( $units as $unit => $label ) {
			if ( 'yes' !== ( $s[ 'show_' . $unit ] ?? 'yes' ) ) {
				continue;
			}
			echo '<div class="eltiz-countdown-item eltiz-countdown-' . esc_attr( $unit ) . '">';
			echo '<span class="eltiz-countdown-digits" aria-live="polite">00</span>';
			echo '<span class="eltiz-countdown-label">' . esc_html( $label ) . '</span>';
			echo '</div>';
		}

		echo '</div>';
	}
}
