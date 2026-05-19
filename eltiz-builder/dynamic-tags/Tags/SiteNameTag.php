<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class SiteNameTag extends DynamicTagBase {
	public function get_name(): string  { return 'site_name'; }
	public function get_label(): string { return __( 'Site Name', 'eltiz-builder' ); }
	public function get_group(): string { return 'site'; }
	public function get_value( array $args = [] ): mixed { return get_bloginfo( 'name' ); }
}
