<?php
/**
 * The discount rule restriction class handles individual discount rule restrictions data.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/includes
 */

if(!defined('WPINC')){  die; }

if(!class_exists('THWDPF_Rule_Restriction')):

class THWDPF_Rule_Restriction{
    public $allowed_products = array();
    public $restricted_products = array();

    public $allowed_cats = array();
    public $restricted_cats = array();

    public $restrictions_other = array();
    public $restrictions_other_json = '';
    
    
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