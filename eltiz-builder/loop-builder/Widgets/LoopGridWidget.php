<?php
declare( strict_types=1 );

namespace EltizBuilder\LoopBuilder\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class LoopGridWidget extends WidgetBase {

	public function get_name(): string  { return 'loop-grid'; }
	public function get_title(): string { return __( 'Loop Grid', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-loop-builder'; }
	public function get_keywords(): array { return [ 'loop', 'posts', 'grid', 'blog', 'dynamic' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_query', [ 'label' => __( 'Query', 'eltiz-builder' ) ] );

		$this->add_control( 'post_type', [
			'label'   => __( 'Post Type', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => $this->get_post_types(),
			'default' => 'post',
		] );

		$this->add_control( 'posts_per_page', [ 'label'=>__('Posts Per Page','eltiz-builder'),'type'=>'number','default'=>6,'min'=>1,'max'=>100 ] );
		$this->add_control( 'orderby',        [ 'label'=>__('Order By','eltiz-builder'),'type'=>'select','options'=>['date'=>'Date','title'=>'Title','menu_order'=>'Menu Order','rand'=>'Random','modified'=>'Modified'],'default'=>'date' ] );
		$this->add_control( 'order',          [ 'label'=>__('Order','eltiz-builder'),'type'=>'select','options'=>['DESC'=>'DESC','ASC'=>'ASC'],'default'=>'DESC' ] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Layout', 'eltiz-builder' ) ] );

		$this->add_responsive_control( 'columns', [
			'label'   => __( 'Columns', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ '1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6' ],
			'default' => '3',
			'selectors' => [ '{{WRAPPER}} .eltiz-loop-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);' ],
		] );

		$this->add_control( 'pagination', [
			'label'   => __( 'Pagination', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'none'=>__('None','eltiz-builder'),'numbers'=>__('Numbers','eltiz-builder'),'load_more'=>__('Load More','eltiz-builder'),'infinite'=>__('Infinite Scroll','eltiz-builder') ],
			'default' => 'none',
		] );

		$this->add_control( 'no_results_text', [ 'label'=>__('No Results Text','eltiz-builder'),'type'=>'text','default'=>__('No posts found.','eltiz-builder') ] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$loop_mgr = \EltizBuilder\Core\Plugin::instance()->make( 'loop_builder' );
		$query_args = $loop_mgr->build_query( $settings );
		$query = new \WP_Query( $query_args );

		echo '<div class="eltiz-loop eltiz-loop-grid"';
		echo ' data-query="' . esc_attr( wp_json_encode( $query_args ) ) . '"';
		echo ' data-pagination="' . esc_attr( $settings['pagination'] ?? 'none' ) . '">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo '<div class="eltiz-loop-item">';
				echo '<article class="eltiz-post-item">';

				if ( has_post_thumbnail() ) {
					echo '<div class="eltiz-post-thumbnail">';
					echo '<a href="' . esc_url( get_permalink() ) . '">';
					the_post_thumbnail( 'medium' );
					echo '</a>';
					echo '</div>';
				}

				echo '<div class="eltiz-post-body">';
				echo '<h3 class="eltiz-post-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
				echo '<div class="eltiz-post-meta">';
				echo '<span class="eltiz-post-date">' . esc_html( get_the_date() ) . '</span>';
				echo '</div>';
				echo '<div class="eltiz-post-excerpt">' . wp_kses_post( get_the_excerpt() ) . '</div>';
				echo '<a href="' . esc_url( get_permalink() ) . '" class="eltiz-button eltiz-post-read-more">' . esc_html__( 'Read More', 'eltiz-builder' ) . '</a>';
				echo '</div>';

				echo '</article>';
				echo '</div>';
			}
			wp_reset_postdata();
		} else {
			echo '<p class="eltiz-no-results">' . esc_html( $settings['no_results_text'] ?? __( 'No posts found.', 'eltiz-builder' ) ) . '</p>';
		}

		// Pagination.
		if ( 'numbers' === ( $settings['pagination'] ?? 'none' ) ) {
			echo '<div class="eltiz-loop-pagination">';
			$pagination_args = [
				'total'   => $query->max_num_pages,
				'current' => max( 1, get_query_var( 'paged' ) ),
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
			];
			echo paginate_links( $pagination_args );
			echo '</div>';
		}

		if ( 'load_more' === ( $settings['pagination'] ?? 'none' ) && $query->max_num_pages > 1 ) {
			echo '<div class="eltiz-loop-load-more-wrap">';
			echo '<button class="eltiz-button eltiz-load-more" data-max-pages="' . $query->max_num_pages . '" data-page="1">' . esc_html__( 'Load More', 'eltiz-builder' ) . '</button>';
			echo '</div>';
		}

		echo '</div>';
	}

	private function get_post_types(): array {
		$types  = get_post_types( [ 'public' => true ], 'objects' );
		$result = [];
		foreach ( $types as $type ) {
			$result[ $type->name ] = $type->labels->singular_name;
		}
		return $result;
	}
}
