<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class HTMLWidget extends WidgetBase {
	public function get_name(): string  { return 'html'; }
	public function get_title(): string { return __( 'HTML', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-code'; }
	public function get_keywords(): array { return [ 'html', 'code', 'embed', 'raw' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_html', [ 'label' => __( 'HTML', 'eltiz-builder' ) ] );
		$this->add_control( 'html_code', [
			'label'       => __( 'HTML Code', 'eltiz-builder' ),
			'type'        => 'code',
			'language'    => 'html',
			'rows'        => 10,
			'default'     => '<p>Your HTML code here</p>',
		] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$html = $this->get_settings( 'html_code' ) ?? '';
		// HTML widget allows raw HTML — only users with unfiltered_html capability can set it.
		if ( current_user_can( 'unfiltered_html' ) ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo wp_kses_post( $html );
		}
	}
}
