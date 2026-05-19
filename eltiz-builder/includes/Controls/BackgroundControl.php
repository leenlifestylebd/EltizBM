<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class BackgroundControl extends ControlBase {
	public function get_type(): string { return 'background'; }
	public function get_default_value(): mixed {
		return [ 'background' => 'classic', 'color' => '', 'image' => [ 'id'=>0,'url'=>'' ], 'gradient_type' => 'linear', 'gradient_angle' => 180 ];
	}
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) return $this->get_default_value();
		$types = [ 'classic', 'gradient', 'video', 'slideshow', 'none' ];
		return [
			'background'     => in_array( $value['background'] ?? 'classic', $types, true ) ? $value['background'] : 'classic',
			'color'          => sanitize_hex_color( $value['color'] ?? '' ) ?? '',
			'image'          => [ 'id' => absint( $value['image']['id'] ?? 0 ), 'url' => esc_url_raw( $value['image']['url'] ?? '' ) ],
			'gradient_type'  => in_array( $value['gradient_type'] ?? 'linear', [ 'linear', 'radial' ], true ) ? $value['gradient_type'] : 'linear',
			'gradient_angle' => (int) ( $value['gradient_angle'] ?? 180 ),
			'gradient_stops' => array_map( fn($s) => [ 'color' => sanitize_hex_color($s['color']??'')??'', 'position'=>(int)($s['position']??0) ], (array)($value['gradient_stops'] ?? []) ),
			'video_url'      => esc_url_raw( $value['video_url'] ?? '' ),
			'repeat'         => sanitize_key( $value['repeat'] ?? 'no-repeat' ),
			'size'           => sanitize_key( $value['size'] ?? 'cover' ),
			'position'       => sanitize_text_field( $value['position'] ?? 'center center' ),
			'attachment'     => sanitize_key( $value['attachment'] ?? 'scroll' ),
		];
	}
	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'types' => [ 'classic', 'gradient', 'video', 'slideshow' ],
		] );
	}
}
