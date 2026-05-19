<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class SocialIconsWidget extends WidgetBase {
	public function get_name(): string  { return 'social-icons'; }
	public function get_title(): string { return __( 'Social Icons', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-social-icons'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_social_icon', [ 'label' => __( 'Social Icons', 'eltiz-builder' ) ] );
		$this->add_control( 'social_icon_list', [
			'label'  => __( 'Social Icons', 'eltiz-builder' ),
			'type'   => 'repeater',
			'fields' => [
				[ 'name'=>'social_icon','label'=>__('Icon','eltiz-builder'),'type'=>'icon','default'=>['value'=>'fab fa-facebook','library'=>'brands'] ],
				[ 'name'=>'link',       'label'=>__('Link','eltiz-builder'),'type'=>'url','default'=>['url'=>'','is_external'=>true] ],
				[ 'name'=>'item_icon_color','label'=>__('Color','eltiz-builder'),'type'=>'color' ],
			],
			'default' => [
				[ 'social_icon'=>['value'=>'fab fa-facebook','library'=>'brands'], 'link'=>['url'=>'https://facebook.com'] ],
				[ 'social_icon'=>['value'=>'fab fa-twitter','library'=>'brands'],  'link'=>['url'=>'https://twitter.com'] ],
				[ 'social_icon'=>['value'=>'fab fa-instagram','library'=>'brands'],'link'=>['url'=>'https://instagram.com'] ],
			],
			'title_field' => '<i class="{{ social_icon.value }}" aria-hidden="true"></i>',
		] );
		$this->add_responsive_control( 'align', [ 'label'=>__('Alignment','eltiz-builder'),'type'=>'choose','options'=>['left'=>['title'=>'Left','icon'=>'eicon-text-align-left'],'center'=>['title'=>'Center','icon'=>'eicon-text-align-center'],'right'=>['title'=>'Right','icon'=>'eicon-text-align-right']],'default'=>'center','selectors'=>['{{WRAPPER}}'=>'text-align:{{VALUE}};'] ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = $s['social_icon_list'] ?? [];

		echo '<div class="eltiz-social-icons-wrapper">';
		echo '<ul class="eltiz-social-icons">';

		foreach ( $items as $item ) {
			$icon   = $item['social_icon']['value'] ?? '';
			$link   = $item['link'] ?? [];
			$target = ! empty( $link['is_external'] ) ? ' target="_blank"' : '';
			$rel    = ' rel="noopener noreferrer' . ( ! empty( $link['nofollow'] ) ? ' nofollow' : '' ) . '"';

			echo '<li class="eltiz-social-icon">';
			echo '<a href="' . esc_url( $link['url'] ?? '#' ) . '"' . $target . $rel . ' aria-label="' . esc_attr( $icon ) . '">';
			echo '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i>';
			echo '</a>';
			echo '</li>';
		}

		echo '</ul>';
		echo '</div>';
	}
}
