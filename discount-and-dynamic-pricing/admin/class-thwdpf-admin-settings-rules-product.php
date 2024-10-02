<?php
/**
 * Dynamic Pricing settings page - Product Rules
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Admin_Settings_Rules_Product')):

class THWDPF_Admin_Settings_Rules_Product extends THWDPF_Admin_Settings_Rules{
	protected static $_instance = null;

	public function __construct() {
		parent::__construct();
		$this->page_id = 'product_rules';
		$this->context = 'product';

		$this->rule_form = new THWDPF_Admin_Form_Product_Rule();
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function prepare_rule_from_posted($posted, $action='new'){
		$label = $this->get_posted_value($posted, 'label', 'text');
		$name  = $this->prepare_dr_name_from_posted($posted, $label, $action);
		$priority = $this->get_posted_value($posted, 'priority', 'text');
		$enabled  = $this->get_posted_value($posted, 'enabled', 'checkbox', 'yes');
		$apply_when = $this->get_posted_value($posted, 'apply_when', 'text');

		$method = $this->get_posted_value($posted, 'method', 'text');
		$discount_type  = $this->get_posted_value($posted, 'discount_type', 'text');
		$discount_amount = $this->get_posted_value($posted, 'discount_amount', 'text');

		$range_discounts_json = $this->get_posted_value($posted, 'range_discounts', 'json');
		$range_discounts = $this->prepare_range_disconts($range_discounts_json);

		$schedule = $this->prepare_dr_schedule_from_posted($posted);
		$need_login = $this->get_posted_value($posted, 'need_login', 'checkbox', 'yes');
		$allowed_roles = $this->get_posted_value($posted, 'allowed_roles', 'select');

		$restrictions = $this->prepare_restrictions_from_posted($posted, 'buy');
			
		$rule = new THWDPF_Rule();
		$rule->set_property('context', $this->context);
		$rule->set_property('name', $name);
		$rule->set_property('label', $label);
		$rule->set_property('priority', $priority);
		$rule->set_property('enabled', $enabled);
		$rule->set_property('apply_when', $apply_when);

		$rule->set_property('method', $method);
		$rule->set_property('discount_type', $discount_type);
		$rule->set_property('discount_amount', $discount_amount);

		$rule->set_property('range_discounts', $range_discounts);

		$rule->set_property('schedule', $schedule);
		$rule->set_property('need_login', $need_login);
		$rule->set_property('allowed_roles', $allowed_roles);

		$rule->set_property('buy_restrictions', $restrictions);

		return $rule;
	}

	private function prepare_restrictions_from_posted($posted, $type=''){
		$allowed_products = $this->get_posted_value($posted, 'allowed_products', 'select');
		$restricted_products = $this->get_posted_value($posted, 'restricted_products', 'select');

		$allowed_cats = $this->get_posted_value($posted, 'allowed_cats', 'select');
		$restricted_cats = $this->get_posted_value($posted, 'restricted_cats', 'select');

		$restrictions_other_json = $this->get_posted_value($posted, 'restrictions_other', 'json');
		$restrictions_other = $this->prepare_restrictions($restrictions_other_json);

		$restriction = new THWDPF_Rule_Restriction();
		$restriction->set_property('allowed_products', $allowed_products);
		$restriction->set_property('restricted_products', $restricted_products);

		$restriction->set_property('allowed_cats', $allowed_cats);
		$restriction->set_property('restricted_cats', $restricted_cats);

		$restriction->set_property('restrictions_other', $restrictions_other);
		$restriction->set_property('restrictions_other_json', $restrictions_other_json);

		return $restriction;
	}
}

endif;
