<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class FormWidget extends WidgetBase {

	public function get_name(): string  { return 'form'; }
	public function get_title(): string { return __( 'Form', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-form-horizontal'; }
	public function get_categories(): array { return [ 'forms' ]; }
	public function get_keywords(): array { return [ 'form', 'contact', 'email', 'submit' ]; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_form', [ 'label' => __( 'Form', 'eltiz-builder' ) ] );

		$this->add_control( 'form_name', [ 'label'=>__('Form Name','eltiz-builder'),'type'=>'text','default'=>__('Contact Form','eltiz-builder') ] );

		$this->add_control( 'form_fields', [
			'label'  => __( 'Fields', 'eltiz-builder' ),
			'type'   => 'repeater',
			'fields' => [
				[ 'name'=>'field_type',    'label'=>__('Type','eltiz-builder'),     'type'=>'select','options'=>['text'=>'Text','email'=>'Email','textarea'=>'Textarea','tel'=>'Phone','number'=>'Number','select'=>'Select','checkbox'=>'Checkbox','radio'=>'Radio','file'=>'File','date'=>'Date','password'=>'Password','hidden'=>'Hidden','acceptance'=>'Acceptance'],'default'=>'text' ],
				[ 'name'=>'field_label',   'label'=>__('Label','eltiz-builder'),    'type'=>'text','default'=>__('Name','eltiz-builder') ],
				[ 'name'=>'placeholder',   'label'=>__('Placeholder','eltiz-builder'),'type'=>'text' ],
				[ 'name'=>'required',      'label'=>__('Required','eltiz-builder'), 'type'=>'switcher','default'=>'' ],
				[ 'name'=>'field_options', 'label'=>__('Options (one per line)','eltiz-builder'),'type'=>'textarea','condition'=>['field_type'=>['select','checkbox','radio']] ],
				[ 'name'=>'custom_id',     'label'=>__('Field ID','eltiz-builder'), 'type'=>'text' ],
			],
			'default' => [
				[ 'field_type'=>'text',  'field_label'=>__('Name','eltiz-builder'),    'placeholder'=>__('Your Name','eltiz-builder'),  'required'=>'yes' ],
				[ 'field_type'=>'email', 'field_label'=>__('Email','eltiz-builder'),   'placeholder'=>__('Your Email','eltiz-builder'), 'required'=>'yes' ],
				[ 'field_type'=>'textarea','field_label'=>__('Message','eltiz-builder'),'placeholder'=>__('Your Message','eltiz-builder') ],
			],
			'title_field' => '{{{ field_label }}}',
		] );

		$this->add_control( 'submit_button_text', [ 'label'=>__('Button Text','eltiz-builder'),'type'=>'text','default'=>__('Send','eltiz-builder') ] );
		$this->add_control( 'success_message', [ 'label'=>__('Success Message','eltiz-builder'),'type'=>'text','default'=>__('Your message has been sent.','eltiz-builder') ] );
		$this->add_control( 'submit_actions', [ 'label'=>__('Actions After Submit','eltiz-builder'),'type'=>'select','options'=>['email'=>'Email','redirect'=>'Redirect','webhook'=>'Webhook'],'default'=>'email' ] );
		$this->add_control( 'email_to', [ 'label'=>__('Send To','eltiz-builder'),'type'=>'text','default'=>get_option('admin_email') ] );
		$this->add_control( 'email_subject', [ 'label'=>__('Email Subject','eltiz-builder'),'type'=>'text','default'=>__('New Message','eltiz-builder') ] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$fields   = $settings['form_fields'] ?? [];
		$form_id  = 'form-' . uniqid();
		$post_id  = get_the_ID();

		echo '<div class="eltiz-form-container">';
		echo '<form class="eltiz-form" id="' . esc_attr( $form_id ) . '" data-form-id="' . esc_attr( $form_id ) . '" novalidate>';

		wp_nonce_field( 'eltiz_form', '_nonce' );
		echo '<input type="hidden" name="action" value="eltiz_form_submit">';
		echo '<input type="hidden" name="form_id" value="' . esc_attr( $form_id ) . '">';
		echo '<input type="hidden" name="post_id" value="' . absint( $post_id ) . '">';

		// Honeypot.
		echo '<div style="position:absolute;left:-9999px;opacity:0;" aria-hidden="true">';
		echo '<input type="text" name="_hp_name" tabindex="-1" autocomplete="new-password">';
		echo '</div>';

		foreach ( $fields as $field ) {
			$this->render_field( $field );
		}

		echo '<div class="eltiz-form-field eltiz-form-type-submit">';
		echo '<button type="submit" class="eltiz-button eltiz-form-submit">';
		echo esc_html( $settings['submit_button_text'] ?? __( 'Send', 'eltiz-builder' ) );
		echo '</button>';
		echo '</div>';

		echo '<div class="eltiz-message eltiz-message--success" style="display:none;" role="alert">';
		echo esc_html( $settings['success_message'] ?? '' );
		echo '</div>';

		echo '<div class="eltiz-message eltiz-message--error" style="display:none;" role="alert"></div>';

		echo '</form>';
		echo '</div>';
	}

	private function render_field( array $field ): void {
		$type  = $field['field_type'] ?? 'text';
		$label = $field['field_label'] ?? '';
		$id    = $field['custom_id'] ?? ( 'field-' . sanitize_title( $label ) );
		$req   = 'yes' === ( $field['required'] ?? '' );
		$placeholder = $field['placeholder'] ?? '';

		echo '<div class="eltiz-form-field eltiz-form-type-' . esc_attr( $type ) . '">';

		if ( ! in_array( $type, [ 'hidden', 'acceptance' ], true ) ) {
			echo '<label class="eltiz-form-label" for="' . esc_attr( $id ) . '">';
			echo esc_html( $label );
			if ( $req ) echo ' <span class="eltiz-form-required" aria-hidden="true">*</span>';
			echo '</label>';
		}

		switch ( $type ) {
			case 'textarea':
				echo '<textarea class="eltiz-form-input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="5"' . ( $req ? ' required' : '' ) . ' aria-label="' . esc_attr( $label ) . '"></textarea>';
				break;

			case 'select':
				echo '<select class="eltiz-form-input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '"' . ( $req ? ' required' : '' ) . ' aria-label="' . esc_attr( $label ) . '">';
				echo '<option value="">' . esc_html( $placeholder ?: __( 'Select...', 'eltiz-builder' ) ) . '</option>';
				foreach ( explode( "\n", $field['field_options'] ?? '' ) as $option ) {
					$option = trim( $option );
					if ( $option ) {
						echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
					}
				}
				echo '</select>';
				break;

			case 'checkbox':
			case 'radio':
				foreach ( explode( "\n", $field['field_options'] ?? '' ) as $option ) {
					$option = trim( $option );
					if ( $option ) {
						echo '<label class="eltiz-form-option-label">';
						echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $id ) . ( 'checkbox' === $type ? '[]' : '' ) . '" value="' . esc_attr( $option ) . '"' . ( $req ? ' required' : '' ) . '> ';
						echo esc_html( $option );
						echo '</label>';
					}
				}
				break;

			case 'acceptance':
				echo '<label class="eltiz-form-option-label">';
				echo '<input type="checkbox" name="' . esc_attr( $id ) . '" value="1"' . ( $req ? ' required' : '' ) . '> ';
				echo esc_html( $label );
				echo '</label>';
				break;

			case 'file':
				echo '<input type="file" class="eltiz-form-input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '"' . ( $req ? ' required' : '' ) . ' aria-label="' . esc_attr( $label ) . '">';
				break;

			case 'hidden':
				echo '<input type="hidden" name="' . esc_attr( $id ) . '" value="' . esc_attr( $field['default_value'] ?? '' ) . '">';
				break;

			default:
				$html_type = in_array( $type, [ 'text','email','tel','number','date','password' ], true ) ? $type : 'text';
				echo '<input type="' . esc_attr( $html_type ) . '" class="eltiz-form-input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $placeholder ) . '"' . ( $req ? ' required' : '' ) . ' aria-label="' . esc_attr( $label ) . '">';
		}

		echo '<div class="eltiz-form-field-error" role="alert" aria-live="polite"></div>';
		echo '</div>';
	}
}
