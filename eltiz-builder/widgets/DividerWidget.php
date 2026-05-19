<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class DividerWidget extends WidgetBase {
	public function get_name(): string  { return 'divider'; }
	public function get_title(): string { return __( 'Divider', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-divider'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_divider', [ 'label' => __( 'Divider', 'eltiz-builder' ) ] );
		$this->add_control( 'style', [ 'label'=>__('Style','eltiz-builder'),'type'=>'select','options'=>['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted','double'=>'Double'],'default'=>'solid','selectors'=>['{{WRAPPER}} .eltiz-divider'=>'border-top-style: {{VALUE}};'] ] );
		$this->add_control( 'color', [ 'label'=>__('Color','eltiz-builder'),'type'=>'color','selectors'=>['{{WRAPPER}} .eltiz-divider'=>'border-top-color: {{VALUE}};'] ] );
		$this->add_responsive_control( 'weight', [ 'label'=>__('Weight','eltiz-builder'),'type'=>'slider','units'=>['px'],'selectors'=>['{{WRAPPER}} .eltiz-divider'=>'border-top-width: {{SIZE}}{{UNIT}};'] ] );
		$this->add_control( 'look', [ 'label'=>__('Separator Type','eltiz-builder'),'type'=>'select','options'=>['line'=>__('Line','eltiz-builder'),'pattern'=>__('Pattern','eltiz-builder')],'default'=>'line' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		echo '<div class="eltiz-divider" role="separator"></div>';
	}
}
