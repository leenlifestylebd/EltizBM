<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class ProductBreadcrumbWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-breadcrumb'; }
	public function get_title(): string { return __( 'WC Breadcrumbs', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-breadcrumbs'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }
	protected function register_controls(): void {}
	protected function render(): void {
		echo '<div class="eltiz-wc-breadcrumb">';
		woocommerce_breadcrumb();
		echo '</div>';
	}
}
