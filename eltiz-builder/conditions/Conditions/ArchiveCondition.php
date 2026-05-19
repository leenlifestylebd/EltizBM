<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class ArchiveCondition extends ConditionBase {
	public function get_name(): string  { return 'archive'; }
	public function get_label(): string { return __( 'Archive', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool { return is_archive(); }
}
