<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class TaxonomyCondition extends ConditionBase {
	public function get_name(): string  { return 'taxonomy'; }
	public function get_label(): string { return __( 'Taxonomy', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool {
		$taxonomy = sanitize_key( $args['taxonomy'] ?? '' );
		$term_id  = absint( $args['term_id'] ?? 0 );
		if ( ! $taxonomy ) return false;
		return $term_id
			? has_term( $term_id, $taxonomy )
			: ( is_tax( $taxonomy ) || is_category() || is_tag() );
	}
}
