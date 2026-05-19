<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class ProductTitleWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-product-title'; }
	public function get_title(): string { return __( 'Product Title', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-title'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section', [ 'label' => __( 'Product Title', 'eltiz-builder' ) ] );
		$this->add_control( 'html_tag', [
			'label'   => __( 'HTML Tag', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'h1'=>'H1','h2'=>'H2','h3'=>'H3','h4'=>'H4','span'=>'Span','div'=>'Div' ],
			'default' => 'h1',
		] );
		$this->end_controls_section();
	}

	protected function render(): void {
		if ( ! function_exists( 'woocommerce_template_single_title' ) ) return;
		$tag = sanitize_key( $this->get_settings( 'html_tag' ) ?? 'h1' );
		echo '<' . esc_html( $tag ) . ' class="eltiz-wc-product-title product_title entry-title">';
		echo wp_kses_post( get_the_title() );
		echo '</' . esc_html( $tag ) . '>';
	}
}
