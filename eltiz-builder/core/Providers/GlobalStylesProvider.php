<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class GlobalStylesProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'global_styles', fn( $c ) => new \EltizBuilder\GlobalStyles\GlobalStylesManager() );
	}
	public function boot(): void {
		$this->container->make( 'global_styles' )->init();
	}
}
