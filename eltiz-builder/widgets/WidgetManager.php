<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

/**
 * Central registry for all widgets.
 */
final class WidgetManager {

	private array $widgets     = [];
	private array $categories  = [];

	public function __construct() {
		$this->register_default_categories();
	}

	private function register_default_categories(): void {
		$this->add_category( 'general', [
			'title' => __( 'General', 'eltiz-builder' ),
			'icon'  => 'eicon-font',
		] );
		$this->add_category( 'layout', [
			'title' => __( 'Layout', 'eltiz-builder' ),
			'icon'  => 'eicon-columns',
		] );
		$this->add_category( 'media', [
			'title' => __( 'Media', 'eltiz-builder' ),
			'icon'  => 'eicon-image',
		] );
		$this->add_category( 'interactive', [
			'title' => __( 'Interactive', 'eltiz-builder' ),
			'icon'  => 'eicon-cursor',
		] );
		$this->add_category( 'forms', [
			'title' => __( 'Forms', 'eltiz-builder' ),
			'icon'  => 'eicon-form-horizontal',
		] );
		$this->add_category( 'woocommerce', [
			'title' => __( 'WooCommerce', 'eltiz-builder' ),
			'icon'  => 'eicon-cart',
		] );
		$this->add_category( 'theme-elements', [
			'title' => __( 'Theme Elements', 'eltiz-builder' ),
			'icon'  => 'eicon-theme-builder',
		] );
	}

	public function register( WidgetBase $widget ): void {
		$this->widgets[ $widget->get_name() ] = $widget;
	}

	public function unregister( string $name ): void {
		unset( $this->widgets[ $name ] );
	}

	public function get( string $name ): ?WidgetBase {
		return $this->widgets[ $name ] ?? null;
	}

	public function get_all(): array {
		return $this->widgets;
	}

	public function add_category( string $slug, array $args ): void {
		$this->categories[ $slug ] = $args;
	}

	public function get_categories(): array {
		return $this->categories;
	}

	/**
	 * Returns the full widget registry schema for the editor.
	 */
	public function get_widgets_config(): array {
		$config = [];
		foreach ( $this->widgets as $name => $widget ) {
			$config[ $name ] = $widget->get_schema();
		}
		return apply_filters( 'eltiz/widgets/config', $config );
	}
}
