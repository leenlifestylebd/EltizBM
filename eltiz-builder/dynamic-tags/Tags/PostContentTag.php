<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class PostContentTag extends DynamicTagBase {
	public function get_name(): string  { return 'post_content'; }
	public function get_label(): string { return __( 'Post Content', 'eltiz-builder' ); }
	public function get_value( array $args = [] ): mixed { return get_the_content(); }
}
