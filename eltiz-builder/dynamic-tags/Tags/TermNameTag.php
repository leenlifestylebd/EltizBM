<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class TermNameTag extends DynamicTagBase {
	public function get_name(): string  { return 'term_name'; }
	public function get_label(): string { return __( 'Term Name', 'eltiz-builder' ); }
	public function get_group(): string { return 'archive'; }
	public function get_value( array $args = [] ): mixed {
		$term = get_queried_object();
		return ( $term instanceof \WP_Term ) ? $term->name : '';
	}
}
