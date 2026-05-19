<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class UserEmailTag extends DynamicTagBase {
	public function get_name(): string  { return 'user_email'; }
	public function get_label(): string { return __( 'Current User Email', 'eltiz-builder' ); }
	public function get_group(): string { return 'user'; }
	public function get_value( array $args = [] ): mixed { return is_user_logged_in() ? wp_get_current_user()->user_email : ''; }
}
