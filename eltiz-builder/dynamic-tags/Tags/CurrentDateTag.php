<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags\Tags;

use EltizBuilder\DynamicTags\DynamicTagBase;

final class CurrentDateTag extends DynamicTagBase {
	public function get_name(): string  { return 'current_date'; }
	public function get_label(): string { return __( 'Current Date', 'eltiz-builder' ); }
	public function get_group(): string { return 'site'; }
	public function get_value( array $args = [] ): mixed {
		$format = sanitize_text_field( $args['format'] ?? get_option( 'date_format' ) );
		return wp_date( $format );
	}
}
