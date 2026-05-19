<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags;

/**
 * Dynamic Tags — resolves placeholders in widget settings at render time.
 */
final class DynamicTagsManager {

	private array $tags = [];

	public function init(): void {
		$this->register_core_tags();
		do_action( 'eltiz/dynamic_tags/register', $this );
	}

	private function register_core_tags(): void {
		$this->register( new Tags\PostTitleTag() );
		$this->register( new Tags\PostContentTag() );
		$this->register( new Tags\PostExcerptTag() );
		$this->register( new Tags\PostDateTag() );
		$this->register( new Tags\PostAuthorTag() );
		$this->register( new Tags\PostFeaturedImageTag() );
		$this->register( new Tags\PostURLTag() );
		$this->register( new Tags\SiteNameTag() );
		$this->register( new Tags\SiteURLTag() );
		$this->register( new Tags\SiteLogoTag() );
		$this->register( new Tags\UserNameTag() );
		$this->register( new Tags\UserEmailTag() );
		$this->register( new Tags\CurrentDateTag() );
		$this->register( new Tags\PostMetaTag() );
		$this->register( new Tags\TermNameTag() );
		$this->register( new Tags\TermDescriptionTag() );
		$this->register( new Tags\AuthorBioTag() );
		$this->register( new Tags\AuthorProfileURLTag() );
	}

	public function register( DynamicTagBase $tag ): void {
		$this->tags[ $tag->get_name() ] = $tag;
	}

	public function get( string $name ): ?DynamicTagBase {
		return $this->tags[ $name ] ?? null;
	}

	public function get_all(): array {
		return $this->tags;
	}

	/**
	 * Resolve a dynamic tag value.
	 *
	 * Tag format: {{ tag_name | arg1:val1 }}
	 */
	public function resolve( string $tag_expression ): mixed {
		$tag_expression = trim( $tag_expression );

		// Strip delimiters.
		if ( str_starts_with( $tag_expression, '{{' ) ) {
			$tag_expression = substr( $tag_expression, 2, -2 );
		}

		$parts     = explode( '|', $tag_expression, 2 );
		$tag_name  = sanitize_key( trim( $parts[0] ) );
		$args      = $this->parse_args( trim( $parts[1] ?? '' ) );

		$tag = $this->tags[ $tag_name ] ?? null;

		if ( ! $tag ) {
			return '';
		}

		return $tag->get_value( $args );
	}

	/**
	 * Walk all string settings and resolve dynamic tags inline.
	 */
	public function process_settings( array $settings ): array {
		array_walk_recursive( $settings, function ( &$value ) {
			if ( is_string( $value ) && str_contains( $value, '{{' ) ) {
				$value = preg_replace_callback(
					'/\{\{(.+?)\}\}/',
					fn( $m ) => $this->resolve( '{{' . $m[1] . '}}' ),
					$value
				);
			}
		} );

		return $settings;
	}

	private function parse_args( string $args_string ): array {
		$result = [];
		if ( ! $args_string ) return $result;

		foreach ( explode( '&', $args_string ) as $pair ) {
			[ $key, $val ] = array_map( 'trim', explode( ':', $pair, 2 ) + [ 1 => '' ] );
			if ( $key ) {
				$result[ sanitize_key( $key ) ] = sanitize_text_field( $val );
			}
		}

		return $result;
	}

	public function get_config(): array {
		$config = [];
		foreach ( $this->tags as $name => $tag ) {
			$config[ $name ] = [
				'name'  => $tag->get_name(),
				'label' => $tag->get_label(),
				'group' => $tag->get_group(),
				'args'  => $tag->get_args_config(),
			];
		}
		return $config;
	}
}
