<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class MediaControl extends ControlBase {

	public function get_type(): string { return 'media'; }

	public function get_default_value(): mixed {
		return [ 'id' => 0, 'url' => '' ];
	}

	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) {
			return $this->get_default_value();
		}
		return [
			'id'  => absint( $value['id'] ?? 0 ),
			'url' => esc_url_raw( $value['url'] ?? '' ),
		];
	}

	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'media_type' => 'image',
			'mime_types' => [],
		] );
	}
}
