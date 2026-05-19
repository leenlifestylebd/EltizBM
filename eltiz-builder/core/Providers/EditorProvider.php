<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;
use EltizBuilder\Editor\Editor;

final class EditorProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'editor', fn( $c ) => new Editor() );
	}

	public function boot(): void {
		if ( is_admin() ) {
			$this->container->make( 'editor' )->init();
		}
	}
}
