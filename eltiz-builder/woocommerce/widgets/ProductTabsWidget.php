<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class ProductTabsWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-product-tabs'; }
	public function get_title(): string { return __( 'Product Tabs', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-tabs'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }
	protected function register_controls(): void {}
	protected function render(): void {
		echo '<div class="eltiz-wc-tabs">';
		woocommerce_output_product_data_tabs();
		echo '</div>';
	}
}
