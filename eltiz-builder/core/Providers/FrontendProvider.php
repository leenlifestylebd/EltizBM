<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;
use EltizBuilder\Frontend\Frontend;

final class FrontendProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'frontend', fn( $c ) => new Frontend() );
	}

	public function boot(): void {
		if ( ! is_admin() ) {
			$this->container->make( 'frontend' )->init();
		}
	}
}
