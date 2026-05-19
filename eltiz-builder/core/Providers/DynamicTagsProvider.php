<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class DynamicTagsProvider extends ServiceProvider {
	public function register(): void {
		$this->container->singleton( 'dynamic_tags', fn( $c ) => new \EltizBuilder\DynamicTags\DynamicTagsManager() );
	}
	public function boot(): void {
		$this->container->make( 'dynamic_tags' )->init();
	}
}
