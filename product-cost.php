<?php
/**
 * Plugin Name: Woocommerce Product Cost
 * Description: Woocommerce addons for enable product cost
 * Author: Funkmo Studio
 * Author URI: http://funkmo.com/downloads/wc-product-cost/
 * Author Email: hello@funkmo.com
 * Version: 1.0
 * Text Domain: funkwoocost
 * Domain Path: /languages/ 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Funk_WooCost' ) ) :

/**
 * Main Funk_WooCost Class
 *
 * @class Funk_WooCost
 * @version	1.0
 */
final class Funk_WooCost {

	/**
	 * @var string
	 */
	public $version = '1.0';

	public $capability = 'manage_options';

	/**
	 * @var Funk_WooCost The single instance of the class
	 * @since 1.0
	 */
	protected static $_instance = null;

	/**
	 * Main Funk_WooCost Instance
	 *
	 * Ensures only one instance of Funk_WooCost is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @return Funk_WooCost - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Funk_WooCost Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		do_action( 'funkwoocost_loaded' );
	}

	/**
	 * Hook into actions and filters
	 * @since  1.0
	 */
	private function init_hooks() {

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'init' ), 0 );

		register_uninstall_hook( __FILE__, 'uninstall' );
	}

	/**
	 * All install stuff
	 * @return [type] [description]
	 */
	public function install() {
	
		do_action( 'on_funkwoocost_install' );
	}

	/**
	 * All uninstall stuff
	 * @return [type] [description]
	 */
	public function uninstall() {

		do_action( 'on_funkwoocost_uninstall' );
	}

	/**
	 * Init Funk_WooCost when WordPress Initialises.
	 */
	public function init() {
		// Before init action
		do_action( 'before_funkwoocost_init' );

		// Init action
		do_action( 'after_funkwoocost_init' );
	}

	/**
	 * Register all scripts to used on our pages
	 * @return [type] [description]
	 */
	public function register_scripts(){

		if ( $this->is_request( 'admin' ) ){
			wp_register_script( 'funkwoocost-admin', plugins_url( '/includes/admin/assets/js/funkwoocost-admin.js', __FILE__ ), array('jquery'), '', true );
		}
 	}

	/**
	 * Define Funk_WooCost Constants
	 */
	private function define_constants() {

		$this->define( 'FUNKWOOCOST_PLUGIN_FILE', __FILE__ );
		$this->define( 'FUNKWOOCOST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'FUNKWOOCOST_VERSION', $this->version );
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 * string $type ajax, frontend or admin
	 * @return bool
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		// all public includes

		if ( $this->is_request( 'admin' ) ) {
			include_once( 'includes/admin/admin.class.php' );
		}

		if ( $this->is_request( 'ajax' ) ) {
			// include_once( 'includes/ajax/..*.php' );
		}

		if ( $this->is_request( 'frontend' ) ) {

		}
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get Ajax URL.
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

}

endif;

/**
 * Notice if woocommerce not activated
 * @return [type] [description]
 */
function funkwoocost_need_woocommerce(){
	?>
    <div class="updated">
        <p><?php _e('Funkmo Woocommerce Product Cost need Woocommerce installed.', 'funkwoocost') ?></p>
    </div>
    <?php
}

/**
 * Returns the main instance of Funk_WooCost to prevent the need to use globals.
 *
 * @since  1.0
 * @return Funk_WooCost
 */
function Funk_WooCost() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return Funk_WooCost::instance();
	} else {
		add_action( 'admin_notices', 'funkwoocost_need_woocommerce' );
	}
}

// Global for backwards compatibility.
Funk_WooCost();