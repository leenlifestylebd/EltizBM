<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class GridControl extends ControlBase {
	public function get_type(): string { return 'grid'; }
	public function get_default_value(): mixed {
		return [ 'columns' => 3, 'template_columns' => '', 'rows' => '', 'template_rows' => '' ];
	}
	protected function is_responsive(): bool { return true; }
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		return [
			'columns'          => absint( $value['columns'] ?? 3 ),
			'template_columns' => sanitize_text_field( $value['template_columns'] ?? '' ),
			'rows'             => sanitize_text_field( $value['rows'] ?? '' ),
			'template_rows'    => sanitize_text_field( $value['template_rows'] ?? '' ),
		];
	}
}
