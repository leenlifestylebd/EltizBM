<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class SingularCondition extends ConditionBase {
	public function get_name(): string  { return 'singular'; }
	public function get_label(): string { return __( 'Singular', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool {
		$subtype = $args['subtype'] ?? '';
		return $subtype ? is_singular( $subtype ) : is_singular();
	}
}
