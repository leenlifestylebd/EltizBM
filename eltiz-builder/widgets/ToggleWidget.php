<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class ToggleWidget extends WidgetBase {
	public function get_name(): string  { return 'toggle'; }
	public function get_title(): string { return __( 'Toggle', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-toggle'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_toggle', [ 'label' => __( 'Toggle', 'eltiz-builder' ) ] );
		$this->add_control( 'tabs', [
			'label'  => __( 'Toggle Items', 'eltiz-builder' ),
			'type'   => 'repeater',
			'fields' => [
				[ 'name'=>'tab_title',   'label'=>__('Title','eltiz-builder'),   'type'=>'text','default'=>__('Toggle Title','eltiz-builder') ],
				[ 'name'=>'tab_content', 'label'=>__('Content','eltiz-builder'), 'type'=>'wysiwyg','default'=>__('Toggle Content Here','eltiz-builder') ],
			],
			'default' => [
				[ 'tab_title'=>__('Toggle #1','eltiz-builder'), 'tab_content'=>__('Toggle content here.','eltiz-builder') ],
			],
			'title_field' => '{{{ tab_title }}}',
		] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		echo '<div class="eltiz-toggle">';
		foreach ( ($s['tabs'] ?? []) as $tab ) {
			echo '<div class="eltiz-toggle-item">';
			echo '<div class="eltiz-toggle-title" role="button" tabindex="0">';
			echo '<span class="eltiz-toggle-icon" aria-hidden="true"><i class="fas fa-plus"></i></span>';
			echo '<span class="eltiz-toggle-title-text">' . esc_html( $tab['tab_title'] ?? '' ) . '</span>';
			echo '</div>';
			echo '<div class="eltiz-toggle-content" style="display:none;">' . wp_kses_post( $tab['tab_content'] ?? '' ) . '</div>';
			echo '</div>';
		}
		echo '</div>';
	}
}
