<?php
/**
 * Dynamic Pricing base class for frontend handles discount calculations.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/public
 */
if(!defined('WPINC')){ die; }

if(!class_exists('THWDPF_Public_Discount')):

abstract class THWDPF_Public_Discount{
	public $plugin_name;
	public $version;
	public $context;

	const ITEM_KEY_DISCOUNTS = 'thwdpf_discounts';
	const ITEM_KEY_ORIGINAL_PRICE = 'thwdpf_original_price';

	public function __construct($plugin_name, $version, $context) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->context = $context;
	}

	public function calculate_discount($discount_type, $discount_amount, $total){
		$discount = 0;
		
		if(is_numeric($discount_amount)){
			if($discount_type === 'percentage' && is_numeric($total)){
				$discount = ($total*$discount_amount)/100;

			}else if($discount_type === 'fixed'){
				$discount = $discount_amount;
			}
		}
		
		return apply_filters('thwdpf_cart_calculated_discount', $discount, $discount_type, $discount_amount, $total);
	}

	/********************************************
	 ******** Filter Discount Rules - START *****
	 ********************************************/
	public function get_qualified_rules($rules, $args){
		$qualified_rules = array();

		if(is_array($rules)){
			$is_logged_in = is_user_logged_in();
			$user  = wp_get_current_user();
			$roles = THWDPF_Utils::get_user_roles($user);
			$today = current_datetime();
		
			foreach ($rules as $name => $rule) {
				if(THWDPF_Utils::is_valid_enabled($rule)){
					if(!$this->is_active($rule, $today)){
						continue;
					}

					if(!$this->is_authorized($rule, $is_logged_in, $user, $roles)){
						continue;
					}

					if($this->is_restricted($rule, $args)){
						continue;
					}

					$qualified_rules[$name] = $rule;
					//$qualified_rules = $this->use_if_allowed($name, $rule, $qualified_rules);
				}
			}

			$qualified_rules = $this->filter_by_apply_when($qualified_rules);
		}

		return $qualified_rules;
	}

	public function filter_by_apply_when($rules){
		$qrules = array();
		$qrules_if_none = array();

		foreach ($rules as $name => $rule) {
			$use_when = $rule->get_property('apply_when');

			if($use_when === 'apply_this_also'){
				$qrules[$name] = $rule;

			}else if($use_when === 'apply_this_only'){
				$qrules = array();
				$qrules_if_none = array();
				$qrules[$name] = $rule;
				break;

			}else if($use_when === 'apply_if_none' && empty($qrules)){
				$qrules_if_none[$name] = $rule;
			}
		}

		foreach ($qrules_if_none as $name => $rule) {
			if(empty($qrules)){
				$qrules[$name] = $rule;
			}
		}

		return $qrules;
	}

	public function is_active($rule, $today){
		$active = true;
		$schedule = $rule->get_property('schedule');
		
		if(is_array($schedule)){
			foreach ($schedule as $value) {
				$start_date = isset($value['start_date']) ? $value['start_date'] : false;
				$start_time = isset($value['start_time']) ? $value['start_time'] : false;
				$end_date = isset($value['end_date']) ? $value['end_date'] : false;
				$end_time = isset($value['end_time']) ? $value['end_time'] : false;

				$start = THWDPF_Utils::get_datetime_obj($start_date, $start_time, 'start');
				$end = THWDPF_Utils::get_datetime_obj($end_date, $end_time, 'end');

				if($start && $today < $start){
					$active = false;
				}

				if($end && $today > $end){
					$active = false;
				}
			}
		}

		return $active;
	}

	public function is_authorized($rule, $is_logged_in, $user, $roles){
		$need_login = $rule->get_property('need_login');

		if($need_login === 'yes'){
			if(!$is_logged_in){
				return false;
			}

			$allowed_roles = $rule->get_property('allowed_roles');
			if(is_array($allowed_roles) && !empty($allowed_roles)){
				$is_authorized = !empty(array_intersect($roles, $allowed_roles));
				return $is_authorized;
			}
		}

		return true;
	}

	/*public function use_if_allowed($name, $rule, $qrules){
		$use_when = $rule->get_property('apply_when');

		if($use_when === 'apply_if_none' && empty($qrules)){
			$qrules[$name] = $rule;

		}else if($use_when === 'apply_this_only'){
			$qrules = array();
			$qrules[$name] = $rule;

		}else if($use_when === 'apply_this_also'){
			$qrules[$name] = $rule;
		}

		return $qrules;
	}*/

	private function is_restricted($rule, $args){
		$buy_restrictions = $rule->get_property('buy_restrictions');

		// if(!$this->is_qualified_product($buy_restrictions, $args)){
		// 	return true;
		// }

		// if(!$this->is_qualified_category($buy_restrictions, $args)){
		// 	return true;
		// }

		if(!$this->is_qualified_others($buy_restrictions, $args)){
			return true;
		}

		return false;
	}

	public function is_qualified_product($restrictions, $args){
		$product_id = isset($args['product_id']) ? $args['product_id'] : false;

		if($product_id){
			$allowed_products = $restrictions->get_property('allowed_products');
			$restricted_products = $restrictions->get_property('restricted_products');
			/* If allowed product contain variable id then all its variations should be allowed.*/
			if(is_array($allowed_products)){
				foreach ($allowed_products as $each_id) {
					$product = wc_get_product( $each_id );
					if($product){
						$prod_type = $product->get_type();
						if($prod_type === 'variable'){
							$children_ids = $product->get_children();
							if(in_array($product_id, $children_ids)){
								if(is_array($restricted_products) && in_array($product_id, $restricted_products)){
									return false;
								}
								return true;
							}
						}
					}
				}
			}
			/* in the case of variable id as product id,then check allowed on its variantions(children) */
			$product = wc_get_product( $product_id );
			if($product){
				$prod_type = $product->get_type();
				if($prod_type === 'variable'){
					$children_ids = $product->get_children();
					foreach ($children_ids as $product_ids) {
						if(is_array($allowed_products) && in_array($product_ids, $allowed_products)){
							return true;
						}
					}
				}
			}
			if(is_array($allowed_products) && !in_array($product_id, $allowed_products)){
				return false;
			}
			/* If restricted products contain variable id then all its variations should be restricted.*/
			if(is_array($restricted_products)){
				foreach ($restricted_products as $each_id) {
					$product = wc_get_product( $each_id );
					if($product){
						$prod_type = $product->get_type();
						if($prod_type === 'variable'){
							$children_ids = $product->get_children();
							if(in_array($product_id, $children_ids)){
								if(is_array($allowed_products) && in_array($product_id, $allowed_products)){
									return true;
								}
								return false;
							}
						}
					}
				}
			}
			if(is_array($restricted_products) && in_array($product_id, $restricted_products)){
				return false;
			}
		}
		return true;
	}

	public function is_qualified_category($restrictions, $args){
		$categories = isset($args['categories']) ? $args['categories'] : false;

		if(is_array($categories) && !empty($categories)){
			$allowed_cats = $restrictions->get_property('allowed_cats');

			if(is_array($allowed_cats) && !empty($allowed_cats)){
				$intersection = array_intersect($categories, $allowed_cats);

				if(empty($intersection)){
					return false;
				}
			}

			$restricted_cats = $restrictions->get_property('restricted_cats');
			if(is_array($restricted_cats) && !empty($restricted_cats)){
				$intersection = array_intersect($categories, $restricted_cats);

				if(!empty($intersection)){
					return false;
				}
			}
		}
		return true;
	}

	public function is_qualified_others($restrictions, $args){
		$restrictions = $restrictions->get_property('restrictions_other');
		$valid = THWDPF_Utils_Condition::is_satisfied($restrictions, $args);
		return $valid;
	}
	/********************************************
	 ******** Filter Discount Rules - END *******
	 ********************************************/
}

endif;