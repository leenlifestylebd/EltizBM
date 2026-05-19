<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class TextEditorWidget extends WidgetBase {

	public function get_name(): string  { return 'text-editor'; }
	public function get_title(): string { return __( 'Text Editor', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-document-file'; }
	public function get_keywords(): array { return [ 'text', 'editor', 'paragraph', 'wysiwyg' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_editor', [
			'label' => __( 'Text Editor', 'eltiz-builder' ),
		] );

		$this->add_control( 'editor', [
			'label'       => '',
			'type'        => 'wysiwyg',
			'default'     => '<p>' . __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'eltiz-builder' ) . '</p>',
			'dynamic'     => [ 'active' => true ],
		] );

		$this->add_control( 'drop_cap', [
			'label'   => __( 'Drop Cap', 'eltiz-builder' ),
			'type'    => 'switcher',
			'default' => '',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', [
			'label' => __( 'Text Editor', 'eltiz-builder' ),
			'tab'   => 'style',
		] );

		$this->add_control( 'text_color', [
			'label'     => __( 'Text Color', 'eltiz-builder' ),
			'type'      => 'color',
			'selectors' => [ '{{WRAPPER}}' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( 'typography', [
			'name'     => 'typography',
			'selector' => '{{WRAPPER}}',
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$editor   = $this->get_settings_for_display( 'editor' );
		$drop_cap = 'yes' === $this->get_settings( 'drop_cap' );

		$class = 'eltiz-text-editor' . ( $drop_cap ? ' eltiz-drop-cap' : '' );
		echo '<div class="' . esc_attr( $class ) . '">';
		echo wp_kses_post( $editor );
		echo '</div>';
	}
}
