<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class HeadingWidget extends WidgetBase {

	public function get_name(): string  { return 'heading'; }
	public function get_title(): string { return __( 'Heading', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-t-letter'; }
	public function get_keywords(): array { return [ 'heading', 'title', 'text', 'h1', 'h2', 'h3' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_title', [
			'label' => __( 'Title', 'eltiz-builder' ),
			'tab'   => 'content',
		] );

		$this->add_control( 'title', [
			'label'       => __( 'Title', 'eltiz-builder' ),
			'type'        => 'textarea',
			'default'     => __( 'Add Your Heading Text Here', 'eltiz-builder' ),
			'placeholder' => __( 'Enter your title', 'eltiz-builder' ),
		] );

		$this->add_control( 'link', [
			'label'     => __( 'Link', 'eltiz-builder' ),
			'type'      => 'url',
			'default'   => [ 'url' => '' ],
		] );

		$this->add_control( 'size', [
			'label'   => __( 'HTML Tag', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [
				'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3',
				'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
				'div' => 'div', 'span' => 'span', 'p' => 'p',
			],
			'default' => 'h2',
		] );

		$this->add_control( 'alignment', [
			'label'   => __( 'Alignment', 'eltiz-builder' ),
			'type'    => 'choose',
			'options' => [
				'left'   => [ 'title' => __( 'Left', 'eltiz-builder' ),   'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => __( 'Center', 'eltiz-builder' ), 'icon' => 'eicon-text-align-center' ],
				'right'  => [ 'title' => __( 'Right', 'eltiz-builder' ),  'icon' => 'eicon-text-align-right' ],
			],
			'selectors' => [ '{{WRAPPER}} .eltiz-heading-title' => 'text-align: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_title_style', [
			'label' => __( 'Title', 'eltiz-builder' ),
			'tab'   => 'style',
		] );

		$this->add_control( 'title_color', [
			'label'     => __( 'Text Color', 'eltiz-builder' ),
			'type'      => 'color',
			'selectors' => [ '{{WRAPPER}} .eltiz-heading-title' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( 'typography', [
			'name'     => 'typography',
			'selector' => '{{WRAPPER}} .eltiz-heading-title',
		] );

		$this->add_group_control( 'text_shadow', [
			'name'     => 'text_shadow',
			'selector' => '{{WRAPPER}} .eltiz-heading-title',
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$tag      = in_array( $settings['size'] ?? 'h2', [ 'h1','h2','h3','h4','h5','h6','div','span','p' ], true )
			? $settings['size']
			: 'h2';

		$title = $settings['title'] ?? '';
		$link  = $settings['link'] ?? [];

		if ( ! empty( $link['url'] ) ) {
			$target   = ! empty( $link['is_external'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
			$nofollow = ! empty( $link['nofollow'] ) ? ' rel="nofollow"' : '';
			$title    = '<a href="' . esc_url( $link['url'] ) . '"' . $target . $nofollow . '>' . esc_html( $title ) . '</a>';
		}

		echo '<' . esc_html( $tag ) . ' class="eltiz-heading-title">';
		echo wp_kses_post( $title );
		echo '</' . esc_html( $tag ) . '>';
	}
}
