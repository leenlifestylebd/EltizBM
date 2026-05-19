<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;
use EltizBuilder\Includes\Controls\ControlsManager;

final class ControlsProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'controls', fn( $c ) => new ControlsManager() );
	}
}
