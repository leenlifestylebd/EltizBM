<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class FormsProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'forms', fn( $c ) => new \EltizBuilder\Forms\FormManager() );
	}
	public function boot(): void {
		$this->container->make( 'forms' )->init();
	}
}
