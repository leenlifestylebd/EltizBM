<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class ShortcodeWidget extends WidgetBase {
	public function get_name(): string  { return 'shortcode'; }
	public function get_title(): string { return __( 'Shortcode', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-shortcode'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_shortcode', [ 'label' => __( 'Shortcode', 'eltiz-builder' ) ] );
		$this->add_control( 'shortcode', [ 'label'=>__('Enter your shortcode','eltiz-builder'),'type'=>'textarea','default'=>'','placeholder'=>'[my-shortcode]' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$shortcode = trim( $this->get_settings( 'shortcode' ) ?? '' );
		if ( ! $shortcode ) return;
		echo '<div class="eltiz-shortcode">' . do_shortcode( $shortcode ) . '</div>';
	}
}
