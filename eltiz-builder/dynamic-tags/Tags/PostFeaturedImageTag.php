<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class PostFeaturedImageTag extends DynamicTagBase {
	public function get_name(): string  { return 'post_featured_image'; }
	public function get_label(): string { return __( 'Featured Image', 'eltiz-builder' ); }
	public function get_value( array $args = [] ): mixed {
		$size = sanitize_key( $args['size'] ?? 'large' );
		return get_the_post_thumbnail_url( null, $size ) ?? '';
	}
}
