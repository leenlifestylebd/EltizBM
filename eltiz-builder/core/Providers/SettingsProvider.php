<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class SettingsProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'settings', fn( $c ) => new \EltizBuilder\Core\Settings() );
	}
}
