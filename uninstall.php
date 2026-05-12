<?php
/**
 * Uninstall routine for SysAdmin Toolbox.
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sysadmin_toolbox_version' );
delete_site_option( 'sysadmin_toolbox_version' );
