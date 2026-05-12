<?php
/**
 * Core plugin class.
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class SysAdmin {

	/**
	 * Loader instance.
	 *
	 * @var SysAdmin_Loader
	 */
	protected $loader;

	/**
	 * Unique plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Initialize core plugin class.
	 */
	public function __construct() {
		$this->plugin_name = 'sysadmin';
		$this->version     = SYSADMIN_TOOLBOX_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load required dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once SYSADMIN_TOOLBOX_PLUGIN_DIR . 'includes/class-sysadmin-loader.php';
		require_once SYSADMIN_TOOLBOX_PLUGIN_DIR . 'includes/class-sysadmin-google-codes.php';
		require_once SYSADMIN_TOOLBOX_PLUGIN_DIR . 'admin/class-sysadmin-admin.php';
		require_once SYSADMIN_TOOLBOX_PLUGIN_DIR . 'public/class-sysadmin-public.php';

		$this->loader = new SysAdmin_Loader();
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new SysAdmin_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'register_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_post_actions' );
	}

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		$plugin_public = new SysAdmin_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run loader.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Plugin name accessor.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Loader accessor.
	 *
	 * @return SysAdmin_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Version accessor.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
