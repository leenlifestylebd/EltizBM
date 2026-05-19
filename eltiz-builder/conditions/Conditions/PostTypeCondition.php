<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class PostTypeCondition extends ConditionBase {
	public function get_name(): string  { return 'post_type'; }
	public function get_label(): string { return __( 'Post Type', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool {
		$type = sanitize_key( $args['post_type'] ?? '' );
		return $type ? is_singular( $type ) || ( is_archive() && get_post_type() === $type ) : false;
	}
}
