<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class PostMetaTag extends DynamicTagBase {
	public function get_name(): string  { return 'post_meta'; }
	public function get_label(): string { return __( 'Post Meta', 'eltiz-builder' ); }
	public function get_value( array $args = [] ): mixed {
		$key = sanitize_key( $args['key'] ?? '' );
		return $key ? get_post_meta( get_the_ID(), $key, true ) : '';
	}
	public function get_args_config(): array {
		return [ [ 'name' => 'key', 'label' => __( 'Meta Key', 'eltiz-builder' ), 'type' => 'text' ] ];
	}
}
