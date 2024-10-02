<?php
/**
 * The plugin activation functionalities.
 *
 * @package    Discount-and-dynamic-pricing
 * @subpackage Discount-and-dynamic-pricing/includes
 * @link       https://themehigh.com
 * @since      1.0.0
 */
if(!defined('WPINC')){	die; }

if (!class_exists('THWDPF_Activator')) :

class THWDPF_Activator {
    public static function activate() {
        flush_rewrite_rules();
        if (!get_option('thwdpf_advanced_settings')){
				$settings = array(
					"enable_bulk_pricing_table" => '1',
					"enable_caption_on_bulk_table" => '1',
					"enable_strikeout" => '1',
					"on_product_page" => '1',
					"on_shop_page" => '1',
					"on_product_category" => '1',
					"strike_sale_price_on_cart" => '0'
				);
			add_option('thwdpf_advanced_settings', $settings);
	    }
    }
}

endif;
