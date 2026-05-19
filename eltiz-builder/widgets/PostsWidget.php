<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class PostsWidget extends WidgetBase {
	public function get_name(): string  { return 'posts'; }
	public function get_title(): string { return __( 'Posts', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-posts-grid'; }
	public function get_keywords(): array { return [ 'posts', 'blog', 'grid', 'archive' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Posts', 'eltiz-builder' ) ] );
		$this->add_control( 'layout',        [ 'label'=>__('Layout','eltiz-builder'),'type'=>'select','options'=>['grid'=>'Grid','list'=>'List','masonry'=>'Masonry'],'default'=>'grid' ] );
		$this->add_responsive_control( 'columns', [ 'label'=>__('Columns','eltiz-builder'),'type'=>'select','options'=>['1'=>'1','2'=>'2','3'=>'3','4'=>'4'],'default'=>'3','selectors'=>['{{WRAPPER}} .eltiz-posts-grid'=>'grid-template-columns:repeat({{VALUE}},1fr);'] ] );
		$this->add_control( 'posts_per_page', [ 'label'=>__('Posts Per Page','eltiz-builder'),'type'=>'number','default'=>6,'min'=>1,'max'=>100 ] );
		$this->add_control( 'post_type',      [ 'label'=>__('Post Type','eltiz-builder'),'type'=>'select','options'=>['post'=>'Post','page'=>'Page'],'default'=>'post' ] );
		$this->add_control( 'orderby',        [ 'label'=>__('Order By','eltiz-builder'),'type'=>'select','options'=>['date'=>'Date','title'=>'Title','rand'=>'Random'],'default'=>'date' ] );
		$this->add_control( 'show_excerpt',   [ 'label'=>__('Show Excerpt','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'show_read_more', [ 'label'=>__('Read More','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'excerpt_length', [ 'label'=>__('Excerpt Length','eltiz-builder'),'type'=>'number','default'=>15,'min'=>0 ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$query = new \WP_Query( [
			'post_type'      => sanitize_key( $s['post_type'] ?? 'post' ),
			'posts_per_page' => absint( $s['posts_per_page'] ?? 6 ),
			'orderby'        => sanitize_key( $s['orderby'] ?? 'date' ),
			'order'          => 'DESC',
		] );

		$layout = sanitize_key( $s['layout'] ?? 'grid' );

		echo '<div class="eltiz-posts eltiz-posts-' . esc_attr( $layout ) . ' eltiz-posts-grid">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo '<article class="eltiz-post-item">';
				if ( has_post_thumbnail() ) {
					echo '<div class="eltiz-post-thumbnail"><a href="' . esc_url( get_permalink() ) . '">';
					the_post_thumbnail( 'medium', [ 'loading' => 'lazy' ] );
					echo '</a></div>';
				}
				echo '<div class="eltiz-post-content">';
				echo '<h3 class="eltiz-post-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
				echo '<div class="eltiz-post-meta"><span class="eltiz-post-date">' . esc_html( get_the_date() ) . '</span></div>';
				if ( 'yes' === ( $s['show_excerpt'] ?? 'yes' ) ) {
					$length = absint( $s['excerpt_length'] ?? 15 );
					echo '<p class="eltiz-post-excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), $length ) ) . '</p>';
				}
				if ( 'yes' === ( $s['show_read_more'] ?? 'yes' ) ) {
					echo '<a href="' . esc_url( get_permalink() ) . '" class="eltiz-post-read-more">' . esc_html__( 'Read More', 'eltiz-builder' ) . '</a>';
				}
				echo '</div>';
				echo '</article>';
			}
			wp_reset_postdata();
		}

		echo '</div>';
	}
}
