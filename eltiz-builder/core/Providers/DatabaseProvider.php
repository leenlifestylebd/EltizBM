<?php
declare( strict_types=1 );

namespace EltizBuilder\Core\Providers;

use EltizBuilder\Core\ServiceProvider;

final class DatabaseProvider extends ServiceProvider {

	public function register(): void {
		// Run DB upgrade check on admin.
		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'maybe_upgrade' ] );
		}
	}

	public function maybe_upgrade(): void {
		$saved_version = get_option( 'eltiz_db_version', '0.0.0' );
		if ( version_compare( $saved_version, ELTIZ_VERSION, '<' ) ) {
			\EltizBuilder\Core\Installer::activate();
		}
	}
}
