<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class CheckoutWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-checkout'; }
	public function get_title(): string { return __( 'Checkout', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-checkout'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }
	protected function register_controls(): void {}
	protected function render(): void {
		echo do_shortcode( '[woocommerce_checkout]' );
	}
}
