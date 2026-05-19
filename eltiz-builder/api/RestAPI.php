<?php
declare( strict_types=1 );

namespace EltizBuilder\API;

/**
 * Registers all Eltiz Builder REST routes under /eltiz/v1/.
 */
final class RestAPI {

	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		$namespace = 'eltiz/v1';

		// Page data endpoints.
		register_rest_route( $namespace, '/page/(?P<id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_page_data' ],
				'permission_callback' => [ $this, 'edit_capability' ],
				'args'                => [ 'id' => [ 'validate_callback' => fn($v) => is_numeric($v) ] ],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_page_data' ],
				'permission_callback' => [ $this, 'edit_capability' ],
				'args'                => [
					'id'   => [ 'validate_callback' => fn($v) => is_numeric($v) ],
					'data' => [ 'required' => true, 'type' => 'string' ],
				],
			],
		] );

		// Widgets config.
		register_rest_route( $namespace, '/widgets', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_widgets' ],
			'permission_callback' => [ $this, 'edit_capability' ],
		] );

		// Templates.
		register_rest_route( $namespace, '/templates', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_templates' ],
				'permission_callback' => [ $this, 'edit_capability' ],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_template' ],
				'permission_callback' => [ $this, 'edit_capability' ],
			],
		] );

		register_rest_route( $namespace, '/templates/(?P<id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_template' ],
				'permission_callback' => [ $this, 'edit_capability' ],
			],
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_template' ],
				'permission_callback' => [ $this, 'edit_capability' ],
			],
		] );

		// Global styles.
		register_rest_route( $namespace, '/global-styles', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_global_styles' ],
				'permission_callback' => [ $this, 'edit_capability' ],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_global_styles' ],
				'permission_callback' => [ $this, 'manage_capability' ],
			],
		] );

		// Form submissions.
		register_rest_route( $namespace, '/form-submissions', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_form_submissions' ],
			'permission_callback' => [ $this, 'manage_capability' ],
		] );

		// Revision history.
		register_rest_route( $namespace, '/revisions/(?P<post_id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_revisions' ],
				'permission_callback' => [ $this, 'edit_capability' ],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_revision' ],
				'permission_callback' => [ $this, 'edit_capability' ],
			],
		] );

		register_rest_route( $namespace, '/revisions/(?P<post_id>\d+)/restore/(?P<revision_id>\d+)', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'restore_revision' ],
			'permission_callback' => [ $this, 'edit_capability' ],
		] );

		do_action( 'eltiz/rest_api/register_routes', $namespace );
	}

	// ─── Capability checks ────────────────────────────────────────────────────

	public function edit_capability( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	public function manage_capability( \WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	// ─── Page data ────────────────────────────────────────────────────────────

	public function get_page_data( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_REST_Response( [ 'message' => __( 'Forbidden.', 'eltiz-builder' ) ], 403 );
		}

		$data     = get_post_meta( $post_id, '_eltiz_data', true );
		$settings = get_post_meta( $post_id, '_eltiz_page_settings', true );

		return new \WP_REST_Response( [
			'data'     => $data     ? json_decode( $data, true )     : [],
			'settings' => $settings ? json_decode( $settings, true ) : [],
		], 200 );
	}

	public function save_page_data( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id  = (int) $request->get_param( 'id' );
		$data     = $request->get_param( 'data' );
		$settings = $request->get_param( 'settings' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_REST_Response( [ 'message' => __( 'Forbidden.', 'eltiz-builder' ) ], 403 );
		}

		// Validate JSON.
		$decoded = json_decode( $data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_REST_Response( [ 'message' => __( 'Invalid JSON data.', 'eltiz-builder' ) ], 400 );
		}

		// Store revision before save.
		$this->store_revision( $post_id );

		update_post_meta( $post_id, '_eltiz_data', wp_slash( $data ) );
		update_post_meta( $post_id, '_eltiz_built_with', 'eltiz' );
		update_post_meta( $post_id, '_eltiz_version', ELTIZ_VERSION );

		if ( $settings ) {
			update_post_meta( $post_id, '_eltiz_page_settings', wp_slash( $settings ) );
		}

		// Update post_content for SEO compatibility.
		$text_content = $this->extract_text_content( $decoded );
		wp_update_post( [
			'ID'           => $post_id,
			'post_content' => wp_kses_post( $text_content ),
		] );

		do_action( 'eltiz/page/saved', $post_id, $decoded );

		return new \WP_REST_Response( [ 'success' => true, 'post_id' => $post_id ], 200 );
	}

	// ─── Widgets ──────────────────────────────────────────────────────────────

	public function get_widgets( \WP_REST_Request $request ): \WP_REST_Response {
		$widget_manager = \EltizBuilder\Core\Plugin::instance()->make( 'widgets' );

		return new \WP_REST_Response( [
			'widgets'    => $widget_manager->get_widgets_config(),
			'categories' => $widget_manager->get_categories(),
		], 200 );
	}

	// ─── Templates ────────────────────────────────────────────────────────────

	public function get_templates( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$type   = sanitize_key( $request->get_param( 'type' ) ?? '' );
		$table  = $wpdb->prefix . 'eltiz_templates';
		$where  = $type ? $wpdb->prepare( 'WHERE type = %s AND status = %s', $type, 'publish' ) : "WHERE status = 'publish'";

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT id, title, type, thumbnail, created_at FROM {$table} {$where} ORDER BY created_at DESC", ARRAY_A );

		return new \WP_REST_Response( $rows ?? [], 200 );
	}

	public function get_template( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$id    = (int) $request->get_param( 'id' );
		$table = $wpdb->prefix . 'eltiz_templates';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a hardcoded prefixed constant, not user input.
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

		if ( ! $row ) {
			return new \WP_REST_Response( [ 'message' => __( 'Template not found.', 'eltiz-builder' ) ], 404 );
		}

		$row['content'] = $row['content'] ? json_decode( $row['content'], true ) : [];

		return new \WP_REST_Response( $row, 200 );
	}

	public function save_template( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$title   = sanitize_text_field( $request->get_param( 'title' ) ?? '' );
		$type    = sanitize_key( $request->get_param( 'type' ) ?? 'block' );
		$content = $request->get_param( 'content' );

		if ( empty( $title ) || empty( $content ) ) {
			return new \WP_REST_Response( [ 'message' => __( 'Title and content are required.', 'eltiz-builder' ) ], 400 );
		}

		$decoded = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_REST_Response( [ 'message' => __( 'Invalid content JSON.', 'eltiz-builder' ) ], 400 );
		}

		$inserted = $wpdb->insert( $wpdb->prefix . 'eltiz_templates', [
			'title'     => $title,
			'type'      => $type,
			'content'   => wp_json_encode( $decoded ),
			'status'    => 'publish',
			'author_id' => get_current_user_id(),
		] );

		if ( ! $inserted ) {
			return new \WP_REST_Response( [ 'message' => __( 'Failed to save template.', 'eltiz-builder' ) ], 500 );
		}

		return new \WP_REST_Response( [ 'success' => true, 'id' => $wpdb->insert_id ], 201 );
	}

	public function delete_template( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$id = (int) $request->get_param( 'id' );
		$wpdb->delete( $wpdb->prefix . 'eltiz_templates', [ 'id' => $id ] );

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	// ─── Global styles ────────────────────────────────────────────────────────

	public function get_global_styles( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [
			'colors' => get_option( 'eltiz_global_colors', [] ),
			'fonts'  => get_option( 'eltiz_global_fonts', [] ),
		], 200 );
	}

	public function save_global_styles( \WP_REST_Request $request ): \WP_REST_Response {
		$colors = $request->get_param( 'colors' );
		$fonts  = $request->get_param( 'fonts' );

		if ( is_array( $colors ) ) {
			update_option( 'eltiz_global_colors', $this->sanitize_global_colors( $colors ) );
		}

		if ( is_array( $fonts ) ) {
			update_option( 'eltiz_global_fonts', $this->sanitize_global_fonts( $fonts ) );
		}

		do_action( 'eltiz/global_styles/saved' );

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	// ─── Form submissions ─────────────────────────────────────────────────────

	public function get_form_submissions( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$form_id = sanitize_text_field( $request->get_param( 'form_id' ) ?? '' );
		$status  = sanitize_key( $request->get_param( 'status' ) ?? '' );
		$table   = $wpdb->prefix . 'eltiz_form_submissions';

		$where_clauses = [];
		$values        = [];

		if ( $form_id ) {
			$where_clauses[] = 'form_id = %s';
			$values[]        = $form_id;
		}
		if ( $status ) {
			$where_clauses[] = 'status = %s';
			$values[]        = $status;
		}

		$where = $where_clauses ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';
		$sql   = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 100";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $values ? $wpdb->get_results( $wpdb->prepare( $sql, ...$values ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );

		return new \WP_REST_Response( $rows ?? [], 200 );
	}

	// ─── Revisions ────────────────────────────────────────────────────────────

	public function get_revisions( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$post_id = (int) $request->get_param( 'post_id' );
		$table   = $wpdb->prefix . 'eltiz_revisions';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a hardcoded prefixed constant.
		$rows    = $wpdb->get_results( $wpdb->prepare( "SELECT id, post_id, created_at, author_id FROM {$table} WHERE post_id = %d ORDER BY created_at DESC LIMIT 50", $post_id ), ARRAY_A );

		return new \WP_REST_Response( $rows ?? [], 200 );
	}

	public function create_revision( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$this->store_revision( $post_id );
		return new \WP_REST_Response( [ 'success' => true ], 201 );
	}

	public function restore_revision( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$post_id     = (int) $request->get_param( 'post_id' );
		$revision_id = (int) $request->get_param( 'revision_id' );
		$table       = $wpdb->prefix . 'eltiz_revisions';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a hardcoded prefixed constant.
		$revision = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND post_id = %d", $revision_id, $post_id ) );

		if ( ! $revision ) {
			return new \WP_REST_Response( [ 'message' => __( 'Revision not found.', 'eltiz-builder' ) ], 404 );
		}

		update_post_meta( $post_id, '_eltiz_data', $revision->data );

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	// ─── Helpers ──────────────────────────────────────────────────────────────

	private function store_revision( int $post_id ): void {
		global $wpdb;

		$existing_data = get_post_meta( $post_id, '_eltiz_data', true );
		if ( ! $existing_data ) {
			return;
		}

		$wpdb->insert( $wpdb->prefix . 'eltiz_revisions', [
			'post_id'   => $post_id,
			'data'      => $existing_data,
			'author_id' => get_current_user_id(),
		] );

		// Keep only the last 50 revisions per post.
		$table = $wpdb->prefix . 'eltiz_revisions';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a hardcoded prefixed constant.
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE post_id = %d AND id NOT IN (SELECT id FROM (SELECT id FROM {$table} WHERE post_id = %d ORDER BY created_at DESC LIMIT 50) tmp)",
			$post_id, $post_id
		) );
	}

	private function extract_text_content( array $elements ): string {
		$text = '';
		array_walk_recursive( $elements, function ( $value, $key ) use ( &$text ) {
			if ( in_array( $key, [ 'title', 'text', 'content', 'editor', 'description' ], true ) && is_string( $value ) ) {
				$text .= ' ' . wp_strip_all_tags( $value );
			}
		} );
		return trim( $text );
	}

	private function sanitize_global_colors( array $colors ): array {
		return array_map( function ( $color ) {
			return [
				'id'    => sanitize_key( $color['id'] ?? '' ),
				'title' => sanitize_text_field( $color['title'] ?? '' ),
				'value' => sanitize_hex_color( $color['value'] ?? '' ) ?? '',
			];
		}, $colors );
	}

	private function sanitize_global_fonts( array $fonts ): array {
		return array_map( function ( $font ) {
			return [
				'id'     => sanitize_key( $font['id'] ?? '' ),
				'title'  => sanitize_text_field( $font['title'] ?? '' ),
				'family' => sanitize_text_field( $font['family'] ?? '' ),
			];
		}, $fonts );
	}
}
