<?php
declare( strict_types=1 );

namespace EltizBuilder\Forms\Actions;

use EltizBuilder\Forms\ActionBase;

final class EmailAction extends ActionBase {

	public function get_type(): string  { return 'email'; }
	public function get_label(): string { return __( 'Email', 'eltiz-builder' ); }

	public function run( array $settings, array $data, int $post_id ): void {
		$to      = sanitize_email( $settings['email_to'] ?? get_option( 'admin_email' ) );
		$subject = sanitize_text_field( $settings['email_subject'] ?? __( 'New Form Submission', 'eltiz-builder' ) );
		$from    = sanitize_email( $settings['email_from'] ?? get_option( 'admin_email' ) );
		$from_name = sanitize_text_field( $settings['email_from_name'] ?? get_bloginfo( 'name' ) );

		$body = $this->build_body( $data, $settings );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			"From: {$from_name} <{$from}>",
		];

		wp_mail( $to, $subject, $body, $headers );
	}

	private function build_body( array $data, array $settings ): string {
		$html = '<table cellspacing="0" cellpadding="10" border="0" style="width:100%;font-family:Arial,sans-serif;">';

		foreach ( $data as $key => $value ) {
			$html .= '<tr>';
			$html .= '<th style="text-align:left;background:#f5f5f5;padding:10px 15px;border:1px solid #ddd;">' . esc_html( $key ) . '</th>';
			$html .= '<td style="padding:10px 15px;border:1px solid #ddd;">' . wp_kses_post( (string) $value ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</table>';

		return $html;
	}
}
