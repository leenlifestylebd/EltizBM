<?php
declare( strict_types=1 );

namespace EltizBuilder\ThemeBuilder;

/**
 * Theme Builder — manages header, footer, single, archive, and other theme templates.
 */
final class ThemeBuilder {

	public const POST_TYPE = 'eltiz_theme_tpl';

	public function init(): void {
		add_action( 'init',              [ $this, 'register_post_type' ] );
		add_action( 'template_include',  [ $this, 'maybe_load_template' ], 11 );
		add_action( 'wp_head',           [ $this, 'render_header_template' ] );
		add_action( 'wp_footer',         [ $this, 'render_footer_template' ] );

		do_action( 'eltiz/theme_builder/init', $this );
	}

	public function register_post_type(): void {
		register_post_type( self::POST_TYPE, [
			'label'               => __( 'Theme Templates', 'eltiz-builder' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'eltiz-dashboard',
			'show_in_rest'        => true,
			'supports'            => [ 'title', 'custom-fields' ],
			'capability_type'     => 'page',
			'rewrite'             => false,
		] );
	}

	/**
	 * Intercept template loading if a matching Eltiz template is found.
	 */
	public function maybe_load_template( string $template ): string {
		$location = $this->get_current_location();
		$tpl_id   = $this->get_matching_template( $location );

		if ( ! $tpl_id ) {
			return $template;
		}

		// Store matched template ID for rendering.
		set_query_var( 'eltiz_theme_tpl_id', $tpl_id );

		$canvas = ELTIZ_PATH . 'templates/theme-builder-canvas.php';
		return file_exists( $canvas ) ? $canvas : $template;
	}

	public function render_header_template(): void {
		$tpl_id = $this->get_matching_template( 'header' );
		if ( $tpl_id ) {
			$this->render_template( $tpl_id );
		}
	}

	public function render_footer_template(): void {
		$tpl_id = $this->get_matching_template( 'footer' );
		if ( $tpl_id ) {
			$this->render_template( $tpl_id );
		}
	}

	private function render_template( int $tpl_id ): void {
		$data = get_post_meta( $tpl_id, '_eltiz_data', true );
		if ( ! $data ) return;

		$elements = json_decode( $data, true );
		if ( ! is_array( $elements ) ) return;

		$frontend = \EltizBuilder\Core\Plugin::instance()->make( 'frontend' );
		$frontend->render_elements( $elements );
	}

	private function get_current_location(): string {
		if ( is_home() || is_front_page() ) return 'home';
		if ( is_singular() )                return 'single';
		if ( is_archive() )                 return 'archive';
		if ( is_search() )                  return 'search';
		if ( is_404() )                     return '404';
		return 'default';
	}

	private function get_matching_template( string $location ): int {
		$templates = get_posts( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'meta_key'       => '_eltiz_tpl_location',
			'meta_value'     => $location,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		return $templates[0] ?? 0;
	}

	public function get_template_locations(): array {
		return apply_filters( 'eltiz/theme_builder/locations', [
			'header'  => __( 'Header', 'eltiz-builder' ),
			'footer'  => __( 'Footer', 'eltiz-builder' ),
			'single'  => __( 'Single Post', 'eltiz-builder' ),
			'archive' => __( 'Archive', 'eltiz-builder' ),
			'search'  => __( 'Search Results', 'eltiz-builder' ),
			'404'     => __( '404 Page', 'eltiz-builder' ),
			'home'    => __( 'Homepage', 'eltiz-builder' ),
		] );
	}
}
