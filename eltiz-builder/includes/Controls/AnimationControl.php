<?php
declare( strict_types=1 );

namespace EltizBuilder\Includes\Controls;

final class AnimationControl extends ControlBase {
	public function get_type(): string { return 'animation'; }
	public function get_default_value(): mixed { return ''; }
	public function sanitize( mixed $value ): mixed { return sanitize_key( (string) $value ); }
	public function get_schema(): array {
		return array_merge( parent::get_schema(), [
			'groups' => [
				'Attention Seekers' => [ 'bounce','flash','pulse','rubberBand','shake','swing','tada','wobble','jello','heartBeat' ],
				'Bouncing Entrances' => [ 'bounceIn','bounceInDown','bounceInLeft','bounceInRight','bounceInUp' ],
				'Fading Entrances'  => [ 'fadeIn','fadeInDown','fadeInLeft','fadeInRight','fadeInUp','fadeInTopLeft','fadeInTopRight','fadeInBottomLeft','fadeInBottomRight' ],
				'Flippers'          => [ 'flip','flipInX','flipInY' ],
				'Rotating Entrances' => [ 'rotateIn','rotateInDownLeft','rotateInDownRight','rotateInUpLeft','rotateInUpRight' ],
				'Sliding Entrances' => [ 'slideInDown','slideInLeft','slideInRight','slideInUp' ],
				'Zoom Entrances'    => [ 'zoomIn','zoomInDown','zoomInLeft','zoomInRight','zoomInUp' ],
			],
		] );
	}
}
