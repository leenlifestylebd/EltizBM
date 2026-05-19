<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class GalleryWidget extends WidgetBase {

	public function get_name(): string  { return 'image-gallery'; }
	public function get_title(): string { return __( 'Image Gallery', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-gallery-grid'; }
	public function get_categories(): array { return [ 'media' ]; }
	public function get_keywords(): array { return [ 'image','photo','gallery','grid','portfolio' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_gallery', [ 'label' => __( 'Image Gallery', 'eltiz-builder' ) ] );

		$this->add_control( 'wp_gallery', [
			'label'     => __( 'Add Images', 'eltiz-builder' ),
			'type'      => 'gallery',
			'default'   => [],
			'show_label'=> false,
			'dynamic'   => [ 'active' => true ],
		] );

		$this->add_control( 'gallery_layout', [
			'label'   => __( 'Layout', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'grid'=>__('Grid','eltiz-builder'),'masonry'=>__('Masonry','eltiz-builder'),'justified'=>__('Justified','eltiz-builder') ],
			'default' => 'grid',
		] );

		$this->add_responsive_control( 'columns', [
			'label'   => __( 'Columns', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ '1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6' ],
			'default' => '3',
			'selectors' => [ '{{WRAPPER}} .eltiz-gallery-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);' ],
		] );

		$this->add_control( 'image_size', [
			'label'   => __( 'Image Size', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'thumbnail'=>'Thumbnail','medium'=>'Medium','large'=>'Large','full'=>'Full' ],
			'default' => 'medium',
		] );

		$this->add_control( 'link_to', [
			'label'   => __( 'Link', 'eltiz-builder' ),
			'type'    => 'select',
			'options' => [ 'none'=>__('None','eltiz-builder'),'file'=>__('Media File','eltiz-builder'),'attachment'=>__('Attachment Page','eltiz-builder') ],
			'default' => 'file',
		] );

		$this->add_control( 'open_lightbox', [
			'label'     => __( 'Lightbox', 'eltiz-builder' ),
			'type'      => 'select',
			'options'   => [ 'yes'=>__('Yes','eltiz-builder'),'no'=>__('No','eltiz-builder') ],
			'default'   => 'yes',
			'condition' => [ 'link_to' => 'file' ],
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$images   = $settings['wp_gallery'] ?? [];

		if ( empty( $images ) ) {
			return;
		}

		$layout   = $settings['gallery_layout'] ?? 'grid';
		$size     = $settings['image_size'] ?? 'medium';
		$link_to  = $settings['link_to'] ?? 'file';
		$lightbox = 'yes' === ( $settings['open_lightbox'] ?? 'yes' ) && 'file' === $link_to;

		echo '<div class="eltiz-gallery eltiz-gallery-' . esc_attr( $layout ) . ' eltiz-gallery-grid"' . ( $lightbox ? ' data-eltiz-lightbox="yes"' : '' ) . '>';

		foreach ( $images as $img ) {
			$img_tag = wp_get_attachment_image( $img['id'] ?? 0, $size, false, [ 'class' => 'eltiz-gallery-image', 'loading' => 'lazy' ] );
			$full    = wp_get_attachment_image_url( $img['id'] ?? 0, 'full' );

			echo '<div class="eltiz-gallery-item">';
			if ( 'file' === $link_to && $full ) {
				echo '<a href="' . esc_url( $full ) . '" data-eltiz-lightbox="' . ( $lightbox ? 'yes' : 'no' ) . '">';
				echo $img_tag;
				echo '</a>';
			} elseif ( 'attachment' === $link_to ) {
				echo '<a href="' . esc_url( get_attachment_link( $img['id'] ?? 0 ) ) . '">' . $img_tag . '</a>';
			} else {
				echo $img_tag;
			}
			echo '</div>';
		}

		echo '</div>';
	}
}
