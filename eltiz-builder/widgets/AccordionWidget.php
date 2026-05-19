<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class AccordionWidget extends WidgetBase {

	public function get_name(): string  { return 'accordion'; }
	public function get_title(): string { return __( 'Accordion', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-accordion'; }
	public function get_keywords(): array { return [ 'accordion', 'faq', 'tabs', 'toggle' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_title', [ 'label' => __( 'Accordion', 'eltiz-builder' ) ] );

		$this->add_control( 'tabs', [
			'label'     => __( 'Items', 'eltiz-builder' ),
			'type'      => 'repeater',
			'fields'    => [
				[
					'name'    => 'tab_title',
					'label'   => __( 'Title', 'eltiz-builder' ),
					'type'    => 'text',
					'default' => __( 'Accordion Title', 'eltiz-builder' ),
				],
				[
					'name'    => 'tab_content',
					'label'   => __( 'Content', 'eltiz-builder' ),
					'type'    => 'wysiwyg',
					'default' => __( 'Accordion Content', 'eltiz-builder' ),
				],
			],
			'default'   => [
				[ 'tab_title' => __( 'Accordion #1', 'eltiz-builder' ), 'tab_content' => __( 'Lorem ipsum dolor sit amet.', 'eltiz-builder' ) ],
				[ 'tab_title' => __( 'Accordion #2', 'eltiz-builder' ), 'tab_content' => __( 'Lorem ipsum dolor sit amet.', 'eltiz-builder' ) ],
			],
			'title_field' => '{{{ tab_title }}}',
		] );

		$this->add_control( 'selected_icon', [
			'label'   => __( 'Icon', 'eltiz-builder' ),
			'type'    => 'icon',
			'default' => [ 'value' => 'fas fa-plus', 'library' => 'solid' ],
		] );

		$this->add_control( 'selected_active_icon', [
			'label'   => __( 'Active Icon', 'eltiz-builder' ),
			'type'    => 'icon',
			'default' => [ 'value' => 'fas fa-minus', 'library' => 'solid' ],
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$tabs     = $settings['tabs'] ?? [];

		echo '<div class="eltiz-accordion" role="list">';

		foreach ( $tabs as $i => $tab ) {
			$is_active  = 0 === $i ? ' eltiz-active' : '';
			$expanded   = 0 === $i ? 'true' : 'false';
			$tab_id     = 'eltiz-accordion-' . $i . '-' . uniqid();
			$panel_id   = 'eltiz-accordion-panel-' . $i . '-' . uniqid();

			echo '<div class="eltiz-accordion-item' . esc_attr( $is_active ) . '">';
			echo '<div class="eltiz-accordion-title" role="button" tabindex="0" aria-expanded="' . esc_attr( $expanded ) . '" aria-controls="' . esc_attr( $panel_id ) . '" id="' . esc_attr( $tab_id ) . '">';
			echo '<i class="eltiz-accordion-icon eltiz-accordion-icon-closed" aria-hidden="true"></i>';
			echo '<i class="eltiz-accordion-icon eltiz-accordion-icon-opened" aria-hidden="true"></i>';
			echo '<span>' . esc_html( $tab['tab_title'] ?? '' ) . '</span>';
			echo '</div>';
			echo '<div id="' . esc_attr( $panel_id ) . '" class="eltiz-accordion-content" role="region" aria-labelledby="' . esc_attr( $tab_id ) . '">';
			echo wp_kses_post( $tab['tab_content'] ?? '' );
			echo '</div>';
			echo '</div>';
		}

		echo '</div>';
	}
}
