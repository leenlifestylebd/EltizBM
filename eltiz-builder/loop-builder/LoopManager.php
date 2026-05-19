<?php
declare( strict_types=1 );

namespace EltizBuilder\LoopBuilder;

/**
 * Loop Builder — dynamic query grids, carousels, infinite scroll.
 */
final class LoopManager {

	public function init(): void {
		add_action( 'eltiz/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'wp_ajax_eltiz_loop_load_more',        [ $this, 'ajax_load_more' ] );
		add_action( 'wp_ajax_nopriv_eltiz_loop_load_more', [ $this, 'ajax_load_more' ] );
		do_action( 'eltiz/loop_builder/init', $this );
	}

	public function register_widgets( \EltizBuilder\Widgets\WidgetManager $manager ): void {
		$manager->register( new Widgets\LoopGridWidget() );
		$manager->register( new Widgets\LoopCarouselWidget() );
	}

	public function ajax_load_more(): void {
		check_ajax_referer( 'eltiz_frontend', 'nonce' );

		$query_args = json_decode( wp_unslash( $_POST['query'] ?? '' ), true );
		$page       = absint( $_POST['page'] ?? 2 );
		$template   = sanitize_text_field( wp_unslash( $_POST['template'] ?? '' ) );

		if ( ! is_array( $query_args ) ) {
			wp_send_json_error();
		}

		$query_args['paged'] = $page;

		$query = new \WP_Query( $this->sanitize_query_args( $query_args ) );

		ob_start();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				// Render loop template.
				echo $this->render_loop_item( get_the_ID(), $template );
			}
			wp_reset_postdata();
		}

		$html = ob_get_clean();

		wp_send_json_success( [
			'html'      => $html,
			'max_pages' => $query->max_num_pages,
			'found'     => $query->found_posts,
		] );
	}

	private function render_loop_item( int $post_id, string $template ): string {
		return apply_filters( 'eltiz/loop/item_html', '', $post_id, $template );
	}

	public function build_query( array $settings ): array {
		$args = [
			'post_type'      => sanitize_key( $settings['post_type'] ?? 'post' ),
			'posts_per_page' => absint( $settings['posts_per_page'] ?? 6 ),
			'orderby'        => sanitize_key( $settings['orderby'] ?? 'date' ),
			'order'          => in_array( $settings['order'] ?? 'DESC', ['ASC','DESC'], true ) ? $settings['order'] : 'DESC',
			'paged'          => max( 1, absint( get_query_var('paged') ?: ($_POST['page'] ?? 1) ) ),
		];

		if ( ! empty( $settings['taxonomy'] ) && ! empty( $settings['terms'] ) ) {
			$args['tax_query'] = [[
				'taxonomy' => sanitize_key( $settings['taxonomy'] ),
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', (array) $settings['terms'] ),
			]];
		}

		if ( ! empty( $settings['search'] ) ) {
			$args['s'] = sanitize_text_field( $settings['search'] );
		}

		return apply_filters( 'eltiz/loop/query_args', $args, $settings );
	}

	private function sanitize_query_args( array $args ): array {
		$safe = [];

		$safe['post_type']      = sanitize_key( $args['post_type'] ?? 'post' );
		$safe['posts_per_page'] = absint( $args['posts_per_page'] ?? 6 );
		$safe['orderby']        = sanitize_key( $args['orderby'] ?? 'date' );
		$safe['order']          = in_array( $args['order'] ?? 'DESC', ['ASC','DESC'], true ) ? $args['order'] : 'DESC';
		$safe['paged']          = absint( $args['paged'] ?? 1 );

		if ( isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ) {
			$safe['tax_query'] = [];
			foreach ( $args['tax_query'] as $tax ) {
				$safe['tax_query'][] = [
					'taxonomy' => sanitize_key( $tax['taxonomy'] ?? '' ),
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', (array) ( $tax['terms'] ?? [] ) ),
				];
			}
		}

		return $safe;
	}
}
