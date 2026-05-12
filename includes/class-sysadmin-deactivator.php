<?php
/**
 * Fired during plugin deactivation.
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation logic.
 */
class SysAdmin_Deactivator {

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Reserved for cleanup that should happen on deactivation.
	}
}
