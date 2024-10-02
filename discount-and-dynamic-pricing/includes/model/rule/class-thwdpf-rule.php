<?php
/**
 * The discount rule class handles individual discount rule data.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){  die; }

if(!class_exists('THWDPF_Rule')):

class THWDPF_Rule{
    public $context = '';

    /* Discount Props */
    public $name = '';
    public $label = '';
    public $priority = '';
    public $enabled = 'yes';
    public $apply_when = '';

    public $method = '';
    public $discount_type = '';
    public $discount_amount = '';
    public $range_discounts = '';

    public $schedule = array();

    public $need_login = '';
    public $allowed_roles = array();

    /* Restrictions */
    public $buy_restrictions = array();
    public $get_restrictions = array();


    /**
     * Set property.
     */
    public function set_property($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
    
    /**
     * Get property.
     */
    public function get_property($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }else {
            return '';
        }
    }
}

endif;
