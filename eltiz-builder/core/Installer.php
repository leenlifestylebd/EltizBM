<?php
declare( strict_types=1 );

namespace EltizBuilder\Core;

/**
 * Handles activation, deactivation, and uninstall routines.
 */
final class Installer {

	private const DB_VERSION_OPTION = 'eltiz_db_version';
	private const DB_VERSION        = '1.0.0';

	public static function activate(): void {
		self::create_tables();
		self::set_defaults();
		self::maybe_redirect();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function uninstall(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			exit;
		}

		if ( get_option( 'eltiz_remove_data_on_uninstall' ) !== '1' ) {
			return;
		}

		self::drop_tables();
		self::delete_options();
	}

	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix . 'eltiz_';

		$sql = [];

		// Templates table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}templates (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title       VARCHAR(255)        NOT NULL DEFAULT '',
			type        VARCHAR(100)        NOT NULL DEFAULT 'page',
			status      VARCHAR(20)         NOT NULL DEFAULT 'publish',
			content     LONGTEXT,
			settings    LONGTEXT,
			thumbnail   VARCHAR(255)        DEFAULT NULL,
			author_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY status (status),
			KEY author_id (author_id)
		) {$charset_collate};";

		// Revisions table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}revisions (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			data        LONGTEXT,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			author_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY post_id (post_id)
		) {$charset_collate};";

		// Form submissions table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}form_submissions (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id     VARCHAR(100)        NOT NULL DEFAULT '',
			post_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			data        LONGTEXT,
			referer     VARCHAR(512)        DEFAULT NULL,
			ip_address  VARCHAR(45)         DEFAULT NULL,
			user_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			status      VARCHAR(20)         NOT NULL DEFAULT 'unread',
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY post_id (post_id),
			KEY status (status)
		) {$charset_collate};";

		// Global styles table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}global_styles (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name        VARCHAR(255)        NOT NULL DEFAULT '',
			type        VARCHAR(50)         NOT NULL DEFAULT 'color',
			value       TEXT,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	private static function drop_tables(): void {
		global $wpdb;
		$prefix = $wpdb->prefix . 'eltiz_';
		$tables = [ 'templates', 'revisions', 'form_submissions', 'global_styles' ];
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" );
		}
	}

	private static function set_defaults(): void {
		$defaults = [
			'eltiz_version'                => ELTIZ_VERSION,
			'eltiz_active_modules'         => self::default_modules(),
			'eltiz_performance_mode'       => 'standard',
			'eltiz_global_colors'          => [],
			'eltiz_global_fonts'           => [],
			'eltiz_remove_data_on_uninstall' => '0',
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
	}

	private static function delete_options(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'eltiz_%'" );
	}

	private static function maybe_redirect(): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			set_transient( 'eltiz_activation_redirect', true, 30 );
		}
	}

	private static function default_modules(): array {
		return [
			'theme-builder'   => true,
			'popup-builder'   => true,
			'forms'           => true,
			'woocommerce'     => true,
			'loop-builder'    => true,
			'motion-effects'  => true,
			'global-styles'   => true,
			'dynamic-tags'    => true,
		];
	}
}
