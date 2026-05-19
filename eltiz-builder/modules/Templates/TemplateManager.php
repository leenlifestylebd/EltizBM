<?php
declare( strict_types=1 );

namespace EltizBuilder\Modules\Templates;

/**
 * Template manager — save, load, export, import template blocks.
 */
final class TemplateManager {

	public function init(): void {
		add_action( 'wp_ajax_eltiz_export_template', [ $this, 'export_template' ] );
		add_action( 'wp_ajax_eltiz_import_template', [ $this, 'import_template' ] );
		do_action( 'eltiz/templates/init', $this );
	}

	public function export_template(): void {
		check_ajax_referer( 'eltiz_editor', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'eltiz-builder' ) );
		}

		$id = absint( $_POST['template_id'] ?? 0 );

		global $wpdb;
		$template = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}eltiz_templates WHERE id = %d",
			$id
		), ARRAY_A );

		if ( ! $template ) {
			wp_die( esc_html__( 'Template not found.', 'eltiz-builder' ) );
		}

		$export = [
			'title'   => $template['title'],
			'type'    => $template['type'],
			'content' => json_decode( $template['content'] ?? '[]', true ),
			'version' => ELTIZ_VERSION,
		];

		$filename = sanitize_file_name( $template['title'] ) . '-eltiz-template.json';

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $export, JSON_PRETTY_PRINT );
		exit;
	}

	public function import_template(): void {
		check_ajax_referer( 'eltiz_editor', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'eltiz-builder' ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( __( 'No file uploaded.', 'eltiz-builder' ) );
		}

		$file = $_FILES['file'];

		if ( ! in_array( $file['type'], [ 'application/json', 'text/plain' ], true ) ) {
			wp_send_json_error( __( 'Invalid file type. Please upload a JSON file.', 'eltiz-builder' ) );
		}

		$content = file_get_contents( $file['tmp_name'] );
		$data    = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $data['content'] ) ) {
			wp_send_json_error( __( 'Invalid template file.', 'eltiz-builder' ) );
		}

		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'eltiz_templates', [
			'title'     => sanitize_text_field( $data['title'] ?? 'Imported Template' ),
			'type'      => sanitize_key( $data['type'] ?? 'block' ),
			'content'   => wp_json_encode( $data['content'] ),
			'status'    => 'publish',
			'author_id' => get_current_user_id(),
		] );

		wp_send_json_success( [
			'id'    => $wpdb->insert_id,
			'title' => $data['title'] ?? 'Imported Template',
		] );
	}

	public function get_template( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}eltiz_templates WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $row ) return null;

		$row['content'] = json_decode( $row['content'] ?? '[]', true );
		return $row;
	}

	public function get_templates( string $type = '' ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'eltiz_templates';
		$where = $type ? $wpdb->prepare( 'WHERE type = %s AND status = %s', $type, 'publish' ) : "WHERE status = 'publish'";

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT id, title, type, thumbnail, created_at FROM {$table} {$where} ORDER BY created_at DESC", ARRAY_A ) ?? [];
	}
}
