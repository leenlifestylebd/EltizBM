<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class ProductPriceWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-product-price'; }
	public function get_title(): string { return __( 'Product Price', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-price'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section', [ 'label' => __( 'Product Price', 'eltiz-builder' ) ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product ) return;

		echo '<div class="eltiz-wc-price price">';
		echo wp_kses_post( $product->get_price_html() );
		echo '</div>';
	}
}
