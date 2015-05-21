<?php
/**
 * Plugin Name: WooCommerce Last Purchased
 * Plugin URI: http://wordpress.org/plugins/woocommerce-last-purchased/
 * Description: This is a WooCommerce extension to show last purchased date popup on product page.
 * Author: Dawid Urbański
 * Version: 1.0
 * Author URI: http://dawidurbanski.com/
 *
 * Text Domain: wlp
 * Domain Path: /languages
 *
 * @package WooCommerce_Last_Purchased
 *
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'Woocommerce_Last_Purchased' ) ) :

class Woocommerce_Last_Purchased{

    /**
     * @var Woocommerce_Last_Purchased The single instance of the class
     */
    protected static $_instance = null;

    private $_woocommerce_directory = 'woocommerce/woocommerce.php';

	private $_timeago_locale_file = null;
	private $_timeago_locale_file_url = null;

	public $hide_popup = false;

	/**
	 * Add necessary actions when object is initialised
	 */
	public function __construct(){

		$this->init_hooks();

		$this->_timeago_locale_file_url =  plugin_dir_url( __FILE__ ) . 'vendor/timeago/locales/jquery.timeago.' . $this->get_language() . '.js';
		$this->_timeago_locale_file =  plugin_dir_path( __FILE__ ) . 'vendor/timeago/locales/jquery.timeago.' . $this->get_language() . '.js';

    }

	private function init_hooks(){

		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ));
		add_action( 'wp_footer', array( $this, 'add_post_meta' ) );
		if( get_option( 'show_wlp_popup' ) == '1' ){
			add_action( 'wp_footer', array( $this, 'show_popup' ));
			add_action( 'wp_enqueue_scripts', array( $this, 'load_styles' ));
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ));
		}

	}

	public function init(){

		do_action( 'before_wlp_init' );

	}

	/**
	 * Main WooCommerce_Last_Purchased Instance
	 *
	 * Ensures only one instance of WooCommerce Last Purchased
	 * is loaded or can be loaded.
	 *
	 * @see WLP()
	 * @return Woocommerce_Last_Purchased
	 */
	public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	/**
	 * Load all styles
	 */
	public function load_styles(){

        wp_enqueue_style( 'wlp_style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), false );

    }

	/**
	 * Load all scripts required by plugin
	 */
	public function load_scripts(){

        wp_register_script( 'timeago', plugin_dir_url( __FILE__ ) . 'vendor/timeago/jquery.timeago.js', array( 'jquery' ), false, true );
        wp_enqueue_script( 'scripts', plugin_dir_url( __FILE__ ) . 'assets/js/scripts.js', array( 'timeago' ), false, true );

        if ( file_exists( $this->_timeago_locale_file ) ){
            wp_enqueue_script( 'timeago_locale', $this->_timeago_locale_file_url, array( 'timeago' ), false, true );
        }

    }

	/**
	 * Load plugin textdomain for internationalisation
	 */
	public function load_textdomain(){

        load_plugin_textdomain( 'wlp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    }

	/**
	 * Get current site language symbol
	 *
	 * @return string
	 */
	private function get_language(){

        $locale = get_locale();
        $locale = explode('_', $locale);
        $locale = $locale[0];
        return $locale;

    }

	/**
	 * Get las order object
	 *
	 * @return bool|WC_Order
	 */
	public function get_last_order(){

		if( ! $this->is_woocommerce_installed() || ! is_product() ) {
			return false;
		}
		$order_id = $this->get_last_order_id();
		return wc_get_order($order_id);

	}

	/**
	 * Check is WooCommerce Directory
	 * in installed plugins array
	 *
	 * @return bool
	 */
	private function is_woocommerce_installed(){

        if ( ! in_array( $this->_woocommerce_directory, $this->get_installed_plugins() ) ) {
            return false;
        }
        return true;

    }

	/**
	 * Get all installed plugins. This is used
	 * to check is WooCommerce istalled
	 *
	 * @return array
	 */
	private function get_installed_plugins(){

        return apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

    }

    /**
     * Get last order ID
     * If no order, return false
     *
     * @return bool|string
     */
    private function get_last_order_id(){

        $last_order_meta = $this->get_last_order_meta();

        if ( !empty( $last_order_meta ) ) {
            return reset( $last_order_meta )->order_id;
        }
        return false;

    }

    /**
     * Get last order meta by ID of current product
     * If no orders found, return false
     *
     * This method needs to use wpdb object, because
     * of lack of similar function in WooCommerce API
     *
     * @return mixed
     */
    private function get_last_order_meta(){

        global $wpdb;

        $query = 'SELECT *
                  FROM wp_woocommerce_order_itemmeta
                  INNER JOIN wp_woocommerce_order_items
                  ON (wp_woocommerce_order_itemmeta.order_item_id = wp_woocommerce_order_items.order_item_id)
                  WHERE meta_key="_product_id" AND meta_value=' . get_the_ID() .'
                  ORDER BY meta_id DESC
                  LIMIT 1';

        $last_order_meta = $wpdb->get_results($query , OBJECT );

        if ( empty( $last_order_meta ) ){
            return false;
        }
        return $last_order_meta;

    }

	/**
	 * Load popup template file
	 */
	public function show_popup(){

		$popup_template = $this->get_templates_directory( 'popup.php' );

		if ( file_exists( $popup_template ) && ! $this->hide_popup ){
			include_once( $popup_template );
		}

	}

	/**
	 * Get file plugin templates directory
	 * If file parameter provided, return file path
	 *
	 * @param mixed $file
	 *
	 * @return string
	 */
	public function get_templates_directory( $file = false ){

		return plugin_dir_path( __FILE__ ) . 'templates/' . $file;

	}

	/**
	 * Get translated last purchased text
	 *
	 * @return string|void
	 */
	public function last_purchased_text(){

        return __( 'Last purchased', 'wlp' );

    }

    /**
     * Get last purchased Date
     *
     * @param string $date_format
     * @return bool|string
     */
    public function last_purchased_date( $date_format = "j-m-Y" ){

        $last_order = $this->get_last_order();

        if ( empty( $last_order ) || $last_order->post->post_type !== "shop_order" ){
            return false;
        }

        return date( $date_format, strtotime( $last_order->order_date ) );

    }

    /**
     * Get last purchased date in ISO 8601 format
     * This format is required to work with timeago.js jQuery plugin
     *
     * @return bool|string
     */
	public function last_purchased_time_ago(){

		$last_order = $this->get_last_order();

		if ( empty( $last_order ) || $last_order->post->post_type !== "shop_order" ){
			return false;
		}
		return date( "c", strtotime( $last_order->post->post_date_gmt ) );

	}

	/**
	 * Add last purchased date to post meta
	 */
	public function add_post_meta(){

		$last_order = $this->get_last_order();
		if ( ! update_post_meta ( get_the_ID(), 'last_purchased', $last_order->order_date ) ) {
			add_post_meta( get_the_ID(), 'last_purchased', $last_order->order_date, true );
		};

	}

}

endif;

/**
 * Returns the main instance of WLP to prevent the need to use globals.
 *
 * @return Woocommerce_Last_Purchased
 */
function WLP() {
    return Woocommerce_Last_Purchased::instance();
}

// Global for backwards compatibility.
$GLOBALS['WLP'] = WLP();

// Allow plugin description to be translatable
$plugin_description = __("This is a WooCommerce extension to show last purchased date popup on product page.");