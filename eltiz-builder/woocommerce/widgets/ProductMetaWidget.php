<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class ProductMetaWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-product-meta'; }
	public function get_title(): string { return __( 'Product Meta', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-meta'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }
	protected function register_controls(): void {}
	protected function render(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) $product = wc_get_product( get_the_ID() );
		if ( ! $product ) return;
		echo '<div class="eltiz-wc-meta">';
		woocommerce_template_single_meta();
		echo '</div>';
	}
}
