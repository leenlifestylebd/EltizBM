<?php
declare( strict_types=1 );
/**
 * Theme builder canvas — replaces the WordPress theme template
 * when a matching Eltiz theme template is found.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$tpl_id = (int) get_query_var( 'eltiz_theme_tpl_id' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'eltiz-theme-canvas' ); ?>>
<?php wp_body_open(); ?>

<div class="eltiz-theme-template">
	<?php
	if ( $tpl_id ) {
		$data     = get_post_meta( $tpl_id, '_eltiz_data', true );
		$elements = $data ? json_decode( $data, true ) : [];
		if ( is_array( $elements ) ) {
			$frontend = \EltizBuilder\Core\Plugin::instance()->make( 'frontend' );
			$frontend->render_elements( $elements );
		}
	}
	?>
</div>

<?php wp_footer(); ?>
</body>
</html>
