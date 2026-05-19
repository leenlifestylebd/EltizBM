<?php
declare( strict_types=1 );

namespace EltizBuilder\Core;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * Lightweight dependency-injection / service container.
 */
final class Container {

	private static ?self $instance = null;

	private array $bindings  = [];
	private array $singletons = [];
	private array $instances  = [];

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Bind a factory closure to an alias.
	 */
	public function bind( string $alias, Closure $factory ): void {
		$this->bindings[ $alias ] = $factory;
	}

	/**
	 * Bind a singleton: resolved only once, then cached.
	 */
	public function singleton( string $alias, Closure $factory ): void {
		$this->singletons[ $alias ] = $factory;
	}

	/**
	 * Register an already-constructed instance.
	 */
	public function instance( string $alias, object $object ): void {
		$this->instances[ $alias ] = $object;
	}

	/**
	 * Resolve an alias from the container.
	 *
	 * @throws RuntimeException When alias is not registered.
	 */
	public function make( string $alias ): mixed {
		// Pre-bound instance.
		if ( isset( $this->instances[ $alias ] ) ) {
			return $this->instances[ $alias ];
		}

		// Singleton.
		if ( isset( $this->singletons[ $alias ] ) ) {
			$this->instances[ $alias ] = ( $this->singletons[ $alias ] )( $this );
			unset( $this->singletons[ $alias ] );
			return $this->instances[ $alias ];
		}

		// Factory (new instance each call).
		if ( isset( $this->bindings[ $alias ] ) ) {
			return ( $this->bindings[ $alias ] )( $this );
		}

		// Try to auto-wire by class name.
		if ( class_exists( $alias ) ) {
			return $this->build( $alias );
		}

		throw new RuntimeException( "EltizBuilder Container: unresolvable alias [{$alias}]." );
	}

	private function build( string $class ): object {
		$ref = new \ReflectionClass( $class );

		if ( ! $ref->isInstantiable() ) {
			throw new RuntimeException( "EltizBuilder Container: [{$class}] is not instantiable." );
		}

		$constructor = $ref->getConstructor();

		if ( null === $constructor ) {
			return new $class();
		}

		$dependencies = array_map(
			function ( \ReflectionParameter $param ) use ( $class ) {
				$type = $param->getType();
				if ( $type instanceof \ReflectionNamedType && ! $type->isBuiltin() ) {
					return $this->make( $type->getName() );
				}
				if ( $param->isDefaultValueAvailable() ) {
					return $param->getDefaultValue();
				}
				throw new RuntimeException(
					"EltizBuilder Container: cannot resolve [{$param->getName()}] in [{$class}]."
				);
			},
			$constructor->getParameters()
		);

		return $ref->newInstanceArgs( $dependencies );
	}

	/** Check if an alias is registered. */
	public function has( string $alias ): bool {
		return isset( $this->instances[ $alias ] )
			|| isset( $this->singletons[ $alias ] )
			|| isset( $this->bindings[ $alias ] )
			|| class_exists( $alias );
	}
}
