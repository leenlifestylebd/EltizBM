<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class WooCommerceProvider extends ServiceProvider {
	public function register(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$this->container->singleton( 'woocommerce', fn( $c ) => new \EltizBuilder\WooCommerce\WooCommerceManager() );
	}
	public function boot(): void {
		if ( ! class_exists( 'WooCommerce' ) || ! $this->container->has( 'woocommerce' ) ) {
			return;
		}
		$this->container->make( 'woocommerce' )->init();
	}
}
