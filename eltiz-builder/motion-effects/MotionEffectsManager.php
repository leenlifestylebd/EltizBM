<?php
declare( strict_types=1 );

namespace EltizBuilder\MotionEffects;

/**
 * Motion Effects — entrance animations, scroll effects, parallax, sticky.
 */
final class MotionEffectsManager {

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'eltiz/frontend/element_attributes', [ $this, 'add_motion_attributes' ], 10, 2 );
		do_action( 'eltiz/motion_effects/init', $this );
	}

	public function enqueue_assets(): void {
		wp_register_script(
			'eltiz-motion',
			ELTIZ_ASSETS_URL . 'js/motion-effects.min.js',
			[ 'eltiz-frontend' ],
			ELTIZ_VERSION,
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);

		wp_register_style(
			'eltiz-animations',
			ELTIZ_ASSETS_URL . 'css/animations.min.css',
			[],
			ELTIZ_VERSION
		);
	}

	public function add_motion_attributes( array $attrs, array $settings ): array {
		$motion = $settings['motion_effects'] ?? [];

		if ( ! empty( $motion['entrance_animation'] ) ) {
			$attrs['data-eltiz-animation']       = sanitize_key( $motion['entrance_animation'] );
			$attrs['data-eltiz-animation-delay'] = absint( $motion['animation_delay'] ?? 0 );
			$attrs['data-eltiz-animation-duration'] = sanitize_key( $motion['animation_duration'] ?? 'normal' );

			wp_enqueue_script( 'eltiz-motion' );
			wp_enqueue_style( 'eltiz-animations' );
		}

		if ( ! empty( $motion['scroll_effects'] ) ) {
			$attrs['data-eltiz-scroll-effect'] = wp_json_encode( $this->sanitize_scroll_effects( $motion['scroll_effects'] ) );
			wp_enqueue_script( 'eltiz-motion' );
		}

		if ( ! empty( $motion['sticky'] ) ) {
			$attrs['data-eltiz-sticky'] = wp_json_encode( $this->sanitize_sticky( $motion['sticky'] ) );
			wp_enqueue_script( 'eltiz-motion' );
		}

		return $attrs;
	}

	private function sanitize_scroll_effects( array $effects ): array {
		return [
			'type'      => sanitize_key( $effects['type'] ?? 'none' ),
			'speed'     => (float) ( $effects['speed'] ?? 1 ),
			'direction' => sanitize_key( $effects['direction'] ?? 'up' ),
		];
	}

	private function sanitize_sticky( array $sticky ): array {
		return [
			'active'   => (bool) ( $sticky['active'] ?? false ),
			'type'     => sanitize_key( $sticky['type'] ?? 'top' ),
			'offset'   => absint( $sticky['offset'] ?? 0 ),
			'effects_offset' => absint( $sticky['effects_offset'] ?? 0 ),
		];
	}

	public function get_effects_config(): array {
		return [
			'animations' => $this->get_animations(),
			'scroll'     => $this->get_scroll_effects(),
			'sticky'     => $this->get_sticky_options(),
		];
	}

	private function get_animations(): array {
		return [
			'none'        => __( 'None', 'eltiz-builder' ),
			'fadeIn'      => 'Fade In',
			'fadeInDown'  => 'Fade In Down',
			'fadeInLeft'  => 'Fade In Left',
			'fadeInRight' => 'Fade In Right',
			'fadeInUp'    => 'Fade In Up',
			'bounceIn'    => 'Bounce In',
			'slideInDown' => 'Slide In Down',
			'slideInLeft' => 'Slide In Left',
			'slideInRight'=> 'Slide In Right',
			'slideInUp'   => 'Slide In Up',
			'zoomIn'      => 'Zoom In',
			'rotateIn'    => 'Rotate In',
			'pulse'       => 'Pulse',
			'tada'        => 'Tada',
			'wobble'      => 'Wobble',
			'jello'       => 'Jello',
			'heartBeat'   => 'Heart Beat',
			'flipInX'     => 'Flip In X',
			'flipInY'     => 'Flip In Y',
		];
	}

	private function get_scroll_effects(): array {
		return [
			'none'      => __( 'None', 'eltiz-builder' ),
			'parallax'  => __( 'Parallax', 'eltiz-builder' ),
			'mouse-track' => __( 'Mouse Track', 'eltiz-builder' ),
			'tilt'      => __( 'Tilt', 'eltiz-builder' ),
			'scale'     => __( 'Scale', 'eltiz-builder' ),
			'rotate'    => __( 'Rotate', 'eltiz-builder' ),
			'opacity'   => __( 'Opacity', 'eltiz-builder' ),
		];
	}

	private function get_sticky_options(): array {
		return [
			'none'   => __( 'None', 'eltiz-builder' ),
			'top'    => __( 'Top', 'eltiz-builder' ),
			'bottom' => __( 'Bottom', 'eltiz-builder' ),
		];
	}
}
