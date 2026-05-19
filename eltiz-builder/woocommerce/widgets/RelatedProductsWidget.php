<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class RelatedProductsWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-related-products'; }
	public function get_title(): string { return __( 'Related Products', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-product-related'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }
	protected function register_controls(): void {
		$this->start_controls_section( 'section', [ 'label' => __( 'Related Products', 'eltiz-builder' ) ] );
		$this->add_control( 'posts_per_page', [ 'label'=>__('Products Per Page','eltiz-builder'),'type'=>'number','default'=>4,'min'=>1,'max'=>12 ] );
		$this->add_control( 'columns', [ 'label'=>__('Columns','eltiz-builder'),'type'=>'number','default'=>4,'min'=>1,'max'=>6 ] );
		$this->end_controls_section();
	}
	protected function render(): void {
		$args = [
			'posts_per_page' => (int) ( $this->get_settings('posts_per_page') ?? 4 ),
			'columns'        => (int) ( $this->get_settings('columns') ?? 4 ),
			'orderby'        => 'rand',
		];
		echo '<div class="eltiz-wc-related">';
		woocommerce_related_products( $args );
		echo '</div>';
	}
}
