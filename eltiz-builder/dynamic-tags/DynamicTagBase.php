<?php
declare( strict_types=1 );

namespace EltizBuilder\DynamicTags;

abstract class DynamicTagBase {
	abstract public function get_name(): string;
	abstract public function get_label(): string;
	abstract public function get_value( array $args = [] ): mixed;
	public function get_group(): string { return 'post'; }
	public function get_args_config(): array { return []; }
}
