<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class PerformanceProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'performance', fn( $c ) => new \EltizBuilder\Modules\Performance\PerformanceManager() );
	}
	public function boot(): void {
		$this->container->make( 'performance' )->init();
	}
}
