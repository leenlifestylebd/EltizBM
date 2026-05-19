<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class PopupBuilderProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'popup_builder', fn( $c ) => new \EltizBuilder\PopupBuilder\PopupManager() );
	}
	public function boot(): void {
		$this->container->make( 'popup_builder' )->init();
	}
}
