<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class VideoWidget extends WidgetBase {

	public function get_name(): string  { return 'video'; }
	public function get_title(): string { return __( 'Video', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-video-camera'; }
	public function get_categories(): array { return [ 'media' ]; }
	public function get_keywords(): array { return [ 'video', 'youtube', 'vimeo', 'embed' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_video', [ 'label' => __( 'Video', 'eltiz-builder' ) ] );

		$this->add_control( 'video_type', [
			'label'   => __( 'Source', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'youtube'=>'YouTube','vimeo'=>'Vimeo','hosted'=>__('Self Hosted','eltiz-builder') ],
			'default' => 'youtube',
		] );

		$this->add_control( 'youtube_url', [
			'label'     => __( 'YouTube URL', 'eltiz-builder' ),
			'type'      => 'text',
			'default'   => 'https://www.youtube.com/watch?v=XHOmBV4js_E',
			'condition' => [ 'video_type' => 'youtube' ],
			'dynamic'   => [ 'active' => true ],
		] );

		$this->add_control( 'vimeo_url', [
			'label'     => __( 'Vimeo URL', 'eltiz-builder' ),
			'type'      => 'text',
			'default'   => 'https://vimeo.com/235215203',
			'condition' => [ 'video_type' => 'vimeo' ],
			'dynamic'   => [ 'active' => true ],
		] );

		$this->add_control( 'hosted_url', [
			'label'     => __( 'Choose File', 'eltiz-builder' ),
			'type'      => 'media',
			'media_type' => 'video',
			'condition' => [ 'video_type' => 'hosted' ],
			'dynamic'   => [ 'active' => true ],
		] );

		$this->add_control( 'autoplay', [
			'label'   => __( 'Autoplay', 'eltiz-builder' ),
			'type'    => 'switcher',
			'default' => '',
		] );

		$this->add_control( 'mute', [
			'label'   => __( 'Mute', 'eltiz-builder' ),
			'type'    => 'switcher',
			'default' => '',
		] );

		$this->add_control( 'loop', [
			'label'   => __( 'Loop', 'eltiz-builder' ),
			'type'    => 'switcher',
			'default' => '',
		] );

		$this->add_control( 'controls', [
			'label'   => __( 'Player Controls', 'eltiz-builder' ),
			'type'    => 'switcher',
			'default' => 'yes',
		] );

		$this->add_control( 'show_image_overlay', [
			'label'   => __( 'Image Overlay', 'eltiz-builder' ),
			'type'    => 'switcher',
			'default' => '',
		] );

		$this->add_control( 'image_overlay', [
			'label'     => __( 'Choose Image', 'eltiz-builder' ),
			'type'      => 'media',
			'condition' => [ 'show_image_overlay' => 'yes' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_video_style', [ 'label'=>__('Video Box','eltiz-builder'),'tab'=>'style' ] );

		$this->add_control( 'aspect_ratio', [
			'label'   => __( 'Aspect Ratio', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ '169'=>'16:9','219'=>'21:9','43'=>'4:3','32'=>'3:2','11'=>'1:1','916'=>'9:16' ],
			'default' => '169',
			'prefix_class' => 'eltiz-aspect-ratio-',
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$type     = $settings['video_type'] ?? 'youtube';

		$video_url = match ( $type ) {
			'youtube' => $this->get_youtube_embed_url( $settings ),
			'vimeo'   => $this->get_vimeo_embed_url( $settings ),
			'hosted'  => $settings['hosted_url']['url'] ?? '',
			default   => '',
		};

		if ( empty( $video_url ) ) {
			return;
		}

		echo '<div class="eltiz-video-container">';

		if ( 'yes' === ( $settings['show_image_overlay'] ?? '' ) && ! empty( $settings['image_overlay']['url'] ) ) {
			echo '<div class="eltiz-video-overlay">';
			echo '<img src="' . esc_url( $settings['image_overlay']['url'] ) . '" alt="" loading="lazy">';
			echo '<button class="eltiz-video-play-btn" aria-label="' . esc_attr__( 'Play video', 'eltiz-builder' ) . '">';
			echo '<i class="eicon-play-o" aria-hidden="true"></i>';
			echo '</button>';
			echo '</div>';
		}

		if ( 'hosted' === $type ) {
			$autoplay = 'yes' === ( $settings['autoplay'] ?? '' ) ? ' autoplay' : '';
			$muted    = 'yes' === ( $settings['mute'] ?? '' ) ? ' muted' : '';
			$loop     = 'yes' === ( $settings['loop'] ?? '' ) ? ' loop' : '';
			$controls = 'yes' !== ( $settings['controls'] ?? 'yes' ) ? '' : ' controls';
			echo '<video src="' . esc_url( $video_url ) . '"' . $autoplay . $muted . $loop . $controls . ' playsinline></video>';
		} else {
			echo '<iframe src="' . esc_url( $video_url ) . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>';
		}

		echo '</div>';
	}

	private function get_youtube_embed_url( array $settings ): string {
		$url = $settings['youtube_url'] ?? '';
		preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m );
		$id = $m[1] ?? '';
		if ( ! $id ) return '';

		$params = [];
		if ( 'yes' === ( $settings['autoplay'] ?? '' ) ) $params['autoplay'] = 1;
		if ( 'yes' === ( $settings['mute'] ?? '' ) )     $params['mute']     = 1;
		if ( 'yes' === ( $settings['loop'] ?? '' ) )     { $params['loop'] = 1; $params['playlist'] = $id; }
		if ( 'yes' !== ( $settings['controls'] ?? 'yes' ) ) $params['controls'] = 0;

		$query = $params ? '?' . http_build_query( $params ) : '';
		return "https://www.youtube.com/embed/{$id}{$query}";
	}

	private function get_vimeo_embed_url( array $settings ): string {
		$url = $settings['vimeo_url'] ?? '';
		preg_match( '/vimeo\.com\/(\d+)/', $url, $m );
		$id = $m[1] ?? '';
		if ( ! $id ) return '';

		$params = [];
		if ( 'yes' === ( $settings['autoplay'] ?? '' ) ) $params['autoplay'] = 1;
		if ( 'yes' === ( $settings['mute'] ?? '' ) )     $params['muted']    = 1;
		if ( 'yes' === ( $settings['loop'] ?? '' ) )     $params['loop']     = 1;

		$query = $params ? '?' . http_build_query( $params ) : '';
		return "https://player.vimeo.com/video/{$id}{$query}";
	}
}
