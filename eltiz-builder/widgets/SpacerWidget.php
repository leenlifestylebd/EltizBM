<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class SpacerWidget extends WidgetBase {
	public function get_name(): string  { return 'spacer'; }
	public function get_title(): string { return __( 'Spacer', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-spacer'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_spacer', [ 'label' => __( 'Spacer', 'eltiz-builder' ) ] );
		$this->add_responsive_control( 'space', [ 'label'=>__('Space','eltiz-builder'),'type'=>'slider','units'=>['px','em','vh'],'default'=>['size'=>50,'unit'=>'px'],'selectors'=>['{{WRAPPER}} .eltiz-spacer'=>'height:{{SIZE}}{{UNIT}};'] ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		echo '<div class="eltiz-spacer" aria-hidden="true"></div>';
	}
}
