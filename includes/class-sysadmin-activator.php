<?php
/**
 * Fired during plugin activation.
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation logic.
 */
class SysAdmin_Activator {

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		add_option( 'sysadmin_toolbox_version', SYSADMIN_TOOLBOX_VERSION );
	}
}
