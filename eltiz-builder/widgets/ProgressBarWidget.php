<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class ProgressBarWidget extends WidgetBase {
	public function get_name(): string  { return 'progress'; }
	public function get_title(): string { return __( 'Progress Bar', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-skill-bar'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_progress', [ 'label' => __( 'Progress Bar', 'eltiz-builder' ) ] );
		$this->add_control( 'title',   [ 'label'=>__('Title','eltiz-builder'),'type'=>'text','default'=>__('Skill','eltiz-builder') ] );
		$this->add_control( 'percent', [ 'label'=>__('Percentage','eltiz-builder'),'type'=>'slider','default'=>['size'=>60,'unit'=>'%'],'units'=>['%'],'min'=>0,'max'=>100 ] );
		$this->add_control( 'display_percentage', [ 'label'=>__('Display Percentage','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$percent = absint( $s['percent']['size'] ?? 60 );

		echo '<div class="eltiz-progress-bar-wrapper">';
		echo '<div class="eltiz-progress-bar-title">';
		echo '<span class="eltiz-title-inner">' . esc_html( $s['title'] ?? '' ) . '</span>';
		if ( 'yes' === ( $s['display_percentage'] ?? 'yes' ) ) {
			echo '<span class="eltiz-progress-percentage">' . $percent . '%</span>';
		}
		echo '</div>';
		echo '<div class="eltiz-progress-bar" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100">';
		echo '<div class="eltiz-progress-bar-inner" data-value="' . $percent . '" style="width:0%"></div>';
		echo '</div>';
		echo '</div>';
	}
}
