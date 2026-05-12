<?php
/**
 * Plugin Name:       SysAdmin Toolbox
 * Plugin URI:        https://nero.local/plugins/sysadmin
 * Description:       SysAdmin toolbox voor scholen: beheer-, onderhoud- en operationele tools voor schoolomgevingen.
 * Version:           0.1.0
 * Author:            SysAdmin Team
 * Author URI:        https://nero.local
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sysadmin
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SYSADMIN_TOOLBOX_VERSION', '0.1.0' );
define( 'SYSADMIN_TOOLBOX_PLUGIN_FILE', __FILE__ );
define( 'SYSADMIN_TOOLBOX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SYSADMIN_TOOLBOX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Fired during plugin activation.
 *
 * @return void
 */
function sysadmin_activate_toolbox() {
	require_once SYSADMIN_TOOLBOX_PLUGIN_DIR . 'includes/class-sysadmin-activator.php';
	SysAdmin_Activator::activate();
}

/**
 * Fired during plugin deactivation.
 *
 * @return void
 */
function sysadmin_deactivate_toolbox() {
	require_once SYSADMIN_TOOLBOX_PLUGIN_DIR . 'includes/class-sysadmin-deactivator.php';
	SysAdmin_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'sysadmin_activate_toolbox' );
register_deactivation_hook( __FILE__, 'sysadmin_deactivate_toolbox' );

require_once SYSADMIN_TOOLBOX_PLUGIN_DIR . 'includes/class-sysadmin.php';

/**
 * Begins execution of the plugin.
 *
 * @return void
 */
function sysadmin_run_toolbox() {
	$plugin = new SysAdmin();
	$plugin->run();
}

sysadmin_run_toolbox();
