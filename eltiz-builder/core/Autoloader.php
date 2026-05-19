<?php
declare( strict_types=1 );

namespace EltizBuilder\Core;

/**
 * PSR-4 autoloader for the EltizBuilder namespace.
 */
final class Autoloader {

	private static array $class_map = [];

	public static function register(): void {
		spl_autoload_register( [ self::class, 'load' ] );
	}

	public static function load( string $class ): void {
		if ( ! str_starts_with( $class, 'EltizBuilder\\' ) ) {
			return;
		}

		// Check class map first.
		if ( isset( self::$class_map[ $class ] ) ) {
			require_once self::$class_map[ $class ];
			return;
		}

		$relative = substr( $class, strlen( 'EltizBuilder\\' ) );
		$parts    = explode( '\\', $relative );

		// Map top-level namespace segment to directory.
		$dir_map = [
			'Core'         => ELTIZ_PATH . 'core/',
			'Modules'      => ELTIZ_PATH . 'modules/',
			'Widgets'      => ELTIZ_PATH . 'widgets/',
			'Editor'       => ELTIZ_PATH . 'editor/',
			'Frontend'     => ELTIZ_PATH . 'frontend/',
			'Admin'        => ELTIZ_PATH . 'admin/',
			'Includes'     => ELTIZ_PATH . 'includes/',
			'API'          => ELTIZ_PATH . 'api/',
			'DynamicTags'  => ELTIZ_PATH . 'dynamic-tags/',
			'WooCommerce'  => ELTIZ_PATH . 'woocommerce/',
			'ThemeBuilder' => ELTIZ_PATH . 'theme-builder/',
			'PopupBuilder' => ELTIZ_PATH . 'popup-builder/',
			'Forms'        => ELTIZ_PATH . 'forms/',
			'Conditions'   => ELTIZ_PATH . 'conditions/',
			'MotionEffects'=> ELTIZ_PATH . 'motion-effects/',
			'GlobalStyles' => ELTIZ_PATH . 'global-styles/',
			'LoopBuilder'  => ELTIZ_PATH . 'loop-builder/',
		];

		$segment = array_shift( $parts );
		$base     = $dir_map[ $segment ] ?? null;

		if ( ! $base ) {
			return;
		}

		$file = $base . implode( '/', $parts ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
