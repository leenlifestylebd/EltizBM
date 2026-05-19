<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class FrontPageCondition extends ConditionBase {
	public function get_name(): string  { return 'front_page'; }
	public function get_label(): string { return __( 'Front Page', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool { return is_front_page(); }
}
