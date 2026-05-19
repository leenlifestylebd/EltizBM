<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class PricingTableWidget extends WidgetBase {

	public function get_name(): string  { return 'pricing-table'; }
	public function get_title(): string { return __( 'Pricing Table', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-price-table'; }
	public function get_keywords(): array { return [ 'pricing', 'plan', 'table', 'price' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_header', [ 'label' => __( 'Header', 'eltiz-builder' ) ] );

		$this->add_control( 'heading', [ 'label'=>__('Title','eltiz-builder'),'type'=>'text','default'=>__('Basic','eltiz-builder') ] );
		$this->add_control( 'sub_heading', [ 'label'=>__('Description','eltiz-builder'),'type'=>'text','default'=>__('A tagline here.','eltiz-builder') ] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_pricing', [ 'label' => __( 'Pricing', 'eltiz-builder' ) ] );

		$this->add_control( 'currency_symbol', [
			'label'   => __( 'Currency Symbol', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'dollar'=>'$','euro'=>'€','pound'=>'£','yen'=>'¥','custom'=>__('Custom','eltiz-builder') ],
			'default' => 'dollar',
		] );
		$this->add_control( 'price',  [ 'label'=>__('Price','eltiz-builder'),'type'=>'text','default'=>'9.99','dynamic'=>['active'=>true] ] );
		$this->add_control( 'period', [ 'label'=>__('Period','eltiz-builder'),'type'=>'text','default'=>__('Monthly','eltiz-builder') ] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_features', [ 'label' => __( 'Features', 'eltiz-builder' ) ] );

		$this->add_control( 'features_list', [
			'label'  => __( 'Features', 'eltiz-builder' ),
			'type'   => 'repeater',
			'fields' => [
				[ 'name'=>'item_text',      'label'=>__('Text','eltiz-builder'),    'type'=>'text','default'=>__('List Item','eltiz-builder') ],
				[ 'name'=>'item_icon',      'label'=>__('Icon','eltiz-builder'),    'type'=>'icon','default'=>['value'=>'fas fa-check-circle','library'=>'solid'] ],
				[ 'name'=>'item_strikeout', 'label'=>__('Strikeout','eltiz-builder'),'type'=>'switcher','default'=>'' ],
			],
			'default' => [
				[ 'item_text'=>__('First Feature','eltiz-builder') ],
				[ 'item_text'=>__('Second Feature','eltiz-builder') ],
				[ 'item_text'=>__('Third Feature','eltiz-builder') ],
			],
			'title_field' => '{{{ item_text }}}',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_cta', [ 'label' => __( 'Footer', 'eltiz-builder' ) ] );

		$this->add_control( 'button_text', [ 'label'=>__('Button Text','eltiz-builder'),'type'=>'text','default'=>__('Choose Plan','eltiz-builder') ] );
		$this->add_control( 'button_url',  [ 'label'=>__('Button URL','eltiz-builder'), 'type'=>'url','default'=>['url'=>'#'] ] );
		$this->add_control( 'footer_additional_info', [ 'label'=>__('Additional Info','eltiz-builder'),'type'=>'text','default'=>__('No credit card required.','eltiz-builder') ] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_ribbon', [ 'label' => __( 'Ribbon', 'eltiz-builder' ) ] );
		$this->add_control( 'show_ribbon',  [ 'label'=>__('Show Ribbon','eltiz-builder'),'type'=>'switcher','default'=>'' ] );
		$this->add_control( 'ribbon_title', [ 'label'=>__('Title','eltiz-builder'),'type'=>'text','default'=>__('Popular','eltiz-builder'),'condition'=>['show_ribbon'=>'yes'] ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$currency_map = [ 'dollar'=>'$','euro'=>'€','pound'=>'£','yen'=>'¥' ];
		$currency = $currency_map[ $s['currency_symbol'] ?? 'dollar' ] ?? '$';

		echo '<div class="eltiz-pricing-table">';

		if ( 'yes' === ( $s['show_ribbon'] ?? '' ) ) {
			echo '<div class="eltiz-pricing-table-ribbon"><div class="eltiz-pricing-table-ribbon-inner"><span>' . esc_html( $s['ribbon_title'] ?? '' ) . '</span></div></div>';
		}

		// Header.
		echo '<div class="eltiz-pricing-table-header">';
		echo '<div class="eltiz-pricing-table-title">' . esc_html( $s['heading'] ?? '' ) . '</div>';
		echo '<div class="eltiz-pricing-table-subtitle">' . esc_html( $s['sub_heading'] ?? '' ) . '</div>';
		echo '</div>';

		// Price.
		echo '<div class="eltiz-pricing-table-price">';
		echo '<span class="eltiz-price-currency">' . esc_html( $currency ) . '</span>';
		echo '<span class="eltiz-price-value">' . esc_html( $s['price'] ?? '0' ) . '</span>';
		echo '<span class="eltiz-price-period">/' . esc_html( $s['period'] ?? '' ) . '</span>';
		echo '</div>';

		// Features.
		echo '<ul class="eltiz-pricing-table-features">';
		foreach ( ( $s['features_list'] ?? [] ) as $feature ) {
			$strike = 'yes' === ( $feature['item_strikeout'] ?? '' ) ? ' style="text-decoration:line-through;opacity:0.5;"' : '';
			$icon   = $feature['item_icon']['value'] ?? 'fas fa-check-circle';
			echo '<li class="eltiz-pricing-table-feature-item"' . $strike . '>';
			echo '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i> ';
			echo esc_html( $feature['item_text'] ?? '' );
			echo '</li>';
		}
		echo '</ul>';

		// Footer.
		echo '<div class="eltiz-pricing-table-footer">';
		$link    = $s['button_url'] ?? [ 'url' => '#' ];
		$target  = ! empty( $link['is_external'] ) ? ' target="_blank"' : '';
		$nofollow = ! empty( $link['nofollow'] ) ? ' rel="nofollow"' : '';
		echo '<a href="' . esc_url( $link['url'] ?? '#' ) . '"' . $target . $nofollow . ' class="eltiz-pricing-table-button eltiz-button">' . esc_html( $s['button_text'] ?? '' ) . '</a>';
		if ( ! empty( $s['footer_additional_info'] ) ) {
			echo '<p class="eltiz-pricing-table-footer-info">' . esc_html( $s['footer_additional_info'] ) . '</p>';
		}
		echo '</div>';

		echo '</div>';
	}
}
