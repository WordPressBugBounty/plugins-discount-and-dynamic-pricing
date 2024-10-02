<?php
/**
 * Class to handles product specific discount calculations for frontend.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/public
 */

if(!defined('WPINC')){ die; }

if(!class_exists('THWDPF_Public_Discount_Product')):

class THWDPF_Public_Discount_Product extends THWDPF_Public_Discount{
	private $price;

	public function __construct($plugin_name, $version) {
		parent::__construct($plugin_name, $version, 'product');
		add_action('after_setup_theme', array($this, 'define_public_hooks'));
	}

	public function define_public_hooks(){
		add_action('woocommerce_before_add_to_cart_button', array($this, 'render_bulk_discount_info'));
		//add_action('woocommerce_before_add_to_cart_button', array($this, 'render_price_table'));
		add_filter('woocommerce_get_price_html', array($this, 'thwdp_strikeout_on_product'), 10, 2);
		add_filter('woocommerce_add_cart_item_data', array($this, 'woo_add_cart_item_data'), 10, 4);
		add_action('woocommerce_before_calculate_totals', array($this, 'apply_each_product_discounts'), 10, 1);
		add_filter('woocommerce_cart_item_price', array($this, 'woo_cart_item_price'), 10, 3);
		add_filter('thwepo_before_calculate_totals_hook_priority', array($this, 'wepo_calculation_priority'));
		add_action( 'woocommerce_before_mini_cart', array($this,'thwdpf_force_cart_calculation' ));
		// add_action('woocommerce_add_to_cart', array($this, 'set_cart_totals_bkp'), 20);
		// add_action('woocommerce_cart_item_removed', array($this, 'set_cart_totals_bkp'));
		// add_action('woocommerce_cart_item_restored', array($this, 'set_cart_totals_bkp'));
		// add_action('woocommerce_after_cart_item_quantity_update', array($this, 'set_cart_totals_bkp'));
		// //add_action('woocommerce_after_calculate_totals', array($this, 'remove_cart_totals_bkp'), 10, 1);
	}

	public function wepo_calculation_priority(){
		$priority = apply_filters('thwdpf_wepo_calculation_hook_priority', 11);
		return $priority;
	}

	public function woo_add_cart_item_data($cart_item_data, $product_id=0, $variation_id=0, $quantity=0){
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		$original_price = $product->get_price();
		if($original_price){
			$cart_item_data[self::ITEM_KEY_DISCOUNTS] = array( 
				'initial_price' => $original_price);
		}

		return $cart_item_data;
	}

	public function apply_each_product_discounts($cart_object){
		foreach($cart_object->cart_contents as $key => $item) {
			if($key === $item['key']){
				if(THWDPF_Utils::is_valid_cart_item($item)){
					$product_id = $item['product_id'];
					$variation_id = $item['variation_id'];
					$quantity = $item['quantity'];
					$product = wc_get_product( $variation_id ? $variation_id : $product_id );
					$data = $this->get_product_discount_data($product, $quantity);
					$discount_rules = isset($data['discount_rules']) ? $data['discount_rules'] : false;
					$original_price = isset($data['original_price']) ? apply_filters('thwdpf_product_original_price',$data['original_price'], $item) : false;
					$discount_data = $this->prepare_discount_data($discount_rules, $item, $original_price);
					$final_price = isset($discount_data['final_price']) ? $discount_data['final_price'] : 0;
					
					$wepo_product_price = isset($item['thwepo-original_price']) ? $item['thwepo-original_price'] : '';
					if($wepo_product_price && ($wepo_product_price != $final_price)){
						$item['thwepo-original_price'] = $final_price;
					}
					if(is_numeric($final_price)){
						$item['data']->set_price($final_price);
						if(!$item['data']->get_sale_price()){
							$item['data']->set_sale_price($final_price);	
						}			
					}
				}
			}
		}
	}

