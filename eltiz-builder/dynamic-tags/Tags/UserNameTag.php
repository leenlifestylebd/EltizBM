<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class UserNameTag extends DynamicTagBase {
	public function get_name(): string  { return 'user_name'; }
	public function get_label(): string { return __( 'Current User Name', 'eltiz-builder' ); }
	public function get_group(): string { return 'user'; }
	public function get_value( array $args = [] ): mixed { return is_user_logged_in() ? wp_get_current_user()->display_name : ''; }
}
