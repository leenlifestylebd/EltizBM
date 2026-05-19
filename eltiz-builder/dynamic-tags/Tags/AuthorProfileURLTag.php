<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class AuthorProfileURLTag extends DynamicTagBase {
	public function get_name(): string  { return 'author_profile_url'; }
	public function get_label(): string { return __( 'Author Profile URL', 'eltiz-builder' ); }
	public function get_group(): string { return 'author'; }
	public function get_value( array $args = [] ): mixed {
		return get_author_posts_url( get_the_author_meta( 'ID' ) );
	}
}
