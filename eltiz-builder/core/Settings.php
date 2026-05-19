<?php
declare( strict_types=1 );

namespace EltizBuilder\Core;

/**
 * Plugin settings manager with type-safe get/set.
 */
final class Settings {

	private const PREFIX = 'eltiz_';

	private array $cache = [];

	public function get( string $key, mixed $default = null ): mixed {
		if ( ! isset( $this->cache[ $key ] ) ) {
			$this->cache[ $key ] = get_option( self::PREFIX . $key, $default );
		}
		return $this->cache[ $key ];
	}

	public function set( string $key, mixed $value ): bool {
		$saved = update_option( self::PREFIX . $key, $value );
		if ( $saved ) {
			$this->cache[ $key ] = $value;
		}
		return $saved;
	}

	public function delete( string $key ): bool {
		$deleted = delete_option( self::PREFIX . $key );
		unset( $this->cache[ $key ] );
		return $deleted;
	}

	public function get_active_modules(): array {
		return (array) $this->get( 'active_modules', [] );
	}

	public function is_module_active( string $module ): bool {
		$modules = $this->get_active_modules();
		return isset( $modules[ $module ] ) && $modules[ $module ];
	}
}
