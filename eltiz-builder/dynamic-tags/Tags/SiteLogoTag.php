<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class SiteLogoTag extends DynamicTagBase {
	public function get_name(): string  { return 'site_logo'; }
	public function get_label(): string { return __( 'Site Logo', 'eltiz-builder' ); }
	public function get_group(): string { return 'site'; }
	public function get_value( array $args = [] ): mixed {
		$logo_id = get_theme_mod( 'custom_logo' );
		return $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	}
}
