<?php
/**
 * The dicount rule conditions specific functionality for the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/includes/utils
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Utils_Condition')):

class THWDPF_Utils_Condition {
	const LOGIC_AND = 'and';
	const LOGIC_OR  = 'or';
	
	const PRODUCT = 'product';
	const PRODUCT_VARIATION = 'product_variation';
	const CATEGORY = 'category';
	const TAG = 'tag';
	
	const USER_ROLE_EQ = 'user_role_eq';
	const USER_ROLE_NE = 'user_role_ne';
	
	const CART_CONTAINS = 'cart_contains'; 
	const CART_NOT_CONTAINS = 'cart_not_contains'; 
	const CART_ONLY_CONTAINS = 'cart_only_contains';
	
	const VALUE_EQ = 'value_eq';
	const VALUE_NE = 'value_ne'; 
	const VALUE_GT = 'value_gt'; 
	const VALUE_LT = 'value_lt';

	const PROD_QTY = 'prod_qty';
	const PROD_PRICE = 'prod_price';
	const PROD_PRICE_REG = 'prod_price_regular';
	const PROD_PRICE_SALE = 'prod_price_sale';
	
	const CART_QTY = 'cart_qty';
	const CART_COUNT = 'cart_count';
	const CART_TOTAL = 'cart_total';
	const CART_WEIGHT = 'cart_weight';
	const CART_SUBTOTAL = 'cart_subtotal';
	
	const PURCH_QTY = 'purch_qty';
	const PURCH_TOTAL = 'purch_total';
	const PURCH_AVG_TOTAL = 'avg_order_total';
	
	public static function is_valid_condition($condition){
		if($condition && $condition instanceof THWDPF_Condition){			
			if(!empty($condition->operand_type) && !empty($condition->operator)){
				return true;
			}
		}
		return false;
	}
	
	public static function is_satisfied($rules_set_list, $args){
		$valid = true;
		if(is_array($rules_set_list) && !empty($rules_set_list)){
			foreach($rules_set_list as $rules_set){				
				if(!self::is_satisfied_rules_set($rules_set, $args)){
					$valid = false;
				}
			}
		}
		return $valid;
	}
	
	public static function is_satisfied_rules_set($rules_set, $args){
		$satisfied = true;
		$condition_rules = $rules_set->get_condition_rules();
		$logic = $rules_set->get_logic();
		
		if(!empty($condition_rules)){
			if($logic === self::LOGIC_AND){			
				foreach($condition_rules as $condition_rule){				
					if(!self::is_satisfied_rule($condition_rule, $args)){
						$satisfied = false;
						break;
					}
				}
			}else if($logic === self::LOGIC_OR){
				$satisfied = false;
				foreach($condition_rules as $condition_rule){				
					if(self::is_satisfied_rule($condition_rule, $args)){
						$satisfied = true;
						break;
					}
				}
			}
		}
		return $satisfied;
	}
	
	private static function is_satisfied_rule($rule, $args){
		$satisfied = true;
		$conditions_set_list = $rule->get_condition_sets();
		$logic = $rule->get_logic();
		
		if(!empty($conditions_set_list)){
			if($logic === self::LOGIC_AND){			
				foreach($conditions_set_list as $conditions_set){				
					if(!self::is_satisfied_conditions_set($conditions_set, $args)){
						$satisfied = false;
						break;
					}
				}
			}else if($logic === self::LOGIC_OR){
				$satisfied = false;
				foreach($conditions_set_list as $conditions_set){				
					if(self::is_satisfied_conditions_set($conditions_set, $args)){
						$satisfied = true;
						break;
					}
				}
			}			
		}
		return $satisfied;
	}
	
	private static function is_satisfied_conditions_set($conditions_set, $args){
		$satisfied = true;
		$conditions = $conditions_set->get_conditions();
		$logic = $conditions_set->get_logic();
		
		if(!empty($conditions)){			 
			if($logic === self::LOGIC_AND){			
				foreach($conditions as $condition){				
					if(!self::is_satisfied_condition($condition, $args)){
						$satisfied = false;
						break;
					}
				}
			}else if($logic === self::LOGIC_OR){
				$satisfied = false;
				foreach($conditions as $condition){				
					if(self::is_satisfied_condition($condition, $args)){
						$satisfied = true;
						break;
					}
				}
			}
		}
		return $satisfied;
	}

	public static function is_satisfied_condition($condition, $args){
		$satisfied = true;
		if(self::is_valid_condition($condition)){
			$op_type  = $condition->operand_type;
			$operator = $condition->operator;
			$operands = $condition->operand;
			
			if($op_type == self::PRODUCT){
				if(!self::is_satisfied_cart_products($operator, $operands, $args)){
					return false;
				}
			}else if($op_type == self::PRODUCT_VARIATION){
				if(!self::is_satisfied_cart_product_variations($operator, $operands, $args)){
					return false;
				}
			}else if($op_type == self::CATEGORY){
				if(!self::is_satisfied_cart_categories($operator, $operands, $args)){
					return false;
				}
			}else if($op_type == self::TAG){
				if(!self::is_satisfied_cart_tags($operator, $operands, $args)){
					return false;
				}
			}else if($operator == self::USER_ROLE_EQ || $operator == self::USER_ROLE_NE){
				if(!self::is_satisfied_user_roles($operator, $operands, $args)){
					return false;
				}
			}else{
				if(is_numeric($operands)){
					if(!self::is_satisfied_cart_properties($op_type, $operator, $operands, $args)){
						return false;
					}
				}
			}
		}

		return $satisfied;
	}

	public static function is_satisfied_user_roles($operator, $operands, $args){
		$user_roles = THWDPF_Utils::get_user_roles();

		if(is_array($user_roles) && is_array($operands)){
			$intersection = array_intersect($user_roles, $operands);
			
			if($operator == self::USER_ROLE_EQ) {
				if(empty($intersection)){
					return false;
				}
			}else if($operator == self::USER_ROLE_NE){
				if(!empty($intersection)){
					return false;
				}
			}
		}

		return true;
	}

	public static function is_satisfied_cart_products($operator, $operands, $args){
		$products = false;
		if(is_array($args) && isset($args['products'])){
			$products = $args['products'];
		}

		if(is_array($products) && !empty($products)){
			$intersection = array();

			if(is_array($operands) && in_array('-1', $operands)){
				//$operands = THWDPF_Utils::get_products(true);
				$operands = $products; //TODO
			}

			if(is_array($operands)){
				$intersection = array_intersect($products, $operands);
			}

			if($operator == self::CART_CONTAINS) {
				if(!THWDPF_Utils::is_subset_of($products, $operands)){
					return false;
				}
			}else if($operator == self::CART_NOT_CONTAINS){
				if(!empty($intersection)){
					return false;
				}
			}else if($operator == self::CART_ONLY_CONTAINS){
				if($products != $operands){
					return false;
				}
			}
		}

		return true;
	}

	public static function is_satisfied_cart_product_variations($operator, $operands, $args){
		$product_variations = false;
		if(is_array($args) && isset($args['product_variations'])){
			$product_variations = $args['product_variations'];
		}

		if(is_array($product_variations) && !empty($product_variations)){
			$intersection = array();
			$operands = is_array($operands) ? $operands : explode(',', $operands);

			if(is_array($operands)){
				$intersection = array_intersect($product_variations, $operands);
			}

			if($operator == self::CART_CONTAINS) {
				if(!THWDPF_Utils::is_subset_of($product_variations, $operands)){
					return false;
				}
			}else if($operator == self::CART_NOT_CONTAINS){
				if(!empty($intersection)){
					return false;
				}
			}else if($operator == self::CART_ONLY_CONTAINS){
				if($product_variations != $operands){
					return false;
				}
			}
		}

		return true;
	}

	public static function is_satisfied_cart_categories($operator, $operands, $args){
		$categories = false;
		if(is_array($args) && isset($args['categories'])){
			$categories = $args['categories'];
		}

		if(is_array($categories) && !empty($categories)){
			$intersection = array();

			if(is_array($operands) && in_array('-1', $operands)){
				$operands = THWDPF_Utils::load_products_cat(true, false);
			}
			$operands = self::check_for_wpml_translations($operands);

			if(is_array($operands)){
				$intersection = array_intersect($categories, $operands);
			}

			if($operator == self::CART_CONTAINS) {
				if(!THWDPF_Utils::is_subset_of($categories, $operands)){
					return false;
				}
			}else if($operator == self::CART_NOT_CONTAINS){
				if(!empty($intersection)){
					return false;
				}
			}else if($operator == self::CART_ONLY_CONTAINS){
				if($categories != $operands){
					return false;
				}
			}
		}

		return true;
	}

	public static function is_satisfied_cart_tags($operator, $operands, $args){
		$tags = false;
		if(is_array($args) && isset($args['tags'])){
			$tags = $args['tags'];
		}

		if(is_array($tags) && !empty($tags)){
			$intersection = array();

			if(is_array($operands) && in_array('-1', $operands)){
				$operands = THWDPF_Utils::load_product_tags(true, false); //TODO
			}
			$operands = self::check_for_wpml_translations($operands);

			if(is_array($operands)){
				$intersection = array_intersect($tags, $operands);
			}

			if($operator == self::CART_CONTAINS) {
				if(!THWDPF_Utils::is_subset_of($tags, $operands)){
					return false;
				}
			}else if($operator == self::CART_NOT_CONTAINS){
				if(!empty($intersection)){
					return false;
				}
			}else if($operator == self::CART_ONLY_CONTAINS){
				if($tags != $operands){
					return false;
				}
			}
		}

		return true;
	}

	public static function is_satisfied_cart_properties($op_type, $operator, $operand, $args){
		if($args){
			$value = false;

			if($op_type == self::PROD_PRICE){
				$value = isset($args['prod_price']) ? $args['prod_price'] : false;

			}else if($op_type == self::PROD_PRICE_REG){
				$value = isset($args['prod_price_regular']) ? $args['prod_price_regular'] : false;

			}else if($op_type == self::PROD_PRICE_SALE){
				$value = isset($args['prod_price_sale']) ? $args['prod_price_sale'] : false;

			}else if($op_type == self::PROD_QTY){
				$value = isset($args['prod_qty']) ? $args['prod_qty'] : false;
				
			}else if($op_type == self::CART_QTY){
				$value = isset($args['cart_qty']) ? $args['cart_qty'] : false;

			}else if($op_type == self::CART_COUNT){
				$value = isset($args['cart_count']) ? $args['cart_count'] : false;

			}else if($op_type == self::CART_TOTAL){
				$value = isset($args['cart_total']) ? $args['cart_total'] : false;

			}else if($op_type == self::CART_SUBTOTAL){
				$value = isset($args['cart_subtotal']) ? $args['cart_subtotal'] : false;

			}else if($op_type == self::CART_WEIGHT){
				$value = isset($args['cart_weight']) ? $args['cart_weight'] : false;

			}else if($op_type == self::PURCH_TOTAL){
				$value = isset($args['purch_total']) ? $args['purch_total'] : false;

			}else if($op_type == self::PURCH_TOTAL){
				$value = isset($args['purch_qty']) ? $args['purch_qty'] : false;
				
			}else if($op_type == self::PURCH_AVG_TOTAL){
				$value = isset($args['avg_order_total']) ? $args['avg_order_total'] : false;
			}
			if($operator == self::VALUE_EQ){
				if($value != $operand){
					return false;
				}
			}else if($operator == self::VALUE_NE){
				if($value == $operand){
					return false;
				}
			}else if($operator == self::VALUE_GT){
				if($value <= $operand){
					return false;
				}
			}else if($operator == self::VALUE_LT){
				if($value >= $operand){
					return false;
				}
			}
		}
		return true;
	}
	
	public static function prepare_conditional_rules($posted, $ajax=false){
		$iname = $ajax ? 'i_rules_ajax' : 'i_rules';
		$conditional_rules = isset($posted[$iname]) ? trim(stripslashes($posted[$iname])) : '';
		
		$condition_rule_sets = array();	
		if(!empty($conditional_rules)){
			$conditional_rules = urldecode($conditional_rules);
			$rule_sets = json_decode($conditional_rules, true);
				
			if(is_array($rule_sets)){
				foreach($rule_sets as $rule_set){
					if(is_array($rule_set)){
						$condition_rule_set_obj = new THWDPF_Condition_Rule_Set();
						$condition_rule_set_obj->set_logic('and');
												
						foreach($rule_set as $condition_sets){
							if(is_array($condition_sets)){
								$condition_rule_obj = new THWDPF_Condition_Rule();
								$condition_rule_obj->set_logic('or');
														
								foreach($condition_sets as $condition_set){
									if(is_array($condition_set)){
										$condition_set_obj = new THWDPF_Condition_Set();
										$condition_set_obj->set_logic('and');
													
										foreach($condition_set as $condition){
											if(is_array($condition)){
												$condition_obj = new THWDPF_Condition();
												$condition_obj->set_property('operand_type', isset($condition['operand_type']) ? $condition['operand_type'] : '');
												$condition_obj->set_property('operand', isset($condition['operand']) ? $condition['operand'] : '');
												$condition_obj->set_property('operator', isset($condition['operator']) ? $condition['operator'] : '');
												$condition_obj->set_property('value', isset($condition['value']) ? $condition['value'] : '');
												
												$condition_set_obj->add_condition($condition_obj);
											}
										}										
										$condition_rule_obj->add_condition_set($condition_set_obj);	
									}								
								}
								$condition_rule_set_obj->add_condition_rule($condition_rule_obj);
							}
						}
						$condition_rule_sets[] = $condition_rule_set_obj;
					}
				}	
			}
		}
		return $condition_rule_sets;
	}

	public static function prepare_conditional_rules_json($cr_set_list, $ajaxFlag=false){
		$conditional_rules_json = '';

		if(is_array($cr_set_list)){
			$condition_rules_arr = array();

			foreach($cr_set_list as $rules_set){	
				$condition_rules = $rules_set->get_condition_rules();
					
				if(is_array($condition_rules)){
					$rule_set_arr = array();

					foreach($condition_rules as $crk => $condition_rule){				
						$conditions_set_list = $condition_rule->get_condition_sets();
						
						if(is_array($conditions_set_list)){
							$rule_arr = array();

							foreach($conditions_set_list as $csk => $conditions_set){				
								$conditions = $conditions_set->get_conditions();
								
								if(is_array($conditions)){
									$conditions_arr = array();

									foreach($conditions as $condition){				
										if(self::is_valid_condition($condition)){
											$condition_arr = array();
											$condition_arr["operand_type"] = $condition->operand_type;
											$condition_arr["value"] = $condition->value;
											$condition_arr["operator"] = $condition->operator;
											$condition_arr["operand"] = $condition->operand;

											$conditions_arr[] = $condition_arr;
										}
									}

									if(!empty($conditions_arr)){
										$rule_arr[] = $conditions_arr;
									}
								}
							}

							if(!empty($rule_arr)){
								$rule_set_arr[] = $rule_arr;
							}
						}
					}

					if(!empty($rule_set_arr)){
						$condition_rules_arr[] = $rule_set_arr;
					}
				}
			}

			if(!empty($condition_rules_arr)){
				$conditional_rules_json = json_encode($condition_rules_arr, true);
				$conditional_rules_json = urlencode($conditional_rules_json);
			}
		}

		return $conditional_rules_json;
	}
	
	
	private static function check_for_wpml_translations($taxonomies){
		if(apply_filters( 'thwdpf_cr_use_wpml_translated_taxonomy', false )){
			if(is_array($taxonomies)){
				foreach($taxonomies as $key => $value){
					$taxonomies[$key] = self::get_wpml_translated_taxonomy($value);
				}
			}
		}		
		return $taxonomies;
	}
	
	private static function get_wpml_translated_taxonomy($slug){
		$translated_slug = $slug;
		if(defined('ICL_LANGUAGE_CODE')){
			$translated_slug = ICL_LANGUAGE_CODE != 'en' ? $slug.'-'.ICL_LANGUAGE_CODE : $slug;
			$translated_slug = apply_filters( 'thwdpf_cr_wpml_translated_taxonomy', $translated_slug, $slug, ICL_LANGUAGE_CODE );
		}
		return $translated_slug;
	}
}

endif;