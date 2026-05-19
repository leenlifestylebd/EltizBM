<?php
declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Access denied.', 'eltiz-builder' ) );
?>
<div class="wrap eltiz-admin-wrap">
	<h1><?php esc_html_e( 'Eltiz Builder Settings', 'eltiz-builder' ); ?></h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'eltiz_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Performance Mode', 'eltiz-builder' ); ?></th>
				<td>
					<select name="eltiz_performance_mode">
						<?php
						$mode = get_option( 'eltiz_performance_mode', 'standard' );
						$modes = [
							'standard' => __( 'Standard', 'eltiz-builder' ),
							'advanced' => __( 'Advanced (Optimized assets)', 'eltiz-builder' ),
						];
						foreach ( $modes as $val => $label ) :
						?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $mode, $val ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Advanced mode loads only assets needed per page.', 'eltiz-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'CSS Print Method', 'eltiz-builder' ); ?></th>
				<td>
					<select name="eltiz_css_print_method">
						<?php
						$method = get_option( 'eltiz_css_print_method', 'internal' );
						$methods = [
							'internal' => __( 'Internal Embedding', 'eltiz-builder' ),
							'external' => __( 'External CSS File', 'eltiz-builder' ),
						];
						foreach ( $methods as $val => $label ) :
						?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $method, $val ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Google Fonts', 'eltiz-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="eltiz_disable_google_fonts" value="1" <?php checked( 1, get_option( 'eltiz_disable_google_fonts', 0 ) ); ?>>
						<?php esc_html_e( 'Disable loading Google Fonts (for GDPR compliance or offline use)', 'eltiz-builder' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Active Modules', 'eltiz-builder' ); ?></th>
				<td>
					<?php
					$active_modules = (array) get_option( 'eltiz_active_modules', [] );
					$all_modules = [
						'theme-builder'  => __( 'Theme Builder', 'eltiz-builder' ),
						'popup-builder'  => __( 'Popup Builder', 'eltiz-builder' ),
						'forms'          => __( 'Form Builder', 'eltiz-builder' ),
						'woocommerce'    => __( 'WooCommerce Builder', 'eltiz-builder' ),
						'loop-builder'   => __( 'Loop Builder', 'eltiz-builder' ),
						'motion-effects' => __( 'Motion Effects', 'eltiz-builder' ),
						'global-styles'  => __( 'Global Styles', 'eltiz-builder' ),
						'dynamic-tags'   => __( 'Dynamic Tags', 'eltiz-builder' ),
					];
					foreach ( $all_modules as $slug => $label ) :
					?>
					<label style="display:block;margin-bottom:8px;">
						<input type="checkbox" name="eltiz_active_modules[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( 1, $active_modules[ $slug ] ?? 0 ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row" style="color:#c0392b;"><?php esc_html_e( 'Remove Data on Uninstall', 'eltiz-builder' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="eltiz_remove_data_on_uninstall" value="1" <?php checked( 1, get_option( 'eltiz_remove_data_on_uninstall', 0 ) ); ?>>
						<?php esc_html_e( 'Delete all Eltiz Builder data when the plugin is uninstalled', 'eltiz-builder' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
