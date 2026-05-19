<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class IconBoxWidget extends WidgetBase {
	public function get_name(): string  { return 'icon-box'; }
	public function get_title(): string { return __( 'Icon Box', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-icon-box'; }
	public function get_keywords(): array { return [ 'icon', 'box', 'feature' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_icon', [ 'label' => __( 'Icon Box', 'eltiz-builder' ) ] );
		$this->add_control( 'selected_icon', [ 'label'=>__('Icon','eltiz-builder'),'type'=>'icon','default'=>['value'=>'fas fa-star','library'=>'solid'] ] );
		$this->add_control( 'title_text',    [ 'label'=>__('Title','eltiz-builder'),'type'=>'text','default'=>__('This is the heading','eltiz-builder') ] );
		$this->add_control( 'description_text', [ 'label'=>__('Description','eltiz-builder'),'type'=>'textarea','default'=>__('Lorem ipsum dolor sit amet consectetur adipiscing elit sed.','eltiz-builder') ] );
		$this->add_control( 'link',          [ 'label'=>__('Link','eltiz-builder'),'type'=>'url','default'=>['url'=>''] ] );
		$this->add_control( 'position',      [ 'label'=>__('Icon Position','eltiz-builder'),'type'=>'choose','options'=>['left'=>['title'=>'Left','icon'=>'eicon-h-align-left'],'top'=>['title'=>'Top','icon'=>'eicon-v-align-top'],'right'=>['title'=>'Right','icon'=>'eicon-h-align-right']],'default'=>'top' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$icon = $s['selected_icon']['value'] ?? '';
		$link = $s['link'] ?? [];

		echo '<div class="eltiz-icon-box eltiz-icon-box-' . esc_attr( $s['position'] ?? 'top' ) . '">';

		if ( $icon ) {
			echo '<div class="eltiz-icon-box-icon">';
			$html = '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i>';
			if ( ! empty( $link['url'] ) ) {
				$html = '<a href="' . esc_url( $link['url'] ) . '">' . $html . '</a>';
			}
			echo $html;
			echo '</div>';
		}

		echo '<div class="eltiz-icon-box-content">';
		if ( ! empty( $s['title_text'] ) ) {
			echo '<h3 class="eltiz-icon-box-title">';
			if ( ! empty( $link['url'] ) ) {
				echo '<a href="' . esc_url( $link['url'] ) . '">' . esc_html( $s['title_text'] ) . '</a>';
			} else {
				echo esc_html( $s['title_text'] );
			}
			echo '</h3>';
		}
		if ( ! empty( $s['description_text'] ) ) {
			echo '<p class="eltiz-icon-box-description">' . esc_html( $s['description_text'] ) . '</p>';
		}
		echo '</div>';
		echo '</div>';
	}
}
