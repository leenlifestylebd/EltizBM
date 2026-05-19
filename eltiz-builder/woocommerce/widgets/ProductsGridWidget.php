<?php
declare( strict_types=1 );

namespace EltizBuilder\WooCommerce\Widgets;

use EltizBuilder\Widgets\WidgetBase;

final class ProductsGridWidget extends WidgetBase {
	public function get_name(): string  { return 'wc-products'; }
	public function get_title(): string { return __( 'Products Grid', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-products'; }
	public function get_categories(): array { return [ 'woocommerce' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section', [ 'label' => __( 'Products', 'eltiz-builder' ) ] );
		$this->add_control( 'posts_per_page', [ 'label'=>__('Products Count','eltiz-builder'),'type'=>'number','default'=>8,'min'=>1,'max'=>100 ] );
		$this->add_control( 'columns', [ 'label'=>__('Columns','eltiz-builder'),'type'=>'number','default'=>4,'min'=>1,'max'=>6 ] );
		$this->add_control( 'orderby', [ 'label'=>__('Order By','eltiz-builder'),'type'=>'select','options'=>['date'=>'Date','price'=>'Price','title'=>'Title','popularity'=>'Popularity','rating'=>'Rating','rand'=>'Random'],'default'=>'date' ] );
		$this->add_control( 'order',   [ 'label'=>__('Order','eltiz-builder'),'type'=>'select','options'=>['ASC'=>'ASC','DESC'=>'DESC'],'default'=>'DESC' ] );
		$this->add_control( 'category','label' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$shortcode = sprintf(
			'[products limit="%d" columns="%d" orderby="%s" order="%s"]',
			absint( $s['posts_per_page'] ?? 8 ),
			absint( $s['columns'] ?? 4 ),
			sanitize_key( $s['orderby'] ?? 'date' ),
			in_array( $s['order'] ?? 'DESC', ['ASC','DESC'], true ) ? $s['order'] : 'DESC'
		);
		echo '<div class="eltiz-wc-products">' . do_shortcode( $shortcode ) . '</div>';
	}
}
