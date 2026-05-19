<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class SearchCondition extends ConditionBase {
	public function get_name(): string  { return 'search'; }
	public function get_label(): string { return __( 'Search Results', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool { return is_search(); }
}
