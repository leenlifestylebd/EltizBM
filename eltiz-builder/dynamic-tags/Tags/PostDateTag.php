<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class PostDateTag extends DynamicTagBase {
	public function get_name(): string  { return 'post_date'; }
	public function get_label(): string { return __( 'Post Date', 'eltiz-builder' ); }
	public function get_value( array $args = [] ): mixed {
		$format = sanitize_text_field( $args['format'] ?? get_option( 'date_format' ) );
		return get_the_date( $format );
	}
	public function get_args_config(): array {
		return [ [ 'name' => 'format', 'label' => __( 'Format', 'eltiz-builder' ), 'type' => 'text' ] ];
	}
}
