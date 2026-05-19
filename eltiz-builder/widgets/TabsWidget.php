<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class TabsWidget extends WidgetBase {

	public function get_name(): string  { return 'tabs'; }
	public function get_title(): string { return __( 'Tabs', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-tabs'; }
	public function get_keywords(): array { return [ 'tabs', 'accordion', 'toggle' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_tabs', [ 'label' => __( 'Tabs', 'eltiz-builder' ) ] );

		$this->add_control( 'tabs', [
			'label'  => __( 'Tab Items', 'eltiz-builder' ),
			'type'   => 'repeater',
			'fields' => [
				[ 'name' => 'tab_title',   'label' => __( 'Title', 'eltiz-builder' ),   'type' => 'text',    'default' => __( 'Tab Title', 'eltiz-builder' ) ],
				[ 'name' => 'tab_content', 'label' => __( 'Content', 'eltiz-builder' ), 'type' => 'wysiwyg', 'default' => __( 'Tab Content', 'eltiz-builder' ) ],
				[ 'name' => 'tab_icon',    'label' => __( 'Icon', 'eltiz-builder' ),    'type' => 'icon' ],
			],
			'default' => [
				[ 'tab_title' => __( 'Tab #1', 'eltiz-builder' ), 'tab_content' => '' ],
				[ 'tab_title' => __( 'Tab #2', 'eltiz-builder' ), 'tab_content' => '' ],
			],
			'title_field' => '{{{ tab_title }}}',
		] );

		$this->add_control( 'type', [
			'label'   => __( 'Position', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'horizontal' => __( 'Horizontal', 'eltiz-builder' ), 'vertical' => __( 'Vertical', 'eltiz-builder' ) ],
			'default' => 'horizontal',
			'prefix_class' => 'eltiz-tabs-',
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$tabs     = $settings['tabs'] ?? [];
		$id_int   = uniqid();

		echo '<div class="eltiz-tabs">';
		echo '<div class="eltiz-tabs-wrapper" role="tablist">';

		foreach ( $tabs as $i => $tab ) {
			$tab_id    = "eltiz-tab-title-{$id_int}{$i}";
			$panel_id  = "eltiz-tab-content-{$id_int}{$i}";
			$active    = 0 === $i ? ' eltiz-active' : '';
			$selected  = 0 === $i ? 'true' : 'false';

			echo '<div id="' . esc_attr( $tab_id ) . '" class="eltiz-tab-title' . esc_attr( $active ) . '" role="tab" tabindex="0" aria-controls="' . esc_attr( $panel_id ) . '" aria-selected="' . esc_attr( $selected ) . '">';
			echo '<span class="eltiz-tab-title-text">' . esc_html( $tab['tab_title'] ?? '' ) . '</span>';
			echo '</div>';
		}

		echo '</div>';
		echo '<div class="eltiz-tabs-content-wrapper">';

		foreach ( $tabs as $i => $tab ) {
			$panel_id = "eltiz-tab-content-{$id_int}{$i}";
			$active   = 0 === $i ? ' eltiz-active' : '';
			$tab_id   = "eltiz-tab-title-{$id_int}{$i}";

			echo '<div id="' . esc_attr( $panel_id ) . '" class="eltiz-tab-content' . esc_attr( $active ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $tab_id ) . '">';
			echo wp_kses_post( $tab['tab_content'] ?? '' );
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}
}
