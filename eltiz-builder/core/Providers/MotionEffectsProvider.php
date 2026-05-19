<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class MotionEffectsProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'motion_effects', fn( $c ) => new \EltizBuilder\MotionEffects\MotionEffectsManager() );
	}
	public function boot(): void {
		$this->container->make( 'motion_effects' )->init();
	}
}
