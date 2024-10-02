<?php
/**
 * The application scope class to retreive data.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Data')):

class THWDPF_Data {
	protected static $_instance = null;
	private $products = array();

	public function __construct() {
		add_action('wp_ajax_thwdpf_load_products', array($this, 'load_products_ajax'));
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function load_products_ajax(){
		$product_list = array();
		$value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
		$count = 0;
		$limit = apply_filters('thwdpf_load_products_per_page', 100);

		if(!empty($value)){
			$value_arr = is_array($value) ? $value : explode(',', stripslashes($value));

			$args = array(
			    'include' => $value_arr,
				'orderby' => 'name',
				'order' => 'ASC',
				'return' => 'ids',
				'limit' => $limit,
				'type'  => $this->get_all_product_types(),
			);
			$products = $this->get_products($args);

			if(is_array($products) && !empty($products)){
				foreach($products as $pid){
					$product_list[] = array("id" => $pid, "text" => get_the_title($pid). "(#" .$pid. ")", "selected" => true);
				}
			}

			$count = count($products);

		}else{
			$term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
			$page = isset($_POST['page']) ? sanitize_text_field($_POST['page']) : 1;

		    $status = apply_filters('thwdpf_load_products_status', 'publish');

		    $args = array(
				's' => $term,
			    'limit' => $limit,
			    'page'  => $page,
			    'status' => $status,
				'orderby' => 'name',
				'order' => 'ASC',
				'return' => 'ids',
				'type'  => $this->get_all_product_types(),
			);

			$products = $this->get_products($args);

			if(is_array($products) && !empty($products)){
				foreach($products as $pid){
					$product_list[] = array("id" => $pid, "text" => get_the_title($pid) . "(#" .$pid. ")" );
				}
			}

			$count = count($products);
		}

		$morePages = $count < $limit ? false : true;

		$results = array(
			"results" => $product_list,
			"pagination" => array( "more" => $morePages )
		);

		wp_send_json_success($results);
  		die();
	}

	private function get_products($args){
		$products = false;
		$is_wpml_active = THWDPF_Utils::is_wpml_active();

		if($is_wpml_active){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;

			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();
			$current_lang = $sitepress->get_current_language();
			$sitepress->switch_lang($default_lang);

			$products = wc_get_products($args);

			$sitepress->switch_lang($current_lang);
			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}else{
			$products = wc_get_products($args);
		}

		return $products;
	}

	/**
	 * Get all product types in store incluiding product variation
	 *
	 * @return Array
	 */ 
	private function get_all_product_types(){
		$product_types = array_merge( array_keys( wc_get_product_types() ));
		array_push($product_types, "variation");
		apply_filters('thwdpf_rules_product_types',  $product_types);
		return $product_types;
	}
}

endif;
