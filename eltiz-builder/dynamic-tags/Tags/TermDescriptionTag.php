<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class TermDescriptionTag extends DynamicTagBase {
	public function get_name(): string  { return 'term_description'; }
	public function get_label(): string { return __( 'Term Description', 'eltiz-builder' ); }
	public function get_group(): string { return 'archive'; }
	public function get_value( array $args = [] ): mixed { return term_description(); }
}
