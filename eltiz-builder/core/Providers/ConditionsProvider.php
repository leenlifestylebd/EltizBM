<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class ConditionsProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'conditions', fn( $c ) => new \EltizBuilder\Conditions\ConditionsManager() );
	}
	public function boot(): void {
		$this->container->make( 'conditions' )->init();
	}
}
