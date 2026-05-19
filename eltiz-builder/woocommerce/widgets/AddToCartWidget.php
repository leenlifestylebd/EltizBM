<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class AddToCartWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-add-to-cart'; }
	public function get_title(): string { return __( 'Add To Cart', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-cart-medium'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section', [ 'label' => __( 'Add To Cart', 'eltiz-builder' ) ] );
		$this->add_control( 'show_quantity', [ 'label'=>__('Show Quantity','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product ) return;

		echo '<div class="eltiz-wc-add-to-cart">';
		woocommerce_template_single_add_to_cart();
		echo '</div>';
	}
}
