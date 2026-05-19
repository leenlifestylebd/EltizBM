<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class SearchFormWidget extends WidgetBase {
	public function get_name(): string  { return 'search-form'; }
	public function get_title(): string { return __( 'Search Form', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-search'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_search', [ 'label' => __( 'Search Form', 'eltiz-builder' ) ] );
		$this->add_control( 'placeholder', [ 'label'=>__('Placeholder','eltiz-builder'),'type'=>'text','default'=>__('Search...','eltiz-builder') ] );
		$this->add_control( 'button_type', [ 'label'=>__('Button Type','eltiz-builder'),'type'=>'select','options'=>['text'=>'Text','icon'=>'Icon'],'default'=>'icon' ] );
		$this->add_control( 'button_text', [ 'label'=>__('Button Text','eltiz-builder'),'type'=>'text','default'=>__('Search','eltiz-builder'),'condition'=>['button_type'=>'text'] ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		echo '<div class="eltiz-search-form-wrapper">';
		echo '<form role="search" method="get" action="' . esc_url( home_url('/') ) . '" class="eltiz-search-form">';
		echo '<div class="eltiz-search-form-fields">';
		echo '<input type="search" class="eltiz-search-form__input" placeholder="' . esc_attr( $s['placeholder'] ?? __('Search...','eltiz-builder') ) . '" value="' . esc_attr( get_search_query() ) . '" name="s" title="' . esc_attr__('Search for:','eltiz-builder') . '" aria-label="' . esc_attr__('Search','eltiz-builder') . '">';
		echo '<button type="submit" class="eltiz-search-form__submit" aria-label="' . esc_attr__('Submit Search','eltiz-builder') . '">';
		if ( 'icon' === ($s['button_type'] ?? 'icon') ) {
			echo '<i class="fas fa-search" aria-hidden="true"></i>';
		} else {
			echo esc_html( $s['button_text'] ?? __('Search','eltiz-builder') );
		}
		echo '</button>';
		echo '</div>';
		echo '</form>';
		echo '</div>';
	}
}
