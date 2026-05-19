<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;
use EltizBuilder\Core\Assets;

final class AssetsProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'assets', fn( $c ) => new Assets() );
	}

	public function boot(): void {
		$this->container->make( 'assets' )->init();
	}
}
