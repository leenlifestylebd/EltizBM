<?php
declare( strict_types=1 );

namespace EltizBuilder\LoopBuilder\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class LoopCarouselWidget extends WidgetBase {
	public function get_name(): string  { return 'loop-carousel'; }
	public function get_title(): string { return __( 'Loop Carousel', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-carousel'; }
	public function get_keywords(): array { return [ 'loop', 'carousel', 'slider', 'posts' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_query', [ 'label' => __( 'Query', 'eltiz-builder' ) ] );
		$this->add_control( 'post_type',      [ 'label'=>__('Post Type','eltiz-builder'),    'type'=>'select','options'=>['post'=>'Post','page'=>'Page'],'default'=>'post' ] );
		$this->add_control( 'posts_per_page', [ 'label'=>__('Posts Per Page','eltiz-builder'),'type'=>'number','default'=>6,'min'=>1,'max'=>50 ] );
		$this->add_control( 'orderby',        [ 'label'=>__('Order By','eltiz-builder'),      'type'=>'select','options'=>['date'=>'Date','rand'=>'Random'],'default'=>'date' ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_carousel', [ 'label' => __( 'Carousel Settings', 'eltiz-builder' ) ] );
		$this->add_control( 'slides_to_show',   [ 'label'=>__('Slides to Show','eltiz-builder'),'type'=>'number','default'=>3,'min'=>1,'max'=>6 ] );
		$this->add_control( 'slides_to_scroll', [ 'label'=>__('Slides to Scroll','eltiz-builder'),'type'=>'number','default'=>1,'min'=>1,'max'=>6 ] );
		$this->add_control( 'autoplay',         [ 'label'=>__('Autoplay','eltiz-builder'),'type'=>'switcher','default'=>'' ] );
		$this->add_control( 'autoplay_speed',   [ 'label'=>__('Autoplay Speed (ms)','eltiz-builder'),'type'=>'number','default'=>3000,'min'=>500 ] );
		$this->add_control( 'loop',             [ 'label'=>__('Infinite Loop','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'show_dots',        [ 'label'=>__('Show Dots','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'show_arrows',      [ 'label'=>__('Show Arrows','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();

		$query_args = [
			'post_type'      => sanitize_key( $settings['post_type'] ?? 'post' ),
			'posts_per_page' => absint( $settings['posts_per_page'] ?? 6 ),
			'orderby'        => sanitize_key( $settings['orderby'] ?? 'date' ),
			'order'          => 'DESC',
		];

		$query = new \WP_Query( $query_args );

		$carousel_settings = wp_json_encode( [
			'slidesToShow'   => absint( $settings['slides_to_show'] ?? 3 ),
			'slidesToScroll' => absint( $settings['slides_to_scroll'] ?? 1 ),
			'autoplay'       => 'yes' === ( $settings['autoplay'] ?? '' ),
			'autoplaySpeed'  => absint( $settings['autoplay_speed'] ?? 3000 ),
			'infinite'       => 'yes' === ( $settings['loop'] ?? 'yes' ),
			'dots'           => 'yes' === ( $settings['show_dots'] ?? 'yes' ),
			'arrows'         => 'yes' === ( $settings['show_arrows'] ?? 'yes' ),
		] );

		echo '<div class="eltiz-loop-carousel" data-carousel-settings="' . esc_attr( $carousel_settings ) . '">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo '<div class="eltiz-loop-carousel-item">';
				echo '<article class="eltiz-post-item">';
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'medium', [ 'loading' => 'lazy' ] );
				}
				echo '<h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
				echo '<p>' . esc_html( get_the_excerpt() ) . '</p>';
				echo '</article>';
				echo '</div>';
			}
			wp_reset_postdata();
		}

		echo '</div>';
	}
}
