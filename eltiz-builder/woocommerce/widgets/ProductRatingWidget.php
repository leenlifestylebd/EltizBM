<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class ProductRatingWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-product-rating'; }
	public function get_title(): string { return __( 'Product Rating', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-rating'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }
	protected function register_controls(): void {}
	protected function render(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) $product = wc_get_product( get_the_ID() );
		if ( ! $product ) return;
		echo '<div class="eltiz-wc-rating">';
		woocommerce_template_single_rating();
		echo '</div>';
	}
}
