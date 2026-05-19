<?php
declare( strict_types=1 );

namespace EltizBuilder\Conditions;

/**
 * Conditions engine — evaluates include/exclude rules for Theme Builder templates.
 */
final class ConditionsManager {

	private array $conditions = [];

	public function init(): void {
		$this->register_core_conditions();
		do_action( 'eltiz/conditions/register', $this );
	}

	private function register_core_conditions(): void {
		$this->register( new Conditions\SingularCondition() );
		$this->register( new Conditions\ArchiveCondition() );
		$this->register( new Conditions\FrontPageCondition() );
		$this->register( new Conditions\SearchCondition() );
		$this->register( new Conditions\NotFoundCondition() );
		$this->register( new Conditions\UserRoleCondition() );
		$this->register( new Conditions\PostTypeCondition() );
		$this->register( new Conditions\TaxonomyCondition() );
	}

	public function register( ConditionBase $condition ): void {
		$this->conditions[ $condition->get_name() ] = $condition;
	}

	public function get_all(): array {
		return $this->conditions;
	}

	/**
	 * Checks if ALL given conditions are satisfied.
	 *
	 * @param array $conditions [['type'=>'singular','subtype'=>'post',...], ...]
	 */
	public function check( array $conditions ): bool {
		foreach ( $conditions as $condition_config ) {
			$type      = sanitize_key( $condition_config['type'] ?? '' );
			$condition = $this->conditions[ $type ] ?? null;

			if ( ! $condition ) {
				continue;
			}

			if ( ! $condition->check( $condition_config ) ) {
				return false;
			}
		}

		return true;
	}
}
