<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class CallToActionWidget extends WidgetBase {
	public function get_name(): string  { return 'call-to-action'; }
	public function get_title(): string { return __( 'Call To Action', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-call-to-action'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_cta', [ 'label' => __( 'Call To Action', 'eltiz-builder' ) ] );
		$this->add_control( 'title',       [ 'label'=>__('Title','eltiz-builder'),'type'=>'text','default'=>__('Make Something Awesome','eltiz-builder') ] );
		$this->add_control( 'description', [ 'label'=>__('Description','eltiz-builder'),'type'=>'textarea','default'=>__('Join us today and build something great.','eltiz-builder') ] );
		$this->add_control( 'button_text', [ 'label'=>__('Button Text','eltiz-builder'),'type'=>'text','default'=>__('Get Started','eltiz-builder') ] );
		$this->add_control( 'button_url',  [ 'label'=>__('Button URL','eltiz-builder'),'type'=>'url','default'=>['url'=>'#'] ] );
		$this->add_control( 'layout',      [ 'label'=>__('Layout','eltiz-builder'),'type'=>'select','options'=>['inline'=>'Inline','stacked'=>'Stacked'],'default'=>'stacked' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$link = $s['button_url'] ?? ['url'=>'#'];
		$target   = !empty($link['is_external']) ? ' target="_blank"' : '';
		$nofollow = !empty($link['nofollow']) ? ' rel="nofollow"' : '';

		echo '<div class="eltiz-cta eltiz-cta-' . esc_attr( $s['layout'] ?? 'stacked' ) . '">';
		echo '<div class="eltiz-cta-content">';
		if ( !empty($s['title']) ) echo '<h2 class="eltiz-cta-title">' . esc_html( $s['title'] ) . '</h2>';
		if ( !empty($s['description']) ) echo '<p class="eltiz-cta-description">' . esc_html( $s['description'] ) . '</p>';
		echo '</div>';
		echo '<div class="eltiz-cta-button-wrap">';
		echo '<a href="' . esc_url( $link['url'] ?? '#' ) . '"' . $target . $nofollow . ' class="eltiz-button">' . esc_html( $s['button_text'] ?? '' ) . '</a>';
		echo '</div>';
		echo '</div>';
	}
}
