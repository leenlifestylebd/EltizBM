<?php
declare( strict_types=1 );

namespace EltizBuilder\Widgets;

final class LoginFormWidget extends WidgetBase {
	public function get_name(): string  { return 'login-form'; }
	public function get_title(): string { return __( 'Login Form', 'eltiz-builder' ); }
	public function get_icon(): string  { return 'eicon-lock-user'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'section_login', [ 'label' => __( 'Login Form', 'eltiz-builder' ) ] );
		$this->add_control( 'show_register', [ 'label'=>__('Show Register Link','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->add_control( 'show_lost_password', [ 'label'=>__('Show Lost Password','eltiz-builder'),'type'=>'switcher','default'=>'yes' ] );
		$this->end_controls_section();
	}

	protected function render(): void {
		if ( is_user_logged_in() ) {
			echo '<p class="eltiz-login-logged-in">' . sprintf( esc_html__( 'You are logged in as %s.', 'eltiz-builder' ), esc_html( wp_get_current_user()->display_name ) ) . '</p>';
			return;
		}

		$s = $this->get_settings_for_display();

		wp_login_form( [
			'echo'     => true,
			'redirect' => esc_url( home_url() ),
		] );

		if ( 'yes' === ( $s['show_register'] ?? 'yes' ) && get_option('users_can_register') ) {
			echo '<p class="eltiz-login-register"><a href="' . esc_url( wp_registration_url() ) . '">' . esc_html__( 'Register', 'eltiz-builder' ) . '</a></p>';
		}

		if ( 'yes' === ( $s['show_lost_password'] ?? 'yes' ) ) {
			echo '<p class="eltiz-login-lost-password"><a href="' . esc_url( wp_lostpassword_url() ) . '">' . esc_html__( 'Lost your password?', 'eltiz-builder' ) . '</a></p>';
		}
	}
}
