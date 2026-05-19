<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class NotFoundCondition extends ConditionBase {
	public function get_name(): string  { return 'not_found'; }
	public function get_label(): string { return __( '404 Not Found', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool { return is_404(); }
}
