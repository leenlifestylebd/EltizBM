<?php
declare( strict_types=1 );

namespace EltizBuilder\Forms\Actions;

use EltizBuilder\Forms\ActionBase;

final class RedirectAction extends ActionBase {
	public function get_type(): string  { return 'redirect'; }
	public function get_label(): string { return __( 'Redirect', 'eltiz-builder' ); }
	public function run( array $settings, array $data, int $post_id ): void {
		// Redirect URL is returned to the JS handler via the AJAX response.
		// Nothing needed server-side beyond what FormManager already passes.
	}
}
