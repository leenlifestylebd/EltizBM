<?php
declare( strict_types=1 );

namespace EltizBuilder\Forms;

/**
 * Form Builder — handles form rendering, AJAX submission, and actions.
 */
final class FormManager {

	private array $actions = [];
	private array $fields   = [];

	public function init(): void {
		$this->register_default_actions();
		$this->register_default_fields();

		add_action( 'wp_ajax_eltiz_form_submit',        [ $this, 'handle_submit' ] );
		add_action( 'wp_ajax_nopriv_eltiz_form_submit', [ $this, 'handle_submit' ] );

		do_action( 'eltiz/forms/init', $this );
	}

	private function register_default_actions(): void {
		$this->register_action( new Actions\EmailAction() );
		$this->register_action( new Actions\RedirectAction() );
		$this->register_action( new Actions\WebhookAction() );
	}

	private function register_default_fields(): void {
		$this->fields = [
			'text'     => __( 'Text', 'eltiz-builder' ),
			'email'    => __( 'Email', 'eltiz-builder' ),
			'phone'    => __( 'Phone', 'eltiz-builder' ),
			'number'   => __( 'Number', 'eltiz-builder' ),
			'textarea' => __( 'Textarea', 'eltiz-builder' ),
			'select'   => __( 'Select', 'eltiz-builder' ),
			'checkbox' => __( 'Checkbox', 'eltiz-builder' ),
			'radio'    => __( 'Radio', 'eltiz-builder' ),
			'date'     => __( 'Date', 'eltiz-builder' ),
			'file'     => __( 'File Upload', 'eltiz-builder' ),
			'hidden'   => __( 'Hidden', 'eltiz-builder' ),
			'password' => __( 'Password', 'eltiz-builder' ),
			'acceptance' => __( 'Acceptance', 'eltiz-builder' ),
		];
	}

	public function register_action( ActionBase $action ): void {
		$this->actions[ $action->get_type() ] = $action;
	}

	public function get_action( string $type ): ?ActionBase {
		return $this->actions[ $type ] ?? null;
	}

	public function get_fields(): array {
		return $this->fields;
	}

	public function handle_submit(): void {
		// Verify nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'eltiz_form' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'eltiz-builder' ) ], 403 );
		}

		$form_id = sanitize_key( wp_unslash( $_POST['form_id'] ?? '' ) );
		$post_id = absint( $_POST['post_id'] ?? 0 );

		if ( ! $form_id || ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid form submission.', 'eltiz-builder' ) ], 400 );
		}

		// Get form widget settings.
		$form_settings = $this->get_form_settings( $form_id, $post_id );

		if ( ! $form_settings ) {
			wp_send_json_error( [ 'message' => __( 'Form not found.', 'eltiz-builder' ) ], 404 );
		}

		// Validate fields.
		$errors = $this->validate_fields( $_POST, $form_settings['fields'] ?? [] );

		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'errors' => $errors ], 422 );
		}

		// Honeypot check.
		if ( ! empty( $_POST['_hp_name'] ) ) {
			wp_send_json_success( [ 'message' => $form_settings['success_message'] ?? __( 'Message sent.', 'eltiz-builder' ) ] );
		}

		// Sanitize and collect submission data.
		$submission_data = $this->collect_submission_data( $_POST, $form_settings['fields'] ?? [] );

		// Store submission.
		$this->store_submission( $form_id, $post_id, $submission_data );

		// Run configured actions.
		foreach ( ( $form_settings['submit_actions'] ?? [] ) as $action_type ) {
			$action = $this->get_action( sanitize_key( $action_type ) );
			if ( $action ) {
				$action->run( $form_settings, $submission_data, $post_id );
			}
		}

		wp_send_json_success( [
			'message'      => sanitize_text_field( $form_settings['success_message'] ?? __( 'Message sent successfully.', 'eltiz-builder' ) ),
			'redirect_url' => esc_url( $form_settings['redirect_url'] ?? '' ),
		] );
	}

	private function validate_fields( array $data, array $fields ): array {
		$errors = [];

		foreach ( $fields as $field ) {
			$id       = $field['custom_id'] ?? $field['_id'] ?? '';
			$required = 'yes' === ( $field['required'] ?? '' );
			$type     = $field['field_type'] ?? 'text';
			$value    = $data[ $id ] ?? '';

			if ( $required && '' === trim( (string) $value ) ) {
				$errors[ $id ] = sprintf( __( '%s is required.', 'eltiz-builder' ), $field['field_label'] ?? $id );
			}

			if ( 'email' === $type && $value && ! is_email( $value ) ) {
				$errors[ $id ] = __( 'Please enter a valid email address.', 'eltiz-builder' );
			}
		}

		return $errors;
	}

	private function collect_submission_data( array $data, array $fields ): array {
		$result = [];

		foreach ( $fields as $field ) {
			$id    = $field['custom_id'] ?? $field['_id'] ?? '';
			$type  = $field['field_type'] ?? 'text';
			$value = $data[ $id ] ?? '';

			$result[ $id ] = match ( $type ) {
				'email'  => sanitize_email( (string) $value ),
				'url'    => esc_url_raw( (string) $value ),
				'number' => is_numeric( $value ) ? (float) $value : 0,
				default  => sanitize_textarea_field( (string) $value ),
			};
		}

		return $result;
	}

	private function store_submission( string $form_id, int $post_id, array $data ): void {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'eltiz_form_submissions', [
			'form_id'    => $form_id,
			'post_id'    => $post_id,
			'data'       => wp_json_encode( $data ),
			'referer'    => esc_url_raw( wp_get_referer() ?: '' ),
			'ip_address' => $this->get_user_ip(),
			'user_id'    => get_current_user_id(),
			'status'     => 'unread',
		] );
	}

	private function get_form_settings( string $form_id, int $post_id ): ?array {
		$raw = get_post_meta( $post_id, '_eltiz_data', true );
		if ( ! $raw ) return null;

		$data = json_decode( $raw, true );
		return $this->find_form_widget( $data ?? [], $form_id );
	}

	private function find_form_widget( array $elements, string $form_id ): ?array {
		foreach ( $elements as $element ) {
			if ( 'widget' === ( $element['type'] ?? '' )
				&& 'form' === ( $element['widgetType'] ?? '' )
				&& ( $element['settings']['form_id'] ?? '' ) === $form_id ) {
				return $element['settings'];
			}
			if ( ! empty( $element['children'] ) ) {
				$found = $this->find_form_widget( $element['children'], $form_id );
				if ( $found ) return $found;
			}
		}
		return null;
	}

	private function get_user_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';

		foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' ] as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( explode( ',', wp_unslash( $_SERVER[ $header ] ) )[0] );
				break;
			}
		}

		return sanitize_text_field( $ip );
	}
}