	public function woo_cart_item_price($price, $cart_item, $cart_item_key){
		$product = isset($cart_item['data']) ? $cart_item['data'] : false;

		if($product && $product->is_on_sale()) {
			$reg_price = $this->get_product_price( $product, array('price' => $product->get_regular_price()) );
			$sale_price = $this->get_product_price( $product );

			$settings = THWDPF_Utils::get_advanced_settings();
			$strike_sale_price = THWDPF_Utils::get_setting_value($settings, 'strike_sale_price_on_cart');
			if(apply_filters( 'thwdpf_strikeout_sale_price_in_cart', $strike_sale_price )){
				if( $product->get_sale_price() > $product->get_price() ){
					$reg_price = $product->get_sale_price();
				}
			}
			// $wepo_price = 	apply_filters('thwdpf_wepo_price_data', array(), $cart_item);
			$wepo_price	=	$this->get_wepo_price_data($cart_item);
			$wepo_price = !empty($wepo_price) ? $wepo_price : false;

			if($wepo_price){
				$extra_cost = isset($wepo_price['price_data']['price_extra']) ? $wepo_price['price_data']['price_extra'] : 0;
				$original_price = isset($cart_item['thwdpf_discounts']['initial_price']) ? $cart_item['thwdpf_discounts']['initial_price'] : 0;
				$temp_reg_price = $original_price + $extra_cost;

				/* When this product have no discount  */

				if($temp_reg_price <= $product->get_price()){
					return $price;
				}

				$reg_price = $temp_reg_price;
			}	

			$price = wc_format_sale_price($reg_price, $sale_price) . $product->get_price_suffix();
			$price = apply_filters( 'thwdpf_cart_product_price_html', $price, $product, $cart_item);
		}

		return $price;
	}

	public function get_wepo_price_data($cart_item){
        $wepo_price_data = array();

        if(!THWDPF_Utils::check_thwepo_plugin_is_active()){
        	return $wepo_price_data;
        }

        if(empty($cart_item)){
            return $wepo_price_data;
        }

        if(class_exists('THWEPO_Price')){
			$wepo_price_Object = new THWEPO_Price();
			$function_exists = method_exists($wepo_price_Object, 'get_wepo_price_data_from_cart_item');
			if($function_exists){
				$wepo_price_data = $wepo_price_Object->get_wepo_price_data_from_cart_item($cart_item);
			}
	    }

        return $wepo_price_data;
    }

	private function get_product_price($product, $args = array()) {
		if ( WC()->cart->display_prices_including_tax() ) {
			$product_price = wc_get_price_including_tax( $product, $args );
		} else {
			$product_price = wc_get_price_excluding_tax( $product, $args );
		}
		return apply_filters( 'thwdpf_cart_product_price', wc_price( $product_price ), $product );
	}

