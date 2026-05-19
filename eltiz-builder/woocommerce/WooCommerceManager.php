<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce;

/**
 * WooCommerce Builder integration — shop, product, cart, checkout, account templates.
 */
final class WooCommerceManager {

	public function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'eltiz/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'eltiz/editor/init', [ $this, 'add_editor_data' ] );

		do_action( 'eltiz/woocommerce/init', $this );
	}

	public function register_widgets( \EltizBuilder\Widgets\WidgetManager $manager ): void {
		$widgets = [
			new Widgets\ProductTitleWidget(),
			new Widgets\ProductGalleryWidget(),
			new Widgets\ProductPriceWidget(),
			new Widgets\AddToCartWidget(),
			new Widgets\ProductRatingWidget(),
			new Widgets\ProductMetaWidget(),
			new Widgets\ProductDescriptionWidget(),
			new Widgets\ProductTabsWidget(),
			new Widgets\RelatedProductsWidget(),
			new Widgets\UpsellsWidget(),
			new Widgets\ProductBreadcrumbWidget(),
			new Widgets\CartWidget(),
			new Widgets\CheckoutWidget(),
			new Widgets\MyAccountWidget(),
			new Widgets\ProductsGridWidget(),
		];

		foreach ( $widgets as $widget ) {
			if ( class_exists( get_class( $widget ) ) ) {
				$manager->register( $widget );
			}
		}
	}

	public function add_editor_data(): void {
		// Expose WooCommerce product attribute data to the editor.
	}
}
