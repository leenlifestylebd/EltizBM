<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class UpsellsWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-upsells'; }
	public function get_title(): string { return __( 'Upsells', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-upsell'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }
	protected function register_controls(): void {}
	protected function render(): void {
		echo '<div class="eltiz-wc-upsells">';
		woocommerce_upsell_display();
		echo '</div>';
	}
}
