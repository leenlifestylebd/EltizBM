<?php
declare( strict_types=1 );

namespace EltizBuilder\Forms;

abstract class ActionBase {
	abstract public function get_type(): string;
	abstract public function get_label(): string;
	abstract public function run( array $form_settings, array $submission_data, int $post_id ): void;
}
