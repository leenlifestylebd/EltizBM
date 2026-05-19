<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions;

abstract class ConditionBase {
	abstract public function get_name(): string;
	abstract public function get_label(): string;
	abstract public function check( array $args = [] ): bool;
}
