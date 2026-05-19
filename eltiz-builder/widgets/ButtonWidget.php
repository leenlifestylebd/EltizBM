<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class ButtonWidget extends WidgetBase {

	public function get_name(): string  { return 'button'; }
	public function get_title(): string { return __( 'Button', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-button'; }
	public function get_keywords(): array { return [ 'button', 'link', 'cta' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_button', [ 'label' => __( 'Button', 'eltiz-builder' ) ] );

		$this->add_control( 'button_type', [
			'label'   => __( 'Type', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'info'=>'Default','success'=>'Success','warning'=>'Warning','danger'=>'Danger' ],
			'default' => 'info',
			'prefix_class' => 'eltiz-button-',
		] );

		$this->add_control( 'text', [
			'label'       => __( 'Text', 'eltiz-builder' ),
			'type'        => 'text',
			'default'     => __( 'Click Here', 'eltiz-builder' ),
			'placeholder' => __( 'Click Here', 'eltiz-builder' ),
			'dynamic'     => [ 'active' => true ],
		] );

		$this->add_control( 'link', [
			'label'   => __( 'Link', 'eltiz-builder' ),
			'type'    => 'url',
			'default' => [ 'url' => '#' ],
			'dynamic' => [ 'active' => true ],
		] );

		$this->add_responsive_control( 'align', [
			'label'   => __( 'Alignment', 'eltiz-builder' ),
			'type'    => 'choose',
			'options' => [
				'left'    => [ 'title'=>__('Left','eltiz-builder'),    'icon'=>'eicon-text-align-left' ],
				'center'  => [ 'title'=>__('Center','eltiz-builder'),  'icon'=>'eicon-text-align-center' ],
				'right'   => [ 'title'=>__('Right','eltiz-builder'),   'icon'=>'eicon-text-align-right' ],
				'justify' => [ 'title'=>__('Justified','eltiz-builder'),'icon'=>'eicon-text-align-justify' ],
			],
			'selectors' => [ '{{WRAPPER}}' => 'text-align: {{VALUE}};' ],
		] );

		$this->add_control( 'size', [
			'label'   => __( 'Size', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'xs'=>__('Extra Small','eltiz-builder'),'sm'=>__('Small','eltiz-builder'),'md'=>__('Medium','eltiz-builder'),'lg'=>__('Large','eltiz-builder'),'xl'=>__('Extra Large','eltiz-builder') ],
			'default' => 'sm',
			'prefix_class' => 'eltiz-size-',
		] );

		$this->add_control( 'selected_icon', [
			'label'            => __( 'Icon', 'eltiz-builder' ),
			'type'             => 'icon',
			'label_block'      => true,
			'fa4compatibility' => 'icon',
		] );

		$this->add_control( 'icon_align', [
			'label'     => __( 'Icon Position', 'eltiz-builder' ),
			'type'      => 'select',
			'options'   => [ 'left'=>__('Before','eltiz-builder'),'right'=>__('After','eltiz-builder') ],
			'default'   => 'left',
			'condition' => [ 'selected_icon[value]!' => '' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [ 'label'=>__('Button','eltiz-builder'),'tab'=>'style' ] );

		$this->add_group_control( 'typography', [ 'name'=>'typography','selector'=>'{{WRAPPER}} .eltiz-button' ] );

		$this->add_control( 'button_text_color', [
			'label'     => __( 'Text Color', 'eltiz-builder' ),
			'type'      => 'color',
			'selectors' => [ '{{WRAPPER}} .eltiz-button' => 'fill: {{VALUE}}; color: {{VALUE}};' ],
		] );

		$this->add_control( 'background_color', [
			'label'     => __( 'Background Color', 'eltiz-builder' ),
			'type'      => 'color',
			'selectors' => [ '{{WRAPPER}} .eltiz-button' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'border_radius', [
			'label'     => __( 'Border Radius', 'eltiz-builder' ),
			'type'      => 'dimensions',
			'selectors' => [ '{{WRAPPER}} .eltiz-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_group_control( 'box_shadow', [ 'name'=>'button_box_shadow','selector'=>'{{WRAPPER}} .eltiz-button' ] );

		$this->add_responsive_control( 'text_padding', [
			'label'     => __( 'Padding', 'eltiz-builder' ),
			'type'      => 'dimensions',
			'selectors' => [ '{{WRAPPER}} .eltiz-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$link     = $settings['link'] ?? [ 'url' => '#' ];
		$text     = $settings['text'] ?? __( 'Click Here', 'eltiz-builder' );
		$target   = ! empty( $link['is_external'] ) ? ' target="_blank"' : '';
		$nofollow = ! empty( $link['nofollow'] ) ? ' rel="nofollow"' : '';

		$icon = $settings['selected_icon']['value'] ?? '';
		$icon_html = '';
		if ( $icon ) {
			$icon_html = '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i> ';
		}

		echo '<div class="eltiz-button-wrapper">';
		echo '<a href="' . esc_url( $link['url'] ?? '#' ) . '"' . $target . $nofollow . ' class="eltiz-button eltiz-button-link">';
		echo '<span class="eltiz-button-content-wrapper">';
		if ( 'left' === ( $settings['icon_align'] ?? 'left' ) ) echo $icon_html;
		echo '<span class="eltiz-button-text">' . esc_html( $text ) . '</span>';
		if ( 'right' === ( $settings['icon_align'] ?? 'left' ) ) echo $icon_html;
		echo '</span>';
		echo '</a>';
		echo '</div>';
	}
}
