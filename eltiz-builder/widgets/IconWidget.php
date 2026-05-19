<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class IconWidget extends WidgetBase {
	public function get_name(): string  { return 'icon'; }
	public function get_title(): string { return __( 'Icon', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-star'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_icon', [ 'label' => __( 'Icon', 'eltiz-builder' ) ] );
		$this->add_control( 'selected_icon', [ 'label'=>__('Icon','eltiz-builder'),'type'=>'icon','default'=>['value'=>'fas fa-star','library'=>'solid'] ] );
		$this->add_control( 'size', [ 'label'=>__('Size','eltiz-builder'),'type'=>'slider','units'=>['px','em'],'selectors'=>['{{WRAPPER}} .eltiz-icon'=>'font-size:{{SIZE}}{{UNIT}};'] ] );
		$this->add_control( 'color', [ 'label'=>__('Color','eltiz-builder'),'type'=>'color','selectors'=>['{{WRAPPER}} .eltiz-icon'=>'color:{{VALUE}};'] ] );
		$this->add_control( 'link', [ 'label'=>__('Link','eltiz-builder'),'type'=>'url','default'=>['url'=>''] ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$icon = $s['selected_icon']['value'] ?? 'fas fa-star';
		$link = $s['link'] ?? [];

		$html = '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i>';

		if ( ! empty( $link['url'] ) ) {
			$target  = ! empty( $link['is_external'] ) ? ' target="_blank"' : '';
			$nofollow = ! empty( $link['nofollow'] ) ? ' rel="nofollow"' : '';
			$html = '<a href="' . esc_url( $link['url'] ) . '"' . $target . $nofollow . '>' . $html . '</a>';
		}

		echo '<div class="eltiz-icon">' . $html . '</div>';
	}
}
