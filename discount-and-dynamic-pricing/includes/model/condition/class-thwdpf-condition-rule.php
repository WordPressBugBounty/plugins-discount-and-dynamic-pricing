<?php
/**
 * Dynamic price rule class.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/includes/rules
 */

if(!defined('WPINC')){ die; }

if(!class_exists('THWDPF_Condition_Rule')):

class THWDPF_Condition_Rule {
	const LOGIC_AND = 'and';
	const LOGIC_OR  = 'or';
	
	public $logic = self::LOGIC_OR;
	public $condition_sets = array();
	
	public function __construct() {
		
	}
	
	public function add_condition_set($condition_set){
		if(isset($condition_set) && $condition_set instanceof THWDPF_Condition_Set){
			$this->condition_sets[] = $condition_set;
		} 
	}
	
	public function set_logic($logic){
		$this->logic = $logic;
	}	
	public function get_logic(){
		return $this->logic;
	}
		
	public function set_condition_sets($condition_sets){
		$this->condition_sets = $condition_sets;
	}	
	public function get_condition_sets(){
		return $this->condition_sets; 
	}	
}

endif;