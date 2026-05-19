<?php
declare( strict_types=1 );

namespace EltizBuilder\Forms\Actions;

use EltizBuilder\Forms\ActionBase;

final class WebhookAction extends ActionBase {
	public function get_type(): string  { return 'webhook'; }
	public function get_label(): string { return __( 'Webhook', 'eltiz-builder' ); }

	public function run( array $settings, array $data, int $post_id ): void {
		$url = esc_url_raw( $settings['webhook_url'] ?? '' );
		if ( ! $url ) return;

		wp_remote_post( $url, [
			'body'    => wp_json_encode( $data ),
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => 10,
			'blocking' => false,
		] );
	}
}
