<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class URLControl extends ControlBase {
	public function get_type(): string { return 'url'; }
	public function get_default_value(): mixed {
		return [ 'url' => '', 'is_external' => false, 'nofollow' => false, 'custom_attributes' => '' ];
	}
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		return [
			'url'               => esc_url_raw( $value['url'] ?? '' ),
			'is_external'       => (bool) ( $value['is_external'] ?? false ),
			'nofollow'          => (bool) ( $value['nofollow'] ?? false ),
			'custom_attributes' => sanitize_text_field( $value['custom_attributes'] ?? '' ),
		];
	}
}
