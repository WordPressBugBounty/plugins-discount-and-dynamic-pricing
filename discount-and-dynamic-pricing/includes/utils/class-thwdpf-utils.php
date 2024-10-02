<?php
/**
 * The common utility functionalities for the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Utils')):

class THWDPF_Utils {
	const OPTION_KEY_DISCOUNT_RULES_PRODUCT = 'thwdpf_discount_rule_product';
	const OPTION_KEY_DISCOUNT_RULES_CART = 'thwdpf_discount_rule_cart';
	const OPTION_KEY_ADVANCED_SETTINGS = 'thwdpf_advanced_settings';
	const KEY_CART_TOTALS_BKP = 'thwdpf_cart_totals';

	public function __construct() {
		
	}

	public static function is_blank($value) {
		return empty($value) && !is_numeric($value);
	}

	public static function is_subset_of($arr1, $arr2){
		if(is_array($arr1) && is_array($arr2)){
			foreach($arr2 as $value){
				if(!in_array($value, $arr1)){
					return false;
				}
			}
		}
		return true;
	}

	public static function get_datetime_obj($date, $time, $mode='end'){
		$datetime = false;

		if(empty($time)){
			$time = $mode === 'end' ? '24:00' : '00:00';
		}
		
		if(!empty($date) && !empty($time)){
			$format = 'M d Y H:i';
			$datetime_str = $date.' '.$time;
			$datetime = DateTime::createFromFormat($format, $datetime_str, wp_timezone());
		}

		return $datetime;
	}

	public static function get_datetime_display($date, $time, $mode='end'){
		$datetime = self::get_datetime_obj($date, $time, $mode);
		return $datetime ? $datetime->format('M d Y H:i') : '--';
	}

	/*******************************************
	 ----- DISCOUNT RULE SETTINGS - START ------
	 *******************************************/
	public static function is_valid_rule($rule){
		if(isset($rule) && $rule instanceof THWDPF_Rule){
			return true;
		} 
		return false;
	}
	
	public static function is_enabled($rule){
		if($rule->get_property('enabled') === 'yes'){
			return true;
		}
		return false;
	}

	public static function is_valid_enabled($rule){
		if(self::is_valid_rule($rule) && self::is_enabled($rule)){
			return true;
		}
		return false;
	}

	public static function get_product_rules(){
		$rules = get_option(self::OPTION_KEY_DISCOUNT_RULES_PRODUCT);
		return is_array($rules) ? $rules : array();
	}
	
	public static function get_product_rule($name, $rules=false){
		if(!$rules){
			$rules = self::get_product_rules();
		}

		if(is_array($rules) && isset($rules[$name])){
			return $rules[$name];
		}
		return false;
	}

	public static function get_cart_rules(){
		$rules = get_option(self::OPTION_KEY_DISCOUNT_RULES_CART);
		return is_array($rules) ? $rules : array();
	}

	public static function get_cart_rule($name, $rules=false){
		if(!$rules){
			$rules = self::get_cart_rules();
		}

		if(is_array($rules) && isset($rules[$name])){
			return $rules[$name];
		}
		return false;
	}

	public static function sort_rules($rules){
		if(is_array($rules)){
			uasort($rules, array(__class__, 'sort_by_priority'));
		}
		return $rules;
	}
	
	public static function sort_by_priority($a, $b){
	    if($a->get_property('priority') == $b->get_property('priority')){
	        return 0;
	    }
	    return ($a->get_property('priority') < $b->get_property('priority')) ? -1 : 1;
	}
	/*******************************************
	 ----- DISCOUNT RULE SETTINGS - END --------
	 *******************************************/


	/**************************************
	 ----- ADVANCED SETTINGS - START ------
	 **************************************/
	public static function get_advanced_settings(){
		$settings = get_option(self::OPTION_KEY_ADVANCED_SETTINGS);
		$settings = apply_filters('thwdpf_advanced_settings', $settings);
		return empty($settings) ? false : $settings;
	}
	
	public static function get_setting_value($settings, $key, $default=''){
		if(is_array($settings) && isset($settings[$key])){
			return $settings[$key];
		}
		return $default != '' ? $default : false;
	}
	
	public static function get_settings($key){
		$settings = self::get_advanced_settings();
		if(is_array($settings) && isset($settings[$key])){
			return $settings[$key];
		}
		return false;
	}
	/**************************************
	 ----- ADVANCED SETTINGS - END --------
	 **************************************/


	public static function get_user_roles($user = false) {
		//$user = $user ? new WP_User( $user ) : wp_get_current_user();
		if(!$user){
			$user = wp_get_current_user();
		}

		if(!($user instanceof WP_User))
		   return false;

		$roles = $user->roles;
		return $roles;
	}

	public static function is_valid_cart_item($item){
		if(isset($item['data']) && $item['data'] instanceof WC_Product){
			return true;
		}
		return false;
	}

	public static function get_cart_totals($cart=false){
		$totals = false;
		$cart = is_a($cart, 'WC_Cart') ? $cart : WC()->cart;
		if($cart && $cart->subtotal > 0){
			$totals = array();
			$totals['cart_total'] = $cart->total;
			$totals['cart_subtotal'] = $cart->subtotal;
			$totals['cart_content_total'] = $cart->get_cart_contents_total();
		}
		return $totals;
	}

	public static function set_cart_totals_bkp($cart=false, $mode=''){
		$cart = is_a($cart, 'WC_Cart') ? $cart : WC()->cart;
		if($cart){
			$totals = self::get_cart_totals($cart);

			foreach($cart->cart_contents as $key => &$item) {
				// if($mode == 'new' && isset($item[THWDPF_Utils::KEY_CART_TOTALS_BKP])){
				// 	continue;
				// }

				$item[THWDPF_Utils::KEY_CART_TOTALS_BKP] = $totals;
			}
		}
	}

	public static function remove_cart_totals_bkp($cart=false){
		$cart = is_a($cart, 'WC_Cart') ? $cart : WC()->cart;
		if($cart){
			foreach($cart->cart_contents as $key => &$item) {
				if(isset($item[THWDPF_Utils::KEY_CART_TOTALS_BKP])){
					unset($item[THWDPF_Utils::KEY_CART_TOTALS_BKP]);
				}
			}
		}
	}

	public static function get_cart_totals_bkp(){
		if(WC()->cart){
			foreach(WC()->cart->cart_contents as $key => &$item) {
				if(isset($item[THWDPF_Utils::KEY_CART_TOTALS_BKP])){
					return $item[THWDPF_Utils::KEY_CART_TOTALS_BKP];
				}
			}
		}

		return false;
	}

	public static function get_cart_total_summary($cart){
		$totals = false;

		if(WC()->cart){
			if(WC()->cart->subtotal > 0){
				$totals = self::get_cart_totals(WC()->cart);
			}else{
				$totals = self::get_cart_totals_bkp();
			}
		}

		return $totals;
	}	

	public static function get_product_summary($product, $quantity=false){
		$summary = array();

		if($product){
			$product_id = $product->get_id();

			$summary['prod_price_regular'] = $product->get_regular_price();
			$summary['prod_price_sale']    = $product->get_sale_price();
			$summary['prod_price'] = $product->get_price();
			$summary['prod_qty']   = self::get_product_qty($product, $quantity);
			$summary['product_id'] = $product_id;

			$summary['categories'] = self::get_product_categories($product_id);
			$summary['tags'] 	   = self::get_product_tags($product_id);
		}

		if(WC()->cart){
			$cart = WC()->cart;

			$summary['cart_qty']    = $cart->get_cart_contents_count();
			$summary['cart_count']  = self::get_number_of_cart_items($cart);
			$summary['cart_weight'] = $cart->get_cart_contents_weight();
			$summary['cart_total']  = $cart->total;
			$summary['cart_subtotal'] = $cart->subtotal;
			$summary['cart_content_total'] = $cart->get_cart_contents_total();

			$cart_totals = self::get_cart_total_summary($cart);
			if(is_array($cart_totals)){
				$summary = array_merge($summary, $cart_totals);
			}
		}
		
		return $summary;
	}

	public static function get_cart_summary(){
		$summary = array();
		$summary['products']   = array();
		$summary['categories'] = array();
		$summary['tags'] 	   = array();
		$summary['variations'] = array();

		if(WC()->cart){
			$cart = WC()->cart;
			$cart_items = $cart->get_cart();

			$cart_qty    = $cart->get_cart_contents_count();
			$cart_count  = self::get_number_of_cart_items($cart);
			$cart_weight = $cart->get_cart_contents_weight();
			$cart_total  = $cart->total; //self::get_cart_total($cart);
			$cart_subtotal = $cart->subtotal;

			foreach($cart_items as $item => $cart_item) {
				$product_id = $cart_item['product_id'];
				$prod_cats  = self::get_product_categories($product_id);
				$prod_tags  = self::get_product_tags($product_id);

				$summary['products'][] = self::get_original_product_id($product_id);
				$summary['categories'] = array_merge($summary['categories'], $prod_cats);
				$summary['tags']       = array_merge($summary['tags'], $prod_tags);

				if($cart_item['variation_id']){
					$summary['variations'][] = $cart_item['variation_id'];
					$summary['products'][] = self::get_original_product_id($cart_item['variation_id']);
				}
			}

			$summary['categories'] = array_unique(array_values($summary['categories']));
			$summary['tags'] = array_unique(array_values($summary['tags']));

			$summary['products']    = array_values($summary['products']);
			$summary['categories']  = apply_filters('thwdpf_cart_product_categories', $summary['categories']);
			$summary['tags'] 		= apply_filters('thwdpf_cart_product_tags', $summary['tags']);
			$summary['variations']  = array_values($summary['variations']);
			$summary['cart_qty']    = $cart_qty;
			$summary['cart_count']  = $cart_count;
			$summary['cart_weight'] = $cart_weight;
			$summary['cart_total']  = $cart_total;
			$summary['cart_subtotal'] = $cart_subtotal;
			$summary['cart_content_total'] = $cart->get_cart_contents_total();
		}

		return $summary;
	}

	public static function get_product_qty($product, $quantity=0){
		if($product && $quantity <= 0){
			$cart_items = WC()->cart ? WC()->cart->get_cart() : false;

			if(is_array($cart_items)){
				$prod_id = $product->get_id();

				foreach($cart_items as $cart_item){
					$product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
					$variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;

				    if(in_array($prod_id, array($product_id, $variation_id))){
				        $quantity = $cart_item['quantity'];
				        break;
				    }
				}
			}
		}
		return $quantity;
	}

	public static function get_cart_total($cart) {
		return wc_prices_include_tax() ? $cart->get_cart_contents_total() + $cart->get_cart_contents_tax() : $cart->get_cart_contents_total();
	}

	public static function get_number_of_cart_items($cart) {
		$cart_items = $cart->get_cart();
		return is_array($cart_items) ? count($cart_items) : 0;
	}

	public static function get_product_categories($product_id){
		$ignore_translation = apply_filters('thwdpf_ignore_wpml_translation_for_product_category', true);
		$categories = self::get_product_terms($product_id, 'category', 'product_cat', $ignore_translation);
		return $categories;
	}

	public static function get_product_tags($product_id){
		$ignore_translation = apply_filters('thwdpf_ignore_wpml_translation_for_product_tag', true);
		$tags = self::get_product_terms($product_id, 'tag', 'product_tag', $ignore_translation);
		return $tags;
	}

	public static function get_product_terms($product_id, $type, $taxonomy, $ignore_translation=false){
		$terms = array();
		$product = wc_get_product( $product_id );
		if($product){
			$prod_type = $product->get_type();
			if($prod_type === 'variation'){
				$product_id = $product->get_parent_id();
			}
		}
		$assigned_terms = wp_get_post_terms($product_id, $taxonomy);
		$is_wpml_active = self::is_wpml_active();
		if($is_wpml_active && $ignore_translation){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;
			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();
		}

		if(is_array($assigned_terms)){
			foreach($assigned_terms as $term){
				$parent_terms = get_ancestors($term->term_id, $taxonomy);
				if(is_array($parent_terms)){
					foreach($parent_terms as $pterm_id){
						$pterm = get_term($pterm_id, $taxonomy);
						$terms[] = $pterm->slug;
					}
				}

				$term_slug = $term->slug;
				if($is_wpml_active && $ignore_translation){
					$default_term = self::get_default_lang_term($term, $taxonomy, $default_lang);
					$term_slug = $default_term->slug;
				}
				$terms[] = $term_slug;
			}
		}

		if($is_wpml_active && $ignore_translation){
			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}

		return $terms;
	}

	public static function get_default_lang_term($term, $taxonomy, $default_lang){
		$dterm_id = icl_object_id($term->term_id, $taxonomy, true, $default_lang);
		$dterm = get_term($dterm_id);
		return $dterm;
	}

	public static function get_original_product_id($product_id){
		$is_wpml_active = self::is_wpml_active();
		//$ignore_translation = true;

		if($is_wpml_active){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;

			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();

			$product_id = icl_object_id($product_id, 'product', true, $default_lang);

			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}
		return $product_id;
	}


	public static function wdpf_capability() {
		$allowed = array('manage_woocommerce', 'manage_options');
		$capability = apply_filters('thwdpf_required_capability', 'manage_woocommerce');

		if(!in_array($capability, $allowed)){
			$capability = 'manage_woocommerce';
		}
		return $capability;
	}

	public static function woo_version_check( $version = '3.0' ) {
	  	if(function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if( version_compare( $woocommerce->version, $version, ">=" ) ) {
		  		return true;
			}
	  	}
	  	return false;
	}

	public static function wdpf_version_check( $version = '1.0.0' ) {
		if(THWDPF_VERSION && version_compare( THWDPF_VERSION, $version, ">=" ) ) {
	  		return true;
		}
	  	return false;
	}

	public static function is_wpml_active(){
		global $sitepress;
		return function_exists('icl_object_id') && is_object($sitepress);
	}

	/**
     * Check the WEPO Pro is active.
     *
     *
     * @return Boolean.
     */
    public static function check_thwepo_plugin_is_active()  {
    	return is_plugin_active('woocommerce-extra-product-options-pro/woocommerce-extra-product-options-pro.php');
    }

	public static function write_log ( $log )  {
		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}
}

endif;
