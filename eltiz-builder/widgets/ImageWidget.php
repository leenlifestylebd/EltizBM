<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class ImageWidget extends WidgetBase {

	public function get_name(): string  { return 'image'; }
	public function get_title(): string { return __( 'Image', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-image'; }
	public function get_categories(): array { return [ 'media' ]; }
	public function get_keywords(): array { return [ 'image', 'photo', 'picture' ]; }

	protected function register_controls(): void {

		$this->start_controls_section( 'section_image', [ 'label' => __( 'Image', 'eltiz-builder' ) ] );

		$this->add_control( 'image', [
			'label'      => __( 'Choose Image', 'eltiz-builder' ),
			'type'       => 'media',
			'media_type' => 'image',
			'default'    => [ 'url' => '' ],
			'dynamic'    => [ 'active' => true ],
		] );

		$this->add_control( 'image_size', [
			'label'   => __( 'Image Size', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'thumbnail'=>'Thumbnail','medium'=>'Medium','large'=>'Large','full'=>'Full','custom'=>'Custom' ],
			'default' => 'large',
		] );

		$this->add_control( 'alignment', [
			'label'   => __( 'Alignment', 'eltiz-builder' ),
			'type'    => 'choose',
			'options' => [
				'left'   => [ 'title'=>__('Left','eltiz-builder'),   'icon'=>'eicon-text-align-left' ],
				'center' => [ 'title'=>__('Center','eltiz-builder'), 'icon'=>'eicon-text-align-center' ],
				'right'  => [ 'title'=>__('Right','eltiz-builder'),  'icon'=>'eicon-text-align-right' ],
			],
			'selectors' => [ '{{WRAPPER}}' => 'text-align: {{VALUE}};' ],
		] );

		$this->add_control( 'caption_source', [
			'label'   => __( 'Caption', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'none'=>__('None','eltiz-builder'), 'attachment'=>__('Attachment Caption','eltiz-builder'), 'custom'=>__('Custom Caption','eltiz-builder') ],
			'default' => 'none',
		] );

		$this->add_control( 'caption', [
			'label'     => __( 'Custom Caption', 'eltiz-builder' ),
			'type'      => 'text',
			'condition' => [ 'caption_source' => 'custom' ],
		] );

		$this->add_control( 'link_to', [
			'label'   => __( 'Link', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'none'=>__('None','eltiz-builder'), 'file'=>__('Media File','eltiz-builder'), 'custom'=>__('Custom URL','eltiz-builder') ],
			'default' => 'none',
		] );

		$this->add_control( 'link', [
			'label'     => __( 'Link URL', 'eltiz-builder' ),
			'type'      => 'url',
			'condition' => [ 'link_to' => 'custom' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style_image', [ 'label'=>__('Image','eltiz-builder'),'tab'=>'style' ] );

		$this->add_responsive_control( 'width', [
			'label'     => __( 'Width', 'eltiz-builder' ),
			'type'      => 'slider',
			'units'     => [ '%','px','vw' ],
			'selectors' => [ '{{WRAPPER}} .eltiz-image img' => 'width: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'max_width', [
			'label'     => __( 'Max Width', 'eltiz-builder' ),
			'type'      => 'slider',
			'units'     => [ '%','px','vw' ],
			'selectors' => [ '{{WRAPPER}} .eltiz-image img' => 'max-width: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'border_radius', [
			'label'     => __( 'Border Radius', 'eltiz-builder' ),
			'type'      => 'dimensions',
			'selectors' => [ '{{WRAPPER}} .eltiz-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_group_control( 'box_shadow', [
			'name'     => 'image_box_shadow',
			'selector' => '{{WRAPPER}} .eltiz-image img',
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$image    = $settings['image'] ?? [];

		if ( empty( $image['url'] ) && empty( $image['id'] ) ) {
			return;
		}

		$img_src = $image['url'] ?? '';
		$img_alt = '';

		if ( ! empty( $image['id'] ) ) {
			$img_alt = get_post_meta( $image['id'], '_wp_attachment_image_alt', true );
			if ( $settings['image_size'] !== 'custom' ) {
				$img_tag = wp_get_attachment_image( $image['id'], $settings['image_size'] ?? 'large' );
			}
		}

		if ( empty( $img_tag ) ) {
			$img_tag = '<img src="' . esc_url( $img_src ) . '" alt="' . esc_attr( $img_alt ) . '" loading="lazy">';
		}

		// Wrap in link if needed.
		$link_to = $settings['link_to'] ?? 'none';
		if ( 'file' === $link_to && ! empty( $image['url'] ) ) {
			$img_tag = '<a href="' . esc_url( $image['url'] ) . '">' . $img_tag . '</a>';
		} elseif ( 'custom' === $link_to && ! empty( $settings['link']['url'] ) ) {
			$link    = $settings['link'];
			$target  = ! empty( $link['is_external'] ) ? ' target="_blank"' : '';
			$rel     = ! empty( $link['nofollow'] ) ? ' rel="nofollow"' : '';
			$img_tag = '<a href="' . esc_url( $link['url'] ) . '"' . $target . $rel . '>' . $img_tag . '</a>';
		}

		echo '<figure class="eltiz-image">';
		echo $img_tag; // Already escaped via WP functions.
		if ( 'none' !== ( $settings['caption_source'] ?? 'none' ) ) {
			$caption = 'custom' === $settings['caption_source']
				? ( $settings['caption'] ?? '' )
				: ( ! empty( $image['id'] ) ? wp_get_attachment_caption( $image['id'] ) : '' );
			if ( $caption ) {
				echo '<figcaption class="eltiz-image-caption">' . esc_html( $caption ) . '</figcaption>';
			}
		}
		echo '</figure>';
	}
}
