<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class AuthorBioTag extends DynamicTagBase {
	public function get_name(): string  { return 'author_bio'; }
	public function get_label(): string { return __( 'Author Bio', 'eltiz-builder' ); }
	public function get_group(): string { return 'author'; }
	public function get_value( array $args = [] ): mixed {
		return get_the_author_meta( 'description' );
	}
}
