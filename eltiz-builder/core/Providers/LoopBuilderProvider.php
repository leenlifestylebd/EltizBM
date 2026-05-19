<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class LoopBuilderProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'loop_builder', fn( $c ) => new \EltizBuilder\LoopBuilder\LoopManager() );
	}
	public function boot(): void {
		$this->container->make( 'loop_builder' )->init();
	}
}
