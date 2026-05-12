<?php
/**
 * Register all actions and filters for the plugin.
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin loader.
 */
class SysAdmin_Loader {

	/**
	 * The array of actions.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $actions;

	/**
	 * The array of filters.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $filters;

	/**
	 * Initialize collections.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add a new action.
	 *
	 * @param string $hook           Hook name.
	 * @param object $component      Class instance.
	 * @param string $callback       Callback method.
	 * @param int    $priority       Hook priority.
	 * @param int    $accepted_args  Number of accepted arguments.
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter.
	 *
	 * @param string $hook           Hook name.
	 * @param object $component      Class instance.
	 * @param string $callback       Callback method.
	 * @param int    $priority       Hook priority.
	 * @param int    $accepted_args  Number of accepted arguments.
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Utility for storing hook registrations.
	 *
	 * @param array<int, array<string, mixed>> $hooks         Existing hooks.
	 * @param string                           $hook          Hook name.
	 * @param object                           $component     Class instance.
	 * @param string                           $callback      Callback method.
	 * @param int                              $priority      Hook priority.
	 * @param int                              $accepted_args Number of accepted arguments.
	 * @return array<int, array<string, mixed>>
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
