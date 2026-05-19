<?php
declare( strict_types=1 );

namespace EltizBuilder\Core;

/**
 * Base service provider — all providers extend this.
 */
abstract class ServiceProvider {

	public function __construct( protected Container $container ) {}

	/** Register bindings into the container. */
	abstract public function register(): void;

	/** Boot the provider after all providers are registered. */
	public function boot(): void {}
}
