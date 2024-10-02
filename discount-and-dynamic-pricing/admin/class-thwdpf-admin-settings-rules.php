<?php
/**
 * The base class for discount rules settings pages.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Admin_Settings_Rules')):

abstract class THWDPF_Admin_Settings_Rules extends THWDPF_Admin_Settings{
	protected $context = '';
	protected $rule_form = null;

	public function __construct() {
		parent::__construct();
	}

	public function init(){
		$this->render_page();
	}

	public function render_page(){
		$this->render_tabs();
		$this->output_content();
	}

	private function render_heading_row(){
		?>
		<tr class="heading">
			<th class="cell-sort sort"></th>
			<th class="cell-select">
				<input type="checkbox" name="select_all" onclick="thwdpfSelectAllRules(this)" >
			</th>
			<th class="cell-label"><?php esc_html_e('Name', 'discount-and-dynamic-pricing'); ?></th>
			<th class="cell-method"><?php esc_html_e('Discount Method', 'discount-and-dynamic-pricing'); ?></th>
			<th class="cell-startfrom"><?php esc_html_e('Start From', 'discount-and-dynamic-pricing'); ?></th>
			<th class="cell-endby"><?php esc_html_e('End By', 'discount-and-dynamic-pricing'); ?></th>
			<th class="cell-status"><?php esc_html_e('Enabled', 'discount-and-dynamic-pricing'); ?></th>
			<th class="cell-actions"><?php esc_html_e('Actions', 'discount-and-dynamic-pricing'); ?></th>
		</tr>
        <?php
	}
	
	private function render_actions_row($title=''){
		?>
		<tr class="actions">
	        <th colspan="5" class="title"><?php esc_html_e($title, 'discount-and-dynamic-pricing'); ?></th>
	        <th colspan="3">
	            <button type="button" name="add_rules" class="btn icon-btn btn-primary right" onclick="thwdpfOpenNewRuleForm(this, '<?php echo $this->context; ?>')"><i class="dashicons dashicons-insert"></i><?php esc_html_e('Add new rule', 'discount-and-dynamic-pricing') ?></button>

	            <button type="button" name="delete_rules" class="btn icon-btn mr-15 right" onclick="thwdpfDeleteSelectedRules(this, '<?php echo $this->context; ?>')" ><i class="dashicons dashicons-trash"></i><?php esc_html_e('Delete', 'discount-and-dynamic-pricing') ?></button>
	        </th>
	    </tr>  
    	<?php 
	}

	private function render_row($name, $rule, $i){
		if(THWDPF_Utils::is_valid_rule($rule)){
			$name = $rule->get_property('name');
			$label = $rule->get_property('label');
			$method = $rule->get_property('method');
			$enabled = $rule->get_property('enabled');
			$schedule = $rule->get_property('schedule');

			$methods = THWDPF_Admin_Form::get_discount_methods();
			$method = isset($methods[$method]) ? $methods[$method] : $methods['simple'];

			$datetime_start = '--';
			$datetime_end = '--';
			if(is_array($schedule)){
				foreach ($schedule as $value) {
					$start_date = isset($value['start_date']) ? $value['start_date'] : false;
					$start_time = isset($value['start_time']) ? $value['start_time'] : false;
					$end_date = isset($value['end_date']) ? $value['end_date'] : false;
					$end_time = isset($value['end_time']) ? $value['end_time'] : false;

					$datetime_start = THWDPF_Utils::get_datetime_display($start_date, $start_time, 'start');
					$datetime_end = THWDPF_Utils::get_datetime_display($end_date, $end_time, 'end');
				}
			}

			$buy_restrictions = $rule->get_property('buy_restrictions');

			$rule_json = htmlspecialchars($this->get_property_set_json($rule));
			$restriction_json = htmlspecialchars($buy_restrictions->get_property('restrictions_other_json'));

			$checked = $enabled === 'yes' ? 'checked="checked"' : '';

			?>
			<tr class="row_<?php echo $i; echo $enabled ? '' : ' thpladmin-disabled' ?>">
				<td class="cell-sort sort ui-sortable-handle">
					<input type="hidden" name="i_name[<?php echo $i; ?>]" class="i_name" value="<?php echo $name; ?>" >
					<input type="hidden" name="i_priority[<?php echo $i; ?>]" class="i_priority" value="<?php echo $i; ?>" >
					<input type="hidden" name="r_props[<?php echo $i; ?>]" class="r_props" value='<?php echo $rule_json; ?>' >
	            	<input type="hidden" name="r_restrictions[<?php echo $i; ?>]" class="r_restrictions" value="<?php echo $restriction_json; ?>" >
				</td>
	            <td class="cell-select">
	            	<input type="checkbox" name="select_rule[]" value="<?php echo $name; ?>">
	            </td>
	            <td class="cell-label"><?php esc_html_e($label, 'discount-and-dynamic-pricing'); ?></td>
	            <td class="cell-method"><?php echo esc_html($method); ?></td>
	            <td class="cell-startfrom"><?php echo esc_html($datetime_start); ?></td>
	            <td class="cell-endby"><?php echo esc_html($datetime_end); ?></td>
	            <td class="cell-status">
	            	<label class="switch">
					  <input type="checkbox" name="i_enabled_<?php echo $name; ?>" value="yes" <?php echo $checked; ?> onchange="thwdpfEnableDisableRule(this, '<?php echo $name; ?>')">
					  <span class="slider round"></span>
					</label>
	            </td>
	            <td class="cell-actions">
					<span class="dashicons dashicons-edit tips" data-tip="<?php esc_html_e('Edit', 'discount-and-dynamic-pricing'); ?>" onclick="thwdpfOpenEditRuleForm(this)"></span>
					<span class="dashicons dashicons-admin-page tips" data-tip="<?php esc_html_e('Duplicate', 'discount-and-dynamic-pricing'); ?>" onclick="thwdpfOpenCopyRuleForm(this)"></span>
					<span class="dashicons dashicons-trash tips" data-tip="<?php esc_html_e('Delete', 'discount-and-dynamic-pricing'); ?>" onclick="thwdpfDeleteRule(this, '<?php echo $name; ?>')"></span>
				</td>
	    	</tr>
			<?php
		}
	}

	public function output_content() {
		$action = isset($_POST['i_action']) ? sanitize_key($_POST['i_action']) : false;

		if($action === 'new' || $action === 'copy')
			echo $this->add_discount_rule($action);	
			
		if($action === 'edit')
			echo $this->edit_discount_rule($action);

		if($action === 'enable_disable_rule')
			echo $this->edit_discount_rule_status();

		if($action === 'delete_rules')
			echo $this->delete_selected_discount_rules();

		if($action === 'auto_save')
			echo $this->auto_save_discount_rules();

		$discount_rules = $this->get_discount_rules();

		?>
        <div class="content-wrap">
		<form method="post" id="thwdpf_discount_rules_form" action="">
			<input type="hidden" name="i_action" value="" >
			<input type="hidden" name="i_enable_rname" value="" >

        	<table id="thwdpf_discount_rules" class="wc_gateways widefat" cellspacing="0">
				<thead>
                	<?php $this->render_actions_row(__('Recently added rules','discount-and-dynamic-pricing')); ?>
                	<?php $this->render_heading_row(); ?>					
				</thead>
                <tfoot>
                	<?php $this->render_heading_row(); ?>
					<?php $this->render_actions_row(); ?>
				</tfoot>
				<tbody class="ui-sortable">
				<?php
					if(!empty($discount_rules)){
						$i=0;
						foreach ($discount_rules as $name => $rule) {
							$this->render_row($name, $rule, $i);
							$i++;
						}
					}else{
						?>
						<tr class="empty-msg-row">
							<td colspan="8">
								<?php esc_html_e('No discount rules found. Click on the "Add new rule" button to create new discount rule.','discount-and-dynamic-pricing'); ?>
							</td>
						</tr>
						<?php
					}
	            ?>
            	</tbody>
			</table> 
        </form>
        <?php
        $this->rule_form->output_rule_forms();
	}

	private function get_property_set_json($rule){
		if(THWDPF_Utils::is_valid_rule($rule)){
			$props_set = array();

			$restrictions = $rule->get_property('buy_restrictions');
			$props_rule = $this->rule_form->get_rule_form_props();
			$props_restriction = $this->rule_form->get_restriction_form_props();

			$rule_set = $this->prepare_property_set($rule, $props_rule);
			$restriction_set = $this->prepare_property_set($restrictions, $props_restriction);
		
			$props_set = array_merge($rule_set, $restriction_set);
			$props_set['priority'] = $rule->get_property('priority');
			$props_set['schedule'] = $rule->get_property('schedule');
			$props_set['range_discounts'] = $rule->get_property('range_discounts');
			
			return json_encode($props_set);
		}else{
			return '';
		}
	}

	private function prepare_property_set($obj, $props){
		$props_set = array();

		if(is_array($props)){
			foreach($props as $pname => $property){
				$pvalue = $obj->get_property($pname);
				$pvalue = is_array($pvalue) ? implode(',', $pvalue) : $pvalue;
				$pvalue = esc_attr($pvalue); //TODO for javascript safe data
				
				// if($property['type'] == 'checkbox'){
				// 	$pvalue = $pvalue ? 1 : 0;
				// }
				$props_set[$pname] = $pvalue;
			}
		}

		return $props_set;
	}

	abstract public function prepare_rule_from_posted($posted, $action);

	private function add_discount_rule($action) {
		try {
			$rule = $this->prepare_rule_from_posted($_POST, $action);
			$result = $this->update_discount_rule($rule);

			if($result == true){
				return $this->print_notices( __('New discount rule added successfully.','discount-and-dynamic-pricing'), 'updated', true);
			}else{
				return $this->print_notices( __('Discount rule not added due to an error.','discount-and-dynamic-pricing'), 'error', true);
			}
		} catch (Exception $e) {
			return $this->print_notices( __('Discount rule not added due to an error.','discount-and-dynamic-pricing'), 'error', true);
		}
	}

	private function edit_discount_rule($action) {
		try {
			$rule = $this->prepare_rule_from_posted($_POST, $action);
			$result = $this->update_discount_rule($rule);

			if($result == true){
				return $this->print_notices( __('Discount rule updated successfully.','discount-and-dynamic-pricing'), 'updated', true);
			}else{
				return $this->print_notices( __('Discount rule not updated due to an error.','discount-and-dynamic-pricing'), 'error', true);
			}
		} catch (Exception $e) {
			return $this->print_notices( __('Discount rule not updated due to an error.','discount-and-dynamic-pricing'), 'error', true);
		}
	}

	private function edit_discount_rule_status(){
		try {
			$rname = $this->get_posted_value($_POST, 'enable_rname', 'key');
			//$rname = isset($_POST['i_enable_rname']) ? sanitize_key($_POST['i_enable_rname']) : false;
			
			if($rname){
				$enable_fname = 'enabled_'.$rname;
				$enabled =  $this->get_posted_value($_POST, $enable_fname, 'key');
				//$enabled = isset($_POST[$enable_fname]) ? sanitize_key($_POST[$enable_fname]) : '';
				$enabled = $enabled == 1 || $enabled === 'yes' ? 'yes' : '';
				$result = $this->update_discount_rule_status($rname, $enabled);

				if($result == true){
					return $this->print_notices( __('Discount rule updated successfully.','discount-and-dynamic-pricing'), 'updated', true);
				}else{
					return $this->print_notices( __('Discount rule not updated due to an error.','discount-and-dynamic-pricing'), 'error', true);
				}
			}
		} catch (Exception $e) {
			return $this->print_notices( __('Discount rule not updated due to an error.','discount-and-dynamic-pricing'), 'error', true);
		}
	}

	private function auto_save_discount_rules(){
		try {
			$r_names = !empty( $_POST['i_name'] ) ? $_POST['i_name'] : array();
			$r_priorities = !empty( $_POST['i_priority'] ) ? $_POST['i_priority'] : array();

			if(is_array($r_names)){
				$priority_map = array();

				$max = max( array_map( 'absint', array_keys( $r_names ) ) );
				for($i = 0; $i <= $max; $i++) {
					$name = $r_names[$i];
					$priority = $r_priorities[$i];

					$priority_map[$name] = $priority;
				}

				$rules = $this->get_discount_rules();
				foreach ($rules as $name => &$rule) {
					$priority = isset($priority_map[$name]) ? $priority_map[$name] : '';
					$rule->set_property('priority', $priority);
				}

				$this->save_discount_rules($rules);
			}
		} catch (Exception $e) {
			return $this->print_notices( __('Discount rule not updated due to an error.','discount-and-dynamic-pricing'), 'error', true);
		}
	}

	private function delete_selected_discount_rules(){
		try {
			$selected = isset($_POST['select_rule']) ? array_map('sanitize_key', $_POST['select_rule']) : false;
			$result = $this->delete_discount_rules($selected);

			if($result == true){
				return $this->print_notices( __('Discount rule(s) deleted successfully.','discount-and-dynamic-pricing'), 'updated', true);
			}else{
				return $this->print_notices( __('Discount rule(s) not deleted due to an error.','discount-and-dynamic-pricing'), 'error', true);
			}
		} catch (Exception $e) {
			return $this->print_notices( __('Discount rule(s) not deleted due to an error.','discount-and-dynamic-pricing'), 'error', true);
		}
	}

	public function get_posted_value($posted, $name, $type='', $cb_val=false){
		$value = '';
		$iname = $name ? 'i_'.$name : false;

		if($iname){
			if($type === 'checkbox'){
				if($cb_val){
					$value = isset($posted[$iname]) ? sanitize_key($posted[$iname]) : 0;
					$value = $value == 1 || $value === $cb_val ? $cb_val : '';
				}else{
					$value = isset($posted[$iname]) ? $posted[$iname] : 0;
				}
			}else if(isset($posted[$iname])){
				if($type === 'textarea'){
					$value = sanitize_textarea_field($posted[$iname]);

				}else if($type === 'email'){
					$value = sanitize_email($posted[$iname]);

				}else if($type === 'key'){
					$value = sanitize_key($posted[$iname]);

				}else if($type === 'select'){
					$value = array_map('sanitize_key', $posted[$iname]);

				}else if($type === 'json'){
					$value = trim(stripslashes($posted[$iname]));

				}else{
					$value = sanitize_text_field($posted[$iname]);
				}

				$value = is_string($value) ? trim(stripslashes($value)) : $value;
			}
		}

		return $value;
	}

	public function prepare_dr_name_from_posted($posted, $label, $action=''){
		$name = $this->get_posted_value($posted, 'name', 'key');

		if($action === 'new' || empty($name)){
			$name  = $this->context === 'cart' ? 'thdpcart' : 'thdpprod';
			$name .= mt_rand(1000,9999);
			$name .= str_replace('-', '_', sanitize_title($label));
		}
		return $name;
	}

	public function prepare_dr_schedule_from_posted($posted){
		$schedule_set = array();

		$start_date = $this->get_posted_value($posted, 'start_date', 'text');
		$end_date   = $this->get_posted_value($posted, 'end_date', 'text');
		$start_time = $this->get_posted_value($posted, 'start_time', 'text');
		$end_time   = $this->get_posted_value($posted, 'end_time', 'text');

		$schedule = array(
			'start_date' => $start_date,
			'start_time' => $start_time,
			'end_date'   => $end_date,
			'end_time'   => $end_time,
		);
		$schedule_set[] = $schedule;

		return $schedule_set;
	}

	public function prepare_range_disconts($range_discounts_json){
		$range_discounts_json = rawurldecode($range_discounts_json);
		$range_discounts = json_decode($range_discounts_json, true);

		return $range_discounts;
	}

	public function prepare_restrictions($restrictions_json){
		$restrictions = array();

		if(!empty($restrictions_json)){
			$restrictions_json = urldecode($restrictions_json);
			$rule_sets = json_decode($restrictions_json, true);
				
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
												$condition_obj->set_property('operator', isset($condition['operator']) ? $condition['operator'] : '');
												$condition_obj->set_property('operand', isset($condition['operand']) ? $condition['operand'] : '');
												
												//$condition_obj->set_property('value', isset($condition['value']) ? $condition['value'] : '');
												
												$condition_set_obj->add_condition($condition_obj);
											}
										}										
										$condition_rule_obj->add_condition_set($condition_set_obj);	
									}								
								}
								$condition_rule_set_obj->add_condition_rule($condition_rule_obj);
							}
						}
						$restrictions[] = $condition_rule_set_obj;
					}
				}	
			}
		}

		return $restrictions;
	}

	/****************************************
	 *----- SAVE & UPDATE RULES - START -----
	 ****************************************/
	private function save_product_rules($rules){
		$result = update_option(THWDPF_Utils::OPTION_KEY_DISCOUNT_RULES_PRODUCT, $rules);
		return $result;
	}

	private function save_cart_rules($rules){
		$result = update_option(THWDPF_Utils::OPTION_KEY_DISCOUNT_RULES_CART, $rules);
		return $result;
	}

	private function save_discount_rules($rules){
		$rules = THWDPF_Utils::sort_rules($rules);

		if($this->context === 'cart'){
			return $this->save_cart_rules($rules);
		}else{
			return $this->save_product_rules($rules);
		}
	}

	private function get_discount_rules(){
		if($this->context === 'cart'){
			return THWDPF_Utils::get_cart_rules();
		}else{
			return THWDPF_Utils::get_product_rules();
		}
	}

	private function get_discount_rule($name, $rules=false){
		if($this->context === 'cart'){
			return THWDPF_Utils::get_cart_rule($name, $rules);
		}else{
			return THWDPF_Utils::get_product_rule($name, $rules);
		}
	}

	public function update_discount_rule($rule){
		if(THWDPF_Utils::is_valid_rule($rule)){	
			$rules = $this->get_discount_rules();
			$rules[$rule->name] = $rule;
			
			return $this->save_discount_rules($rules);
		}
		return false;
	}

	public function update_discount_rule_status($name, $enabled){
		$rule = $this->get_discount_rule($name);

		if(THWDPF_Utils::is_valid_rule($rule)){
			$rule->set_property('enabled', $enabled);

			return $this->update_discount_rule($rule);
		}
		return false;
	}

	public function delete_discount_rules($rnames){
		if(is_array($rnames) && !empty($rnames)){
			$rules = $this->get_discount_rules();

			if(!empty($rules)){
				foreach ($rules as $name => $rule) {
					if(in_array($name, $rnames)){
						unset($rules[$name]);
					}
				}
				return $this->save_discount_rules($rules);
			}
		}
		return false;
	}
	/****************************************
	 *----- SAVE & UPDATE RULES - END -------
	 ****************************************/
}

endif;
