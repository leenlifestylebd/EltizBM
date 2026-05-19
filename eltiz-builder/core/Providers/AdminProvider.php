<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;
use EltizBuilder\Admin\Admin;

final class AdminProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'admin', fn( $c ) => new Admin() );
	}

	public function boot(): void {
		if ( is_admin() ) {
			$this->container->make( 'admin' )->init();
		}
	}
}
