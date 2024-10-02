<?php
/**
* Plugin Name: Dynamic Pricing and Discount Rules
* Description: Dynamic Pricing and Discount Rules For WooComemerce plugin let you create and manage discount rules for your products and cart.
* Version:     2.2.8
* Author:      ThemeHigh
* Author URI:  https://www.themehigh.com
* Text Domain: discount-and-dynamic-pricing
* Domain Path: /languages 
* Requires at least: 5.2
* Requires PHP: 7.2
* WC requires at least: 4.0.0
* WC tested up to: 9.1
*/

if(!defined( 'ABSPATH' )) exit;

if (!function_exists('is_woocommerce_active')){
	function is_woocommerce_active(){
	    $active_plugins = (array) get_option('active_plugins', array());
	    if(is_multisite()){
		   $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	    }
	    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || class_exists('WooCommerce');
	}
}

if(is_woocommerce_active()) {
	!defined('THWDPF_FILE') && define('THWDPF_FILE', __FILE__);
	!defined('THWDPF_BASE_NAME') && define('THWDPF_BASE_NAME', plugin_basename( __FILE__ ));
	!defined('THWDPF_PATH') && define('THWDPF_PATH', plugin_dir_path( __FILE__ ));
	!defined('THWDPF_URL') && define('THWDPF_URL', plugins_url( '/', __FILE__ ));

	/**
     * The code that runs during plugin activation.
     */
	function activate_thwdpf() {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-thwdpf-activator.php');
		THWDPF_Activator::activate();
	}
	register_activation_hook( __FILE__, 'activate_thwdpf' );

	function run_thwdpf() {
		require plugin_dir_path( __FILE__ ) . 'includes/class-thwdpf.php';
		$plugin = new THWDPF();
	}
	run_thwdpf();

	add_action( 'before_woocommerce_init', 'thwdpf_before_woocommerce_init' ) ;

	function thwdpf_before_woocommerce_init() {
	    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
	        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	    }
	}
}
