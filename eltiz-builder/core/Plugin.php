<?php
declare( strict_types=1 );

namespace EltizBuilder\Core;

/**
 * Main plugin bootstrap — singleton.
 */
final class Plugin {

	private static ?self $instance = null;
	private Container $container;

	/** Registered service providers. */
	private array $providers = [
		\EltizBuilder\Core\Providers\AssetsProvider::class,
		\EltizBuilder\Core\Providers\DatabaseProvider::class,
		\EltizBuilder\Core\Providers\SettingsProvider::class,
		\EltizBuilder\Core\Providers\WidgetProvider::class,
		\EltizBuilder\Core\Providers\ControlsProvider::class,
		\EltizBuilder\Core\Providers\DynamicTagsProvider::class,
		\EltizBuilder\Core\Providers\EditorProvider::class,
		\EltizBuilder\Core\Providers\FrontendProvider::class,
		\EltizBuilder\Core\Providers\AdminProvider::class,
		\EltizBuilder\Core\Providers\RestApiProvider::class,
		\EltizBuilder\Core\Providers\TemplateProvider::class,
		\EltizBuilder\Core\Providers\GlobalStylesProvider::class,
		\EltizBuilder\Core\Providers\ThemeBuilderProvider::class,
		\EltizBuilder\Core\Providers\PopupBuilderProvider::class,
		\EltizBuilder\Core\Providers\FormsProvider::class,
		\EltizBuilder\Core\Providers\WooCommerceProvider::class,
		\EltizBuilder\Core\Providers\MotionEffectsProvider::class,
		\EltizBuilder\Core\Providers\LoopBuilderProvider::class,
		\EltizBuilder\Core\Providers\PerformanceProvider::class,
		\EltizBuilder\Core\Providers\ConditionsProvider::class,
	];

	private function __construct() {
		$this->container = Container::instance();
		$this->boot();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function container(): Container {
		return $this->container;
	}

	public function make( string $alias ): mixed {
		return $this->container->make( $alias );
	}

	private function boot(): void {
		$this->load_textdomain();
		$this->register_providers();
		$this->boot_providers();

		do_action( 'eltiz_builder/init', $this );
	}

	private function load_textdomain(): void {
		load_plugin_textdomain(
			'eltiz-builder',
			false,
			ELTIZ_PATH . 'languages/'
		);
	}

	private function register_providers(): void {
		foreach ( $this->providers as $provider_class ) {
			if ( ! class_exists( $provider_class ) ) {
				continue;
			}
			$provider = new $provider_class( $this->container );
			$provider->register();
		}
	}

	private function boot_providers(): void {
		foreach ( $this->providers as $provider_class ) {
			if ( ! class_exists( $provider_class ) ) {
				continue;
			}
			$alias    = $this->provider_alias( $provider_class );
			if ( $this->container->has( $alias ) ) {
				$provider = $this->container->make( $alias );
				if ( method_exists( $provider, 'boot' ) ) {
					$provider->boot();
				}
			}
		}
	}

	private function provider_alias( string $class ): string {
		return 'provider.' . strtolower( basename( str_replace( '\\', '/', $class ) ) );
	}
}
