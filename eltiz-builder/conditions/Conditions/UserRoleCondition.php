<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions\Conditions;

use EltizBuilder\Conditions\ConditionBase;

final class UserRoleCondition extends ConditionBase {
	public function get_name(): string  { return 'user_role'; }
	public function get_label(): string { return __( 'User Role', 'eltiz-builder' ); }
	public function check( array $args = [] ): bool {
		$role = sanitize_key( $args['role'] ?? '' );
		if ( ! $role ) return true;
		$user = wp_get_current_user();
		return in_array( $role, (array) $user->roles, true );
	}
}
