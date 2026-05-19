<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;
use EltizBuilder\Widgets\WidgetManager;

final class WidgetProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( 'widgets', fn( $c ) => new WidgetManager() );
	}

	public function boot(): void {
		$manager = $this->container->make( 'widgets' );
		$this->register_core_widgets( $manager );
		do_action( 'eltiz/widgets/register', $manager );
	}

	private function register_core_widgets( WidgetManager $manager ): void {
		$widgets = [
			\EltizBuilder\Widgets\HeadingWidget::class,
			\EltizBuilder\Widgets\TextEditorWidget::class,
			\EltizBuilder\Widgets\ImageWidget::class,
			\EltizBuilder\Widgets\VideoWidget::class,
			\EltizBuilder\Widgets\ButtonWidget::class,
			\EltizBuilder\Widgets\AccordionWidget::class,
			\EltizBuilder\Widgets\TabsWidget::class,
			\EltizBuilder\Widgets\CounterWidget::class,
			\EltizBuilder\Widgets\CountdownWidget::class,
			\EltizBuilder\Widgets\TestimonialsWidget::class,
			\EltizBuilder\Widgets\GalleryWidget::class,
			\EltizBuilder\Widgets\PricingTableWidget::class,
			\EltizBuilder\Widgets\DividerWidget::class,
			\EltizBuilder\Widgets\SpacerWidget::class,
			\EltizBuilder\Widgets\IconWidget::class,
			\EltizBuilder\Widgets\IconBoxWidget::class,
			\EltizBuilder\Widgets\SocialIconsWidget::class,
			\EltizBuilder\Widgets\ProgressBarWidget::class,
			\EltizBuilder\Widgets\ToggleWidget::class,
			\EltizBuilder\Widgets\GoogleMapsWidget::class,
			\EltizBuilder\Widgets\HTMLWidget::class,
			\EltizBuilder\Widgets\ShortcodeWidget::class,
			\EltizBuilder\Widgets\PostsWidget::class,
			\EltizBuilder\Widgets\CallToActionWidget::class,
			\EltizBuilder\Widgets\SearchFormWidget::class,
			\EltizBuilder\Widgets\LoginFormWidget::class,
			\EltizBuilder\Widgets\BreadcrumbsWidget::class,
		];

		foreach ( $widgets as $class ) {
			if ( class_exists( $class ) ) {
				$manager->register( new $class() );
			}
		}
	}
}
