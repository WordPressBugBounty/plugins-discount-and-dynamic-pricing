<?php
/**
 * Class to handle cart specific discount calculations for frontend.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/public
 */

if(!defined('WPINC')){ die; }

if(!class_exists('THWDPF_Public_Discount_Cart')):

class THWDPF_Public_Discount_Cart extends THWDPF_Public_Discount{

	public function __construct($plugin_name, $version) {
		parent::__construct($plugin_name, $version, 'cart');

		add_action('init', array($this, 'define_public_hooks'),20);
	}

	public function define_public_hooks(){
		add_action('woocommerce_before_cart', array($this, 'prepare_discount_coupons'));
		/* To apply cart rule after user logged in from checkout page */
		add_action('woocommerce_review_order_before_payment', array($this, 'prepare_discount_coupons'));
		add_filter('woocommerce_get_shop_coupon_data', array($this, 'get_discount_coupon_data'), 10, 3);
		add_filter('woocommerce_cart_totals_coupon_label', array($this, 'get_cart_totals_coupon_label'), 10, 2);
		add_filter('woocommerce_cart_totals_coupon_html', array($this, 'get_cart_totals_coupon_html'), 10, 3);
		add_filter('woocommerce_coupon_message', array($this, 'remove_coupon_messages'), 10, 3);
		add_filter('woocommerce_coupon_error', array($this, 'remove_coupon_errors'), 10, 3);
	}

	private function prepare_coupon_code($name, $rule){
    	return $name;
    }

    private function set_coupon_label($coupon, $label){
        $coupon->add_meta_data('thwdp_coupon_label', $label, true);
    }
    private function get_coupon_label($coupon){
    	return $coupon->get_meta('thwdp_coupon_label');
    }

    private function set_dynamic_discount($coupon, $flag=true){
    	$coupon->add_meta_data('thwdp_is_dynamic', $flag, true);
    }
    private function is_dynamic_discount($coupon){
    	return $coupon->get_meta('thwdp_is_dynamic');
    }

	public function prepare_discount_coupons() {
		$cart = WC()->cart;
		$discount_data = $this->get_cart_discount_data($cart);
		$rules = isset($discount_data['rules']) ? $discount_data['rules'] : array();
		foreach ($rules as $name => $rule) {
			$discount_amount = isset($rule['amount']) ? $rule['amount'] : '';

			if((float)$discount_amount > 0){
				try {
					$coupon_code = $this->prepare_coupon_code($name, $rule);

					if($coupon_code && !$cart->has_discount($coupon_code)){
						$coupon = new WC_Coupon($coupon_code);

						if($coupon->is_valid()){
							$cart->apply_coupon( $coupon_code );
						}
					}
					else{
						if($cart->has_discount($coupon_code)){
							$cart->remove_coupon( $coupon_code );
							$coupon = new WC_Coupon($coupon_code);

							if($coupon->is_valid()){
								$cart->apply_coupon( $coupon_code );
							}
						}
					}
				}catch (Exception $e) {
	                continue;
	            }
			}
		}
    }

	public function get_discount_coupon_data($coupon_data, $coupon_code, $coupon){
		$discount_data = $this->get_cart_discount_data();
		$rules = isset($discount_data['rules']) ? $discount_data['rules'] : array();

		if(is_array($rules) && array_key_exists($coupon_code, $rules)){
			$discount_data = $rules[$coupon_code];

			if(is_array($discount_data)){
				$discount_amount = isset($discount_data['amount']) ? $discount_data['amount'] : '';

				if((float)$discount_amount > 0){
					$discount_label = isset($discount_data['label']) ? $discount_data['label'] : '';
					$discount_type  = isset($discount_data['type']) ? $discount_data['type'] : '';
					$discount_type  = $discount_type === 'percentage' ? 'percent' : 'fixed_cart';

					$coupon_data = array(
			            'discount_type' => $discount_type,
			            'amount' => $discount_amount,
			            'individual_use' => false,
			        );
					
			        $this->set_dynamic_discount($coupon, true);
			        $this->set_coupon_label($coupon, $discount_label);
				}
			}
		}

		return apply_filters('thwdpf_cart_discount_coupon_data', $coupon_data);
	}

    public function get_cart_totals_coupon_label($label, $coupon){
    	$new_label = $this->get_coupon_label($coupon);
		return $new_label ? $new_label : $label;
	}

	public function get_cart_totals_coupon_html($coupon_html, $coupon, $discount_amount_html){
		if($this->is_dynamic_discount($coupon)){
			$coupon_html = $discount_amount_html;
		}
		return $coupon_html;
	}

	public function remove_coupon_messages($msg, $msg_code, $coupon){
		if($this->is_dynamic_discount($coupon)){
			$msg = '';
		}
		return $msg;
	}

	public function remove_coupon_errors($err, $err_code, $coupon) {
	    // Check if $coupon is not null before using it
	    if ($coupon !== null) {
	        $coupon_code = $coupon->get_code();
	        $code_prefix = substr($coupon_code, 0, 8);

	        if ($code_prefix === 'thdpprod' || $code_prefix === 'thdpcart') {
	            $err = '';
	        }
	    }
	    return $err;
	}


	/*******************************************
	 ***** PREPARE DISCOUNT DATA - START *******
	 *******************************************/
	private function get_valid_discount_rules($cart_summary){
		$rules = THWDPF_Utils::get_cart_rules();
		$rules = $this->get_qualified_rules($rules, $cart_summary);

		return $rules;
	}

	private function get_cart_discount_data($cart=false){
		$cart_summary = THWDPF_Utils::get_cart_summary();
		$rules = $this->get_valid_discount_rules($cart_summary);
		$discount_data = $this->prepare_discount_data($rules, $cart_summary);

		return $discount_data;
	}
	private function get_cart_percentage_amount($discount_amount, $cart_total){
			$discount_amount = $cart_total*($discount_amount/100);
		return $discount_amount;
	}

	private function prepare_discount_data($rules, $cart_summary){
		$discount_data = array();
		if(is_array($rules)){
			$discount_rules = array();
			$cart_total = isset($cart_summary['cart_subtotal']) ? $cart_summary['cart_subtotal'] : 0;
			foreach ($rules as $name => $rule) {
				$name  = $rule->get_property('name');
				$discount_amount = $rule->get_property('discount_amount');
				$discount_type = $rule->get_property('discount_type');
				if( ($discount_type == 'percentage') && ($discount_amount > 0) ){
					$discount_amount = $this->get_cart_percentage_amount($discount_amount, $cart_total);
					$discount_type = 'fixed';
					$cart_total = $cart_total - $discount_amount;
				}
				if($discount_amount > 0){
					$rule_info = array(
						'name'   => $name,
						'label'  => $rule->get_property('label'),
						'type'   => $discount_type,
						'amount' => $discount_amount, 
					);

					$discount_rules[$name] = $rule_info;
				}
			}

			$discount_data['rules'] = $discount_rules;
		}

		return empty($discount_data) ? false : $discount_data;
	}
	/*******************************************
	 ***** PREPARE DISCOUNT DATA - END *********
	 *******************************************/
}

endif;