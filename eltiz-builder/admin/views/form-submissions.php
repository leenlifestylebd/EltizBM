<?php
declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'edit_posts' ) ) wp_die( esc_html__( 'Access denied.', 'eltiz-builder' ) );

global $wpdb;
$table = $wpdb->prefix . 'eltiz_form_submissions';
$submissions = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100", ARRAY_A );
?>
<div class="wrap eltiz-admin-wrap">
	<h1><?php esc_html_e( 'Form Submissions', 'eltiz-builder' ); ?></h1>

	<?php if ( empty( $submissions ) ) : ?>
		<p><?php esc_html_e( 'No form submissions found.', 'eltiz-builder' ); ?></p>
	<?php else : ?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( '#', 'eltiz-builder' ); ?></th>
				<th><?php esc_html_e( 'Form', 'eltiz-builder' ); ?></th>
				<th><?php esc_html_e( 'Page', 'eltiz-builder' ); ?></th>
				<th><?php esc_html_e( 'Status', 'eltiz-builder' ); ?></th>
				<th><?php esc_html_e( 'Date', 'eltiz-builder' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'eltiz-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $submissions as $sub ) :
				$data  = json_decode( $sub['data'] ?? '{}', true );
				$page  = get_the_title( $sub['post_id'] ?? 0 );
			?>
			<tr>
				<td><?php echo absint( $sub['id'] ); ?></td>
				<td><?php echo esc_html( $sub['form_id'] ?? '' ); ?></td>
				<td><?php echo esc_html( $page ?: '#' . $sub['post_id'] ); ?></td>
				<td><span class="eltiz-badge eltiz-badge--<?php echo esc_attr( $sub['status'] ?? 'unread' ); ?>"><?php echo esc_html( $sub['status'] ?? 'unread' ); ?></span></td>
				<td><?php echo esc_html( $sub['created_at'] ?? '' ); ?></td>
				<td>
					<button type="button" class="button button-small eltiz-view-submission"
						data-data="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
						<?php esc_html_e( 'View', 'eltiz-builder' ); ?>
					</button>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
