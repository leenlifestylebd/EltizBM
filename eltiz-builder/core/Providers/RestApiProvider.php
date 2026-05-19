<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;
use EltizBuilder\API\RestAPI;

final class RestApiProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'rest_api', fn( $c ) => new RestAPI() );
	}

	public function boot(): void {
		$this->container->make( 'rest_api' )->init();
	}
}
