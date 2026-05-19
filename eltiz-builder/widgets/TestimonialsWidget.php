<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class TestimonialsWidget extends WidgetBase {

	public function get_name(): string  { return 'testimonials'; }
	public function get_title(): string { return __( 'Testimonials', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-testimonial'; }
	public function get_keywords(): array { return [ 'testimonial', 'review', 'quote' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_testimonials', [ 'label' => __( 'Testimonials', 'eltiz-builder' ) ] );

		$this->add_control( 'testimonials', [
			'label'  => __( 'Items', 'eltiz-builder' ),
			'type'   => 'repeater',
			'fields' => [
				[ 'name' => 'content',    'label' => __( 'Content', 'eltiz-builder' ),     'type' => 'textarea' ],
				[ 'name' => 'name',       'label' => __( 'Name', 'eltiz-builder' ),         'type' => 'text' ],
				[ 'name' => 'job',        'label' => __( 'Title', 'eltiz-builder' ),        'type' => 'text' ],
				[ 'name' => 'image',      'label' => __( 'Image', 'eltiz-builder' ),        'type' => 'media','media_type'=>'image' ],
				[ 'name' => 'link',       'label' => __( 'Link', 'eltiz-builder' ),         'type' => 'url' ],
				[ 'name' => 'rating',     'label' => __( 'Rating (1-5)', 'eltiz-builder' ), 'type' => 'number','min'=>1,'max'=>5,'default'=>5 ],
			],
			'default' => [
				[ 'content' => __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'eltiz-builder' ), 'name' => __( 'John Doe', 'eltiz-builder' ), 'job' => __( 'Designer', 'eltiz-builder' ) ],
			],
			'title_field' => '{{{ name }}}',
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$items    = $settings['testimonials'] ?? [];

		echo '<div class="eltiz-testimonials-wrapper">';

		foreach ( $items as $item ) {
			echo '<div class="eltiz-testimonial-item">';
			if ( ! empty( $item['image']['url'] ) ) {
				echo '<div class="eltiz-testimonial-image">';
				echo '<img src="' . esc_url( $item['image']['url'] ) . '" alt="' . esc_attr( $item['name'] ?? '' ) . '" loading="lazy">';
				echo '</div>';
			}
			echo '<div class="eltiz-testimonial-content">';
			if ( ! empty( $item['rating'] ) ) {
				$rating = min( 5, max( 1, (int) $item['rating'] ) );
				echo '<div class="eltiz-testimonial-rating" aria-label="' . sprintf( esc_attr__( '%d out of 5 stars', 'eltiz-builder' ), $rating ) . '">';
				for ( $i = 1; $i <= 5; $i++ ) {
					echo '<i class="fas fa-star' . ( $i > $rating ? ' eltiz-star-empty' : '' ) . '" aria-hidden="true"></i>';
				}
				echo '</div>';
			}
			echo '<blockquote class="eltiz-testimonial-text"><p>' . esc_html( $item['content'] ?? '' ) . '</p></blockquote>';
			echo '<div class="eltiz-testimonial-meta">';
			echo '<span class="eltiz-testimonial-name">' . esc_html( $item['name'] ?? '' ) . '</span>';
			if ( ! empty( $item['job'] ) ) {
				echo '<span class="eltiz-testimonial-job">' . esc_html( $item['job'] ) . '</span>';
			}
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}

		echo '</div>';
	}
}
