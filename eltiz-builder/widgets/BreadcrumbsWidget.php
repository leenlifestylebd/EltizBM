<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class BreadcrumbsWidget extends WidgetBase {
	public function get_name(): string  { return 'breadcrumbs'; }
	public function get_title(): string { return __( 'Breadcrumbs', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-breadcrumbs'; }
	public function get_categories(): array { return [ 'theme-elements' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_breadcrumbs', [ 'label' => __( 'Breadcrumbs', 'eltiz-builder' ) ] );
		$this->add_control( 'separator', [ 'label'=>__('Separator','eltiz-builder'),'type'=>'text','default'=>'/' ] );
		$this->add_control( 'show_home', [ 'label'=>__('Show Home','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'home_text', [ 'label'=>__('Home Text','eltiz-builder'),'type'=>'text','default'=>__('Home','eltiz-builder') ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s         = $this->get_settings_for_display();
		$separator = '<span class="eltiz-breadcrumb-separator" aria-hidden="true">' . esc_html( $s['separator'] ?? '/' ) . '</span>';

		$breadcrumbs = [];

		if ( 'yes' === ($s['show_home'] ?? 'yes') ) {
			$breadcrumbs[] = '<a href="' . esc_url( home_url('/') ) . '">' . esc_html( $s['home_text'] ?? __('Home','eltiz-builder') ) . '</a>';
		}

		if ( is_singular() ) {
			$post_type = get_post_type_object( get_post_type() );
			if ( $post_type && ! in_array( get_post_type(), ['post','page'], true ) ) {
				$breadcrumbs[] = esc_html( $post_type->labels->singular_name );
			}
			$breadcrumbs[] = '<span>' . esc_html( get_the_title() ) . '</span>';
		} elseif ( is_archive() ) {
			$breadcrumbs[] = '<span>' . esc_html( get_the_archive_title() ) . '</span>';
		} elseif ( is_search() ) {
			$breadcrumbs[] = '<span>' . sprintf( esc_html__( 'Search results for: %s', 'eltiz-builder' ), esc_html( get_search_query() ) ) . '</span>';
		} elseif ( is_404() ) {
			$breadcrumbs[] = '<span>' . esc_html__( '404 Not Found', 'eltiz-builder' ) . '</span>';
		}

		echo '<nav class="eltiz-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'eltiz-builder' ) . '">';
		echo '<ol class="eltiz-breadcrumbs-list">';
		foreach ( $breadcrumbs as $i => $crumb ) {
			$is_last = $i === count( $breadcrumbs ) - 1;
			echo '<li class="eltiz-breadcrumb-item' . ( $is_last ? ' eltiz-breadcrumb-item--active' : '' ) . '"' . ( $is_last ? ' aria-current="page"' : '' ) . '>';
			echo $crumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — Already escaped above.
			if ( ! $is_last ) echo $separator;
			echo '</li>';
		}
		echo '</ol>';
		echo '</nav>';
	}
}
