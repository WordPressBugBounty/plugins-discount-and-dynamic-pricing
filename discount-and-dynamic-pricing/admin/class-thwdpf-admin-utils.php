<?php
/**
 * 
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){ die; }

if(!class_exists('THWDPF_Admin_Utils')):

class THWDPF_Admin_Utils{
	public function __construct() {

	}

	public static function get_all_product_categories($only_slug = false){
		$product_cats = self::get_product_terms('category', 'product_cat', $only_slug);
		return $product_cats;
	}

	public static function get_all_product_tags($only_slug = false){
		$product_tags = self::get_product_terms('tag', 'product_tag', $only_slug);
		return $product_tags;
	}

	private static function get_product_terms($type, $taxonomy, $only_slug = false){
		$product_terms = array();
		$pterms = get_terms($taxonomy, 'orderby=count&hide_empty=0');

		$ignore_translation = true;
		$is_wpml_active = THWDPF_Utils::is_wpml_active();
		if($is_wpml_active && $ignore_translation){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;
			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();
		}

		if(is_array($pterms)){
			foreach($pterms as $term){
				$dterm = $term;

				if($is_wpml_active && $ignore_translation){
					$dterm = THWDPF_Utils::get_default_lang_term($term, $taxonomy, $default_lang);
				}

				if($only_slug){
					$product_terms[] = $dterm->slug;
				}else{
					$product_terms[] = array("id" => $dterm->slug, "title" => $dterm->name);
				}
			}
		}

		if($is_wpml_active && $ignore_translation){
			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}

		return $product_terms;
	}

	public static function get_all_user_roles($only_id = false){		
		global $wp_roles;
    	$roles = $wp_roles->roles;
    	$user_roles = array();
		
		if($only_id){
			foreach($roles as $key => $role){
				$user_roles[] = $key;
			}
		}else{
			foreach($roles as $key => $role){
				$user_roles[$key] = $role['name'];
			}
		}
		return $user_roles;
	}
}

endif;