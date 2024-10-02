<?php
/**
 * The public specific functionality of the plugin.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/public
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Public')):
class THWDPF_Public {
	private $plugin_name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}
	
	public function enqueue_public_styles_and_scripts() {
		$debug_mode = apply_filters('thwdpf_debug_mode', false);
		$suffix = $debug_mode ? '' : '.min';

		$this->enqueue_styles($suffix);
		// $this->enqueue_scripts($suffix);
	}
	
	private function enqueue_styles($suffix) {
		wp_enqueue_style('thwdpf-public-style', THWDPF_ASSETS_URL_PUBLIC . 'css/thwdpf-public'. $suffix .'.css', $this->version);
	}

	private function enqueue_scripts($suffix) {
		$in_footer = apply_filters('thwdpf_enqueue_script_in_footer', false);
		$deps = array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-tiptip', 'woocommerce_admin', 'select2', 'wp-color-picker', 'wp-i18n');

		// wp_enqueue_script('thwdpf-timepicker-script', THWDPF_ASSETS_URL_PUBLIC.'js/timepicker/jquery.timepicker.min.js', array('jquery'), '1.0.1', $in_footer);

		wp_enqueue_script('thwdpf-public-script', THWDPF_ASSETS_URL_PUBLIC . 'js/thwdpf-public'. $suffix .'.js', $deps, $this->version, $in_footer);
		wp_set_script_translations('thwdpf-public-script', 'discount-and-dynamic-pricing', dirname(THWDPF_BASE_NAME) . '/languages/');

		//$skip_products_loading = THWDPF_Utils::skip_products_loading();
		//$skip_products_loading = $skip_products_loading ? 'yes' : 'no';

		$wdpf_var = array();
		wp_localize_script('thwdpf-public-script', 'wdpf_var', $wdpf_var);
		
	}
}
endif;	