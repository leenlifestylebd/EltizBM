<?php
declare( strict_types=1 );

namespace EltizBuilder\Editor;

use EltizBuilder\Core\Plugin;

/**
 * Manages the visual editor page integration.
 */
final class Editor {

	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_menu',            [ $this, 'add_editor_page' ] );
		add_filter( 'post_row_actions',      [ $this, 'add_edit_link' ], 10, 2 );
		add_filter( 'page_row_actions',      [ $this, 'add_edit_link' ], 10, 2 );
		add_action( 'admin_notices',         [ $this, 'activation_redirect' ] );
	}

	public function add_editor_page(): void {
		add_submenu_page(
			'',
			__( 'Eltiz Builder Editor', 'eltiz-builder' ),
			__( 'Edit with Eltiz', 'eltiz-builder' ),
			'edit_posts',
			'eltiz-editor',
			[ $this, 'render_editor' ]
		);
	}

	public function render_editor(): void {
		$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to edit this post.', 'eltiz-builder' ) );
		}

		// Full-screen editor — render the React app root.
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Eltiz Builder', 'eltiz-builder' ); ?></title>
			<?php do_action( 'eltiz/editor/head', $post_id ); ?>
		</head>
		<body class="eltiz-editor-body" data-post-id="<?php echo $post_id; ?>">
			<div id="eltiz-editor-root"></div>
			<?php
			wp_enqueue_scripts();
			do_action( 'admin_print_scripts' );
			do_action( 'admin_print_styles' );
			do_action( 'eltiz/editor/footer', $post_id );
			?>
		</body>
		</html>
		<?php
		exit;
	}

	public function enqueue_editor_assets( string $hook ): void {
		if ( 'admin_page_eltiz-editor' !== $hook ) {
			return;
		}

		// Google Fonts for editor UI.
		wp_enqueue_style(
			'eltiz-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			[],
			null
		);

		// Font Awesome.
		wp_enqueue_style(
			'font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
			[],
			'6.5.0'
		);

		// Editor CSS.
		wp_enqueue_style(
			'eltiz-editor',
			ELTIZ_ASSETS_URL . 'css/editor.min.css',
			[],
			ELTIZ_VERSION
		);

		// Editor React app.
		wp_enqueue_script(
			'eltiz-editor',
			ELTIZ_ASSETS_URL . 'js/editor.min.js',
			[ 'wp-element', 'wp-api-fetch', 'wp-i18n', 'underscore', 'backbone' ],
			ELTIZ_VERSION,
			true
		);

		$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post    = get_post( $post_id );

		wp_localize_script( 'eltiz-editor', 'eltizEditorConfig', [
			'postId'        => $post_id,
			'postType'      => $post ? $post->post_type : 'post',
			'postTitle'     => $post ? $post->post_title : '',
			'homeUrl'       => home_url(),
			'siteUrl'       => site_url(),
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'restUrl'       => rest_url( 'eltiz/v1/' ),
			'nonce'         => wp_create_nonce( 'eltiz_editor' ),
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'version'       => ELTIZ_VERSION,
			'assetsUrl'     => ELTIZ_ASSETS_URL,
			'isRtl'         => is_rtl(),
			'breakpoints'   => $this->get_breakpoints(),
			'postTypes'     => $this->get_post_types(),
			'currentUser'   => [
				'id'     => get_current_user_id(),
				'name'   => wp_get_current_user()->display_name,
				'avatar' => get_avatar_url( get_current_user_id() ),
			],
		] );

		wp_set_script_translations( 'eltiz-editor', 'eltiz-builder', ELTIZ_PATH . 'languages/' );
	}

	public function add_edit_link( array $actions, \WP_Post $post ): array {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = admin_url( 'admin.php?page=eltiz-editor&post=' . $post->ID );

		$actions['eltiz_edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Edit with Eltiz', 'eltiz-builder' )
		);

		return $actions;
	}

	public function activation_redirect(): void {
		if ( ! get_transient( 'eltiz_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'eltiz_activation_redirect' );

		if ( ! is_multisite() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=eltiz-dashboard' ) );
			exit;
		}
	}

	private function get_breakpoints(): array {
		return [
			'desktop' => [ 'label' => 'Desktop', 'width' => 1025, 'icon' => 'desktop' ],
			'tablet'  => [ 'label' => 'Tablet',  'width' => 768,  'icon' => 'tablet' ],
			'mobile'  => [ 'label' => 'Mobile',  'width' => 375,  'icon' => 'mobile' ],
		];
	}

	private function get_post_types(): array {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$result     = [];

		foreach ( $post_types as $type ) {
			$result[ $type->name ] = $type->labels->singular_name;
		}

		return $result;
	}
}