	/*******************************************
	 ***** PREPARE DISCOUNT DATA - START *******
	 *******************************************/
	private function get_valid_discount_rules($product, $quantity=false){
		$valid_rules = array();
		$rules = THWDPF_Utils::get_product_rules();
		//$rules = $this->get_qualified_rules($rules, $prod_summary);

		if(is_array($rules)){
			$prod_summary = THWDPF_Utils::get_product_summary($product, $quantity);
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

					$restrictions = $rule->get_property('buy_restrictions');

					if(!$this->is_qualified_product($restrictions, $prod_summary)){
						continue;
					}

					if(!$this->is_qualified_category($restrictions, $prod_summary)){
						continue;
					}

					$valid_rules[$name] = $rule;
				}
			}
			//$valid_rules = $this->filter_by_apply_when($valid_rules);
		}

		return $valid_rules;
	}

	private function filter_discount_rules($rules, $product, $quantity=0){
		$valid_rules = array();

		if(is_array($rules)){
			$args = THWDPF_Utils::get_product_summary($product, $quantity);

			foreach ($rules as $name => $rule) {
				if(THWDPF_Utils::is_valid_enabled($rule)){
					$restrictions = $rule->get_property('buy_restrictions');

					if(!$this->is_qualified_others($restrictions, $args)){
						continue;
					}

					$valid_rules[$name] = $rule;
				}
			}

			$valid_rules = $this->filter_by_apply_when($valid_rules);
		}
		
		return $valid_rules;
	}
	
	private function get_product_discount_data($product, $quantity=1){
		$rules = $this->get_valid_discount_rules($product, $quantity);
		$original_price = $product->get_price();
		$discount_data = array();
		if($original_price){
			$discount_data = array(
				'discount_rules' => $rules,
				'original_price' => $original_price,
			);
		}
		return $discount_data;
	}

	private function prepare_discount_data($discount_rules, $item, $prod_price){
		$discount_data = array();
		$old_product_price = $prod_price;
		if(is_array($discount_rules)){
			$product = isset($item['data']) ? $item['data'] : false;
			$prod_qty = isset($item['quantity']) ? $item['quantity'] : false;

			if(is_numeric($prod_price) && is_numeric($prod_qty) && $prod_qty > 0){
				$total_discount = 0;
				$discount_rules = $this->filter_discount_rules($discount_rules, $product, $prod_qty);

				foreach ($discount_rules as $name => $rule) {
					$discount = $this->calculate_product_discount($rule, $prod_price, $prod_qty);
					$prod_price -= $discount;
			
					if($discount > 0){
						$total_discount += $discount;
					}
				}
				$final_price = $total_discount > 0 ? $old_product_price-$total_discount : $old_product_price;

				$discount_data['product_qty'] = $prod_qty;
				$discount_data['product_price'] = $old_product_price;
				$discount_data['total_discount'] = $total_discount;
				$discount_data['final_price'] = $final_price < 0 ? 0 : $final_price ;
			}
		}

		return empty($discount_data) ? false : $discount_data;
	}

	private function calculate_product_discount($rule, $prod_price, $prod_qty){
		$discount = 0;

		if(THWDPF_Utils::is_valid_enabled($rule)){
			$method = $rule->get_property('method');

			if($method === 'simple'){
				$disc_type = $rule->get_property('discount_type');
    			$disc_amount = $rule->get_property('discount_amount');

    			$discount = $this->calculate_discount($disc_type, $disc_amount, $prod_price);

			}else if($method === 'bulk'){
				$range_discounts = $rule->get_property('range_discounts');

				if(is_array($range_discounts)){
					foreach ($range_discounts as $key => $range) {
						$is_valid_range = false;
						
						$min_qty = isset($range['min_qty']) && is_numeric($range['min_qty']) ? $range['min_qty'] : 0;
						$max_qty = isset($range['max_qty']) && is_numeric($range['max_qty']) ? $range['max_qty'] : 0;

						if($max_qty > 0 && $min_qty >= 0){
							if($max_qty >= $min_qty){
								if($prod_qty >= $min_qty && $prod_qty <= $max_qty){
									$is_valid_range = true;
								}
							}
						}else if($max_qty > 0){
							if($prod_qty <= $max_qty){
								$is_valid_range = true;
							}
						}else if($min_qty >= 0){
							if($prod_qty >= $min_qty){
								$is_valid_range = true;
							}
						}

						if($is_valid_range){
							$disc_type = isset($range['discount_type']) ? $range['discount_type'] :'';
    						$disc_amount = isset($range['discount']) ? $range['discount'] : 0;

							$discount = $this->calculate_discount($disc_type, $disc_amount, $prod_price);
							break;
						}
					}
				}
			}
		}

		return $discount;
	}

	public function thwdpf_force_cart_calculation() {
		if ( is_cart() || is_checkout() ) {
			return;
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_totals();
	}
	
	/*public function set_cart_totals_bkp(){
		THWDPF_Utils::set_cart_totals_bkp(WC()->cart);
	}*/

	/*public function remove_cart_totals_bkp($cart){
		//THWDPF_Utils::set_cart_totals_bkp(WC()->cart, 'add');
		//THWDPF_Utils::remove_cart_totals_bkp($cart);
	}*/
	/*******************************************
	 ***** PREPARE DISCOUNT DATA - END *********
	 *******************************************/


	/*****************************************
	 ********* PRICE DISPLAY - START *********
	 *****************************************/
	// private function is_show_price_table($settings=false){
	// 	$show = THWDPF_Utils::get_setting_value($settings, 'show_price_table');
	// 	$show = apply_filters('thwdpf_show_price_table', $show);
	// 	$show = true; //TODO remove this line

	// 	return $show;
	// }

	public function render_bulk_discount_info(){
		$settings = THWDPF_Utils::get_advanced_settings();
		$show = THWDPF_Utils::get_setting_value($settings, 'enable_bulk_pricing_table');
		$show_caption = THWDPF_Utils::get_setting_value($settings, 'enable_caption_on_bulk_table');
		if(apply_filters('show_thwdp_bulk_pricing_table', $show)){	
			global $product;
			$rules = $this->get_valid_discount_rules($product, 1);

			foreach ($rules as $name => $rule) {
				if(THWDPF_Utils::is_valid_enabled($rule)){
					$method = $rule->get_property('method');

					if($method === 'bulk'){
						$range_discounts = $rule->get_property('range_discounts');

						if(is_array($range_discounts) && !empty($range_discounts)){
							$cell_html_qty = '';
							$cell_html_amt = '';
							$label = is_null($rule->get_property('label')) ? '' : $rule->get_property('label');
							foreach ($range_discounts as $key => $range) {
								$min_qty = isset($range['min_qty']) ? $range['min_qty'] : 0;
								$max_qty = isset($range['max_qty']) ? $range['max_qty'] : 0;
								/* Check maxqty greather than minqty or maxqty is blank(for inifinite qty) */
								if($max_qty >= $min_qty || $max_qty === ''){
									$dtype = isset($range['discount_type']) ? $range['discount_type'] :'';
			    					$damount = isset($range['discount']) ? $range['discount'] : 0;
			    					if($dtype != ''){
										$damount = $dtype === 'percentage' ? $damount.'%' : wc_price($damount);
			    					}	
			    					if($max_qty == $min_qty){
			    						$cell_html_qty .= '<td class="qty">'.$min_qty.'</td>';
			    					}
									elseif ($max_qty === '') {
										$cell_html_qty .= '<td class="qty">'.$min_qty.'+</td>';
									}
			    					else{
										$cell_html_qty .= '<td class="qty">'.$min_qty.' - '.$max_qty.'</td>';
									}
									$cell_html_amt .= '<td class="amt"> <span class="amount">'.$damount.'</span></td>';
								}
							}

							if($cell_html_qty && $cell_html_amt){
								if(apply_filters('show_thwdp_bulk_pricing_table_caption', $show_caption)){
								?>
									<h4> <?php echo esc_html($label); ?> </h4>
								<?php } ?>
								<div class="thwdpf-bulk-table-scroll">
									<table class="thwdpf-bulk-discount-info">
										<tbody>
										<tr class="row-qty">
											<th class="label"><?php esc_html_e('Quantity', 'discount-and-dynamic-pricing'); ?></th>
											<?php echo wp_kses_post($cell_html_qty); ?>
										</tr>
										<tr class="row-amt">
											<th class="label">
												<?php esc_html_e('Discount', 'discount-and-dynamic-pricing'); ?>
												<div style="width: max-content;"><?php esc_html_e('(Per Qty)', 'discount-and-dynamic-pricing'); ?></div></th>
											<?php echo wp_kses_post($cell_html_amt); ?>
										</tr>
									</tbody></table>
								</div>
								<?php
							}
						}
					}
				}
			}
		}	
	}

	public function thwdp_strikeout_on_product( $price_html, $product ) {
		if(is_admin()){
            return $price_html;
        }
		if(is_null($product)){
            return $price_html;
        }
        $settings = THWDPF_Utils::get_advanced_settings();
		$enable_strikeout = THWDPF_Utils::get_setting_value($settings, 'enable_strikeout');
        $strike_price = apply_filters('thwdp_strikeout_price_enable_filter', $enable_strikeout, $price_html, $product);
        if (!$strike_price) {
            return $price_html;
        }
        $on_product_page = THWDPF_Utils::get_setting_value($settings, 'on_product_page');
        if (is_product() && !$on_product_page ) {
            return $price_html;
        }
        $on_shop_page = THWDPF_Utils::get_setting_value($settings, 'on_shop_page');
        if (is_shop() && !$on_shop_page ) {
            return $price_html;
        }
        $on_category_page = THWDPF_Utils::get_setting_value($settings, 'on_product_category');
        if (is_product_category() && !$on_category_page ) {
            return $price_html;
        }
        $excluded_product_types = apply_filters('thwdp_excluded_product_type_for_strikout_price', array( 'subscription_variation', 'variable-subscription', 'grouped', 'composite'), $product);
        if (is_array($excluded_product_types) && !empty($excluded_product_types)) {
        	$prod_type =  $product->get_type();
        	if (in_array($prod_type, $excluded_product_types)) {
                return $price_html;
            }
            //Check the product object is from WC_Product Class
            if (is_a($product, 'WC_Product')) {
				$data = $this->get_product_discount_data($product, 1);
				$flag = 0;
				$res_prods = array();
				$allow_prods = array();
				$curency_symbol = empty(get_woocommerce_currency_symbol()) ? false : get_woocommerce_currency_symbol();
				if(is_array($data)){
					$discount_rules = isset($data['discount_rules']) ? $data['discount_rules'] : false;
					$old_product_price = isset($data['original_price']) ? $data['original_price'] : false;

					//To check bulk discount rules.
					$cart_obj = WC()->cart;
					if ( !is_null($cart_obj) && !$cart_obj->is_empty() ) {
						$product_id = $product->get_id();
						$product_types = array('simple','variation');
						$cart = WC()->cart->get_cart();
						foreach ( $cart as $cart_item_key => $cart_item ) {
							$cart_product_id = $cart_item['data']->get_id();
							$cart_item['quantity'] =  $cart_item['quantity'] > 0  ? $cart_item['quantity'] + 1 : $cart_item['quantity'];
							$cart_product_type = $cart_item['data']->get_type();

							if(in_array($prod_type, $product_types) ){
								if($product_id === $cart_product_id){
		   							$discount_data = $this->prepare_discount_data($discount_rules, $cart_item, $old_product_price);
									$final_price = isset($discount_data['final_price']) ? $discount_data['final_price'] : 0;
									if($final_price < $old_product_price){
										$price_html = wc_format_sale_price($old_product_price, $final_price) . $product->get_price_suffix();
									}
									return $price_html;
								}	
							}
							if($product_id === $cart_item['product_id'] && $product->is_type('variable')){
								$discount_data = $this->prepare_discount_data($discount_rules, $cart_item, $old_product_price);
								$discount_rules = $this->filter_discount_rules($discount_rules, $product, 1);
								foreach ($discount_rules as $name => $rule) {
									$method = $rule->get_property('method');
									if($method === 'bulk'){
										$flag = 1;
									}
									$restrictions = $rule->get_property('buy_restrictions');
									$res_prods += $restrictions->get_property('restricted_products') != NULL ? $restrictions->get_property('restricted_products') : array();
									$allow_prods += $restrictions->get_property('allowed_products') != NULL ? $restrictions->get_property('allowed_products') : array();
								}
								$total_discount = isset($discount_data['total_discount']) ? $discount_data['total_discount'] : 0;
								$prices = $product->get_variation_prices( true );
								$min_price = current( $prices['price'] );
								$max_price = end( $prices['price'] );
								$min_reg_price = current( $prices['regular_price'] );
								$max_reg_price = end( $prices['regular_price'] );
								if ( $min_price <= $max_price && $total_discount > 0) {
									if($min_price == $max_price){
										add_filter( 'woocommerce_show_variation_price','__return_true');
									}
									if($min_price > 0){
										$min_price = $total_discount > 0 ? $min_price-$total_discount : $min_price;
										$min_price = $min_price < 0 ? 0 : $min_price;
										$min_price = number_format($min_price, 2);
									}
									$variation_products = $product->get_children();
									$have_restricted_prods = (count(array_intersect($variation_products, $res_prods))) ? true : false;
									$have_few_allowed_prods = (count(array_intersect($variation_products, $allow_prods))) ? true : false;
									if($have_few_allowed_prods){
										if(count(array_intersect($variation_products, $allow_prods)) == count($variation_products)){
											$have_few_allowed_prods = false; //every variation allowed.
										}
									}
									if( $have_restricted_prods || $have_few_allowed_prods ){
										$flag = 1;
									}
									if($flag === 0){
										$max_price = $total_discount > 0 ? $max_price-$total_discount : $max_price;
									}
									$max_price = $max_price < 0 ? 0 : $max_price;
									$max_price = number_format($max_price, 2);
									if ($min_price == $max_price && $flag == 0){
										add_filter( 'woocommerce_show_variation_price','__return_false');
										$final_price = $total_discount > 0 ? $old_product_price-$total_discount : $old_product_price;
										$final_price = $final_price < 0 ? 0 : $final_price ;
										$final_price = number_format($final_price, 2);
										$old_product_price = number_format($old_product_price, 2);
										return $price_html = wc_format_sale_price($old_product_price, $final_price) . $product->get_price_suffix();
									}
									$separator = '<br>';
									$price_html = '<del aria-hidden="true">' . $price_html . '</del>' . $separator;
									// $price_html .= '<ins>'.wc_price( $min_price ) . $product->get_price_suffix().'  -  '.wc_price( $max_price ) . $product->get_price_suffix().'</ins>';
									$price_html .=  '<ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'.$curency_symbol.'</span>'.$min_price.'</bdi></span> - <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'.$curency_symbol.'</span>'.$max_price.'</bdi></span></ins>';
								}
	    						return $price_html;
							}	
						}
					}
					$total_discount = 0;
					$discount_rules = $this->filter_discount_rules($discount_rules, $product, 1);
					foreach ($discount_rules as $name => $rule) {
						$discount = $this->calculate_product_discount($rule, $old_product_price, 1);
						$restrictions = $rule->get_property('buy_restrictions');
						$res_prods += $restrictions->get_property('restricted_products') != NULL ? $restrictions->get_property('restricted_products') : array();
						$allow_prods += $restrictions->get_property('allowed_products') != NULL ? $restrictions->get_property('allowed_products') : array();
						if($discount > 0){
							$total_discount += $discount;
						}
					}
					$final_price = $total_discount > 0 ? $old_product_price-$total_discount : $old_product_price;
					$final_price = $final_price < 0 ? 0 : $final_price ;
					$final_price = number_format($final_price, 2);
					$old_product_price = number_format($old_product_price, 2);

					if( $product->is_type('variable') ){
						$prices = $product->get_variation_prices( true );
						$min_price = current( $prices['price'] );
						$max_price = end( $prices['price'] );
						$min_reg_price = current( $prices['regular_price'] );
						$max_reg_price = end( $prices['regular_price'] );
						if ( $min_price <= $max_price && $total_discount > 0) {
							if($min_price == $max_price){
								add_filter( 'woocommerce_show_variation_price','__return_true');
							}
							if($min_price > 0){
								$min_price = $total_discount > 0 ? $min_price-$total_discount : $min_price;
							$min_price = $min_price < 0 ? 0 : $min_price;
							$min_price = number_format($min_price, 2);
							}
							$variation_products = $product->get_children();
							$have_restricted_prods = (count(array_intersect($variation_products, $res_prods))) ? true : false;
							$have_few_allowed_prods = (count(array_intersect($variation_products, $allow_prods))) ? true : false;
							if($have_few_allowed_prods){
								if(count(array_intersect($variation_products, $allow_prods)) == count($variation_products)){
									$have_few_allowed_prods = false; //every variation allowed.
								}
							}
							if( $have_restricted_prods || $have_few_allowed_prods ){
								$flag = 1;
							}
							if($flag == 0){
								$max_price = $total_discount > 0 ? $max_price-$total_discount : $max_price;
							}	
							$max_price = $max_price < 0 ? 0 : $max_price;
							$max_price = number_format($max_price, 2);
							if ($min_price == $max_price && $flag == 0){
								add_filter( 'woocommerce_show_variation_price','__return_false');
								return $price_html = wc_format_sale_price($old_product_price, $final_price) . $product->get_price_suffix();
							}
							$separator = '<br>';
							$price_html = '<del aria-hidden="true">' . $price_html . '</del>' . $separator;
							$price_html .= '<ins>'.wc_price( $min_price ) . $product->get_price_suffix().'  -  '.wc_price( $max_price ) . $product->get_price_suffix().'</ins>';
							// $price_html .=  '<ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'.$curency_symbol.'</span>'.$min_price.'</bdi></span> - <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'.$curency_symbol.'</span>'.$max_price.'</bdi></span></ins>';
						}
    					return $price_html;
    				}	
					if($final_price < $old_product_price){
						// $curency_symbol = empty(get_woocommerce_currency_symbol()) ? false : get_woocommerce_currency_symbol();
						// $price_html = '<del aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'.$curency_symbol.'</span>'.$old_product_price.'</bdi></span></del> <ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">'.$curency_symbol.'</span>'.$final_price.'</bdi></span></ins>';
						$price_html = wc_format_sale_price($old_product_price, $final_price) . $product->get_price_suffix();
					}
				}
			}
		}
		return $price_html;
	}

	//Display Price Table
	/*public function render_price_table(){
		$settings = THWDPF_Utils::get_advanced_settings();

		if($this->is_show_price_table($settings)){
			global $product;
			$discount_data = $this->get_product_discount_data($product, 1);
			
			if(is_array($discount_data)){
				$price = $product->get_price_html();
				$product_price  = isset($discount_data['product_price']) ? $discount_data['product_price'] : false;
				$discount_final = isset($discount_data['discount_final']) ? $discount_data['discount_final'] : false;
				$final_price    = isset($discount_data['final_price']) ? $discount_data['final_price'] : false;

				$label_discount = THWDPF_Utils::get_setting_value($settings, 'pt_discount_label', 'Discount');
				$label_price    = THWDPF_Utils::get_setting_value($settings, 'pt_price_label', 'Product Price');
				$label_total    = THWDPF_Utils::get_setting_value($settings, 'pt_total_label', 'Total');
				$is_div_model   = THWDPF_Utils::get_setting_value($settings, 'use_div_model');

				if($discount_final > 0){
					if($is_div_model === 'yes'){
						?>
						<h2><?php esc_html_e('Price Table', 'discount-and-dynamic-pricing'); ?></h2>
						<div class="thwdpf-price-table" style="display: block">
							<div class="table-item extra-price">
								<div class="label"><?php esc_html_e($label_discount, 'discount-and-dynamic-pricing'); ?></div>
								<div class="value"><?php echo esc_html($discount_final); ?></div>
							</div>
							<div class="table-item product-price">
								<div class="label"><?php esc_html_e($label_price, 'discount-and-dynamic-pricing'); ?></div>
								<div class="value"><?php echo esc_html($price); ?></div>
							</div>
							<div class="table-item total-price">
								<div class="label"><?php esc_html_e($label_total, 'discount-and-dynamic-pricing'); ?></div>
								<div class="value" data-price="<?php echo esc_html($final_price); ?>"><?php echo esc_html($final_price); ?></div>
							</div>
						</div>
						<?php
					}else{
						?>
						<h2><?php esc_html_e('Price Table', 'discount-and-dynamic-pricing'); ?></h2>
						<table class="thwdpf-price-table" style="display: block">
							<tbody>
								<tr class="extra-price">
									<td class="label"><?php esc_html_e($label_discount, 'discount-and-dynamic-pricing'); ?></td>
									<td class="value"><?php echo esc_html($discount_final); ?></td>
								</tr>
								<tr class="product-price">
									<td class="label"><?php esc_html_e($label_price, 'discount-and-dynamic-pricing'); ?></td>
									<td class="value"><?php echo esc_html($price); ?></td>
								</tr>
								<tr class="total-price">
									<td class="label"><?php esc_html_e($label_total, 'discount-and-dynamic-pricing'); ?></td>
									<td class="value" data-price="<?php echo esc_html($final_price); ?>"><?php echo esc_html($final_price); ?></td>
								</tr>
							</tbody>
						</table>
						<?php
					}
				}
			}
		}
	}*/
	/****************************************
	 ********* PRICE DISPLAY - END **********
	 ****************************************/
}

endif;