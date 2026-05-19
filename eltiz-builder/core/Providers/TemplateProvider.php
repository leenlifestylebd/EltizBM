<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class TemplateProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'templates', fn( $c ) => new \EltizBuilder\Modules\Templates\TemplateManager() );
	}
	public function boot(): void {
		$this->container->make( 'templates' )->init();
	}
}
