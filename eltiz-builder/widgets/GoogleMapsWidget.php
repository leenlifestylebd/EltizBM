<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class GoogleMapsWidget extends WidgetBase {
	public function get_name(): string  { return 'google_maps'; }
	public function get_title(): string { return __( 'Google Maps', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-google-maps'; }
	public function get_keywords(): array { return [ 'map', 'google', 'location', 'embed' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_map', [ 'label' => __( 'Google Maps', 'eltiz-builder' ) ] );
		$this->add_control( 'address', [ 'label'=>__('Location','eltiz-builder'),'type'=>'text','default'=>'London, UK','placeholder'=>'Enter city, address...' ] );
		$this->add_responsive_control( 'height', [ 'label'=>__('Height','eltiz-builder'),'type'=>'slider','units'=>['px'],'default'=>['size'=>400,'unit'=>'px'],'selectors'=>['{{WRAPPER}} .eltiz-google-map'=>'height:{{SIZE}}{{UNIT}};'] ] );
		$this->add_control( 'zoom', [ 'label'=>__('Zoom','eltiz-builder'),'type'=>'slider','default'=>['size'=>10],'min'=>0,'max'=>20,'step'=>1 ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$address = urlencode( sanitize_text_field( $s['address'] ?? 'London' ) );
		$zoom    = absint( $s['zoom']['size'] ?? 10 );

		echo '<div class="eltiz-google-map">';
		echo '<iframe loading="lazy" src="https://maps.google.com/maps?q=' . $address . '&t=m&z=' . $zoom . '&output=embed&iwloc=near" frameborder="0" allowfullscreen aria-label="' . esc_attr__( 'Google Map', 'eltiz-builder' ) . '"></iframe>';
		echo '</div>';
	}
}
