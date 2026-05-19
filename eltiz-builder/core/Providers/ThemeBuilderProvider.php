<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class ThemeBuilderProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'theme_builder', fn( $c ) => new \EltizBuilder\ThemeBuilder\ThemeBuilder() );
	}
	public function boot(): void {
		$this->container->make( 'theme_builder' )->init();
	}
}
