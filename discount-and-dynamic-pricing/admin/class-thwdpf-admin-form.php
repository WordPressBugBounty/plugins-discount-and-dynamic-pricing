<?php
/**
 * The base class for the discount rule form.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){ die; }

if(!class_exists('THWDPF_Admin_Form')):

class THWDPF_Admin_Form {
	protected $rule_props = array();
	protected $restriction_props = array();

	protected $cell_props = array();
	protected $cell_props_TA = array();
	protected $cell_props_CP = array();
	protected $cell_props_CB = array();
	protected $cell_props_CBS = array();
	protected $cell_props_CBL = array();
	protected $cell_props_DTD = array();
	protected $cell_props_DTT = array();

	public function __construct() {
		$this->init_constants();
	}

	private function init_constants(){
		$this->cell_props = array(
			'label_cell_props' => 'class="label"',
			'input_cell_props' => 'class="field"',
			'input_width' => '270px',
		);
		$this->cell_props_TA = array(
			'label_cell_props' => 'class="label"',
			'input_cell_props' => 'class="field"',
			'input_width' => '270px',
			'rows' => 10,
			'cols' => 29,
		);
		$this->cell_props_CP = array(
			'label_cell_props' => 'class="label"',
			'input_cell_props' => 'class="field"',
			'input_width' => '233px',
		);

		$this->cell_props_CB = array(
			'label_props' => 'style="margin-right: 40px;"',
		);
		$this->cell_props_CBS = array(
			'label_props' => 'style="margin-right: 15px;"',
		);
		$this->cell_props_CBL = array(
			'label_props' => 'style="margin-right: 52px;"',
		);

		$this->cell_props_DTD = array(
			'input_width' => '133px',
		);
		$this->cell_props_DTT = array(
			'input_width' => '132px',
		);
	}

	public static function get_discount_methods(){
		return array(
			'simple'  => __('Simple discount','discount-and-dynamic-pricing'),
			'bulk'    => __('Bulk discount','discount-and-dynamic-pricing'),
		);
	}

	public function get_discount_types(){
		return array(
			'fixed'      => __('Fixed discount','discount-and-dynamic-pricing'),
			'percentage' => __('Percentage discount','discount-and-dynamic-pricing'),
		);
	}

	public function get_discount_usages(){
		return array(
			'apply_this_only' => __('Apply this and discard others','discount-and-dynamic-pricing'),
			'apply_if_none'   => __('Apply when no other discounts','discount-and-dynamic-pricing'),
			'apply_this_also' => __('Apply with other discounts','discount-and-dynamic-pricing'),
		);
	}

	public function get_discount_rule_form_props(){
		$discount_types = $this->get_discount_types();
		$apply_when = $this->get_discount_usages();
		$user_roles = THWDPF_Admin_Utils::get_all_user_roles();
		$methods = self::get_discount_methods();
				
		return array(
			'name' => array('type'=>'hidden', 'name'=>'name', 'label'=>__('Name','discount-and-dynamic-pricing'), 'required'=>1),
			'label' => array('type'=>'text', 'name'=>'label', 'label'=>__('Label','discount-and-dynamic-pricing'), 'required'=>1),
			'method' => array('type'=>'select', 'name'=>'method', 'label'=>__('Method','discount-and-dynamic-pricing'), 'required'=>1, 'options'=>$methods, 'onchange'=>'thwdpfMethodChangeListener(this)'),
			'discount_type' => array('type'=>'select', 'name'=>'discount_type', 'label'=>__('Discount Type','discount-and-dynamic-pricing'), 'required'=>1, 'options'=>$discount_types),
			'discount_amount' => array('type'=>'text', 'name'=>'discount_amount', 'label'=>__('Discount Amount','discount-and-dynamic-pricing'), 'required'=>1),

			'apply_when' => array('type'=>'select', 'name'=>'apply_when', 'label'=>__('Apply When','discount-and-dynamic-pricing'), 'required'=>1, 'options'=>$apply_when),
			'enabled' => array('type'=>'checkbox', 'name'=>'enabled', 'description'=>__('Enabled','discount-and-dynamic-pricing'), 'value'=>'yes', 'checked'=>1),

			'start_date' => array('type'=>'datepicker', 'name'=>'start_date', 'label'=>__('Start Date','discount-and-dynamic-pricing')),
			'start_time' => array('type'=>'timepicker', 'name'=>'start_time', 'label'=>__('Start Time','discount-and-dynamic-pricing')),
			'end_date' => array('type'=>'datepicker', 'name'=>'end_date', 'label'=>__('End Date','discount-and-dynamic-pricing')),
			'end_time' => array('type'=>'timepicker', 'name'=>'end_time', 'label'=>__('End Time','discount-and-dynamic-pricing')),

			'need_login' => array('type'=>'checkbox', 'name'=>'need_login', 'label'=>__('Need login?','discount-and-dynamic-pricing'), 'description'=>__('Check this box if restricted to logged in users.','discount-and-dynamic-pricing'), 'value'=>'yes', 'checked'=>0, 'onchange'=>'thwdpfNeedLoginChangeListener(this)'),
			'allowed_roles' => array('type'=>'multiselect', 'name'=>'allowed_roles', 'label'=>__('Allowed Roles','discount-and-dynamic-pricing'), 'placeholder'=>__('Select roles','discount-and-dynamic-pricing'), 'options'=>$user_roles, 'multiple'=>1),
		);
	}

	public function get_discount_rule_restriction_form_props(){
		$categories = THWDPF_Admin_Utils::get_all_product_categories();
				
		return array(
			'allowed_products' => array('type'=>'product', 'name'=>'allowed_products', 'label'=>__('Allowed Products','discount-and-dynamic-pricing'), 'placeholder'=>__('Search for a product…','discount-and-dynamic-pricing')),
			'restricted_products' => array('type'=>'product', 'name'=>'restricted_products', 'label'=>__('Restricted Products','discount-and-dynamic-pricing'), 'placeholder'=>__('Search for a product…','discount-and-dynamic-pricing')),

			'allowed_cats' => array('type'=>'term', 'name'=>'allowed_cats', 'label'=>__('Allowed Categories','discount-and-dynamic-pricing'), 'placeholder'=>__('Any category','discount-and-dynamic-pricing'), 'options'=>$categories, 'enhanced'=>1),
			'restricted_cats' => array('type'=>'term', 'name'=>'restricted_cats', 'label'=>__('Restricted Categories','discount-and-dynamic-pricing'), 'placeholder'=>__('No categories','discount-and-dynamic-pricing'), 'options'=>$categories, 'enhanced'=>1),
		);
	}

	public function render_form_field_element($field, $args=array(), $render_cell=true){
		if($field && is_array($field)){
			$defaults = array(
			    'label_cell_props' => 'class="label"',
				'input_cell_props' => 'class="field"',
				'label_cell_colspan' => '',
				'input_cell_colspan' => '',
			);
			$args = wp_parse_args( $args, $defaults );

			$ftype    = isset($field['type']) ? $field['type'] : 'text';
			$flabel   = isset($field['label']) ? $field['label'] : '';
			$sublabel = isset($field['sub_label']) ? $field['sub_label'] : '';
			$tooltip  = isset($field['hint_text']) ? $field['hint_text'] : '';

			$field_html = '';

			if($ftype == 'text'){
				$field_html = $this->render_form_field_element_inputtext($field, $args);

			}else if($ftype == 'textarea'){
				$field_html = $this->render_form_field_element_textarea($field, $args);

			}else if($ftype == 'select'){
				$field_html = $this->render_form_field_element_select($field, $args);

			}else if($ftype == 'multiselect'){
				$field_html = $this->render_form_field_element_multiselect($field, $args);

			}else if($ftype == 'colorpicker'){
				$field_html = $this->render_form_field_element_colorpicker($field, $args);

			}else if($ftype == 'checkbox'){
				$field_html = $this->render_form_field_element_checkbox($field, $args, $render_cell);
				//$flabel 	= '&nbsp;';

			}else if($ftype == 'number'){
				$field_html = $this->render_form_field_element_number($field, $args);

			}else if($ftype == 'datepicker'){
				$field_html = $this->render_form_field_element_datepicker($field, $args);

			}else if($ftype == 'timepicker'){
				$field_html = $this->render_form_field_element_timepicker($field, $args);

			}else if($ftype == 'datetime'){
				$field_html = $this->render_form_field_element_datetime($field, $args);

			}else if($ftype == 'product'){
				$field_html = $this->render_form_field_element_product($field, $args);

			}else if($ftype == 'term'){
				$field_html = $this->render_form_field_element_term($field, $args);
			}

			if($render_cell){
				$required = isset($field['required']) ? $field['required'] : false;
				$label_html = $this->prepare_form_field_label($flabel, $sublabel, $required);

				$label_cell_props = !empty($args['label_cell_props']) ? $args['label_cell_props'] : '';
				$input_cell_props = !empty($args['input_cell_props']) ? $args['input_cell_props'] : '';

				?>
				<td <?php echo $label_cell_props; ?> ><?php echo $label_html; ?></td>
				<?php $this->render_form_fragment_tooltip($tooltip); ?>
				<td <?php echo $input_cell_props; ?> ><?php echo $field_html; ?></td>
				<?php
			}else{
				echo $field_html;
			}
		}
	}

	private function prepare_form_field_label($label, $sublabel='', $required=false){
		$label = __($label, 'discount-and-dynamic-pricing');

		$required_html = $required ? '<abbr class="required" title="required">*</abbr>' : '';
		$label_html = $label.$required_html;

		if(!empty($sublabel)){
			$sublabel = __($sublabel, 'discount-and-dynamic-pricing');
			$label_html .= '<br/><span class="thpladmin-subtitle">'. $sub_label .'</span>';
		}
		return $label_html;
	}

	private function prepare_form_field_props($field, $args = array()){
		$field_props = '';

		$defaults = array(
		    'input_width' => '',
		    'input_class' => array(),
			'input_name_prefix' => 'i_',
			'input_name_suffix' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$ftype = isset($field['type']) ? $field['type'] : 'text';
		$input_class = is_array($args['input_class']) ? $args['input_class'] : array();
		$enhanced_select = isset($field['enhanced']) ? $field['enhanced'] : false;

		$multiple = isset($args['multiple']) ? $args['multiple'] : false;
		if($multiple || $ftype == 'multiselect' || $ftype == 'multiselect_grouped'){
			$multiple = true;
		}

		if($multiple){
			$args['input_name_suffix'] = $args['input_name_suffix'].'[]';
		}
		
		if($ftype == 'text'){
			$input_class[] = 'thwdpf-inputtext';
		}else if($ftype == 'number'){
			$input_class[] = 'thwdpf-inputtext';
		}else if($ftype == 'select' || $ftype == 'multiselect' || $ftype == 'multiselect_grouped'){
			$input_class[] = 'thwdpf-select';
		}else if($ftype == 'colorpicker'){
			$input_class[] = 'thwdpf-color thpladmin-colorpick';
		}else if($ftype == 'product'){
			$input_class[] = 'thwdpf-product-select';
		}

		if($enhanced_select || $ftype == 'multiselect' || $ftype == 'multiselect_grouped'){
			$input_class[] = 'thwdpf-enhanced-multi-select';
		}

		$input_style = '';
		if(!empty($args['input_width'])){
			$input_style = 'width:'.$args['input_width'].';';
		}

		$fname  = $args['input_name_prefix'].$field['name'].$args['input_name_suffix'];
		$fvalue = isset($field['value']) ? esc_html($field['value']) : '';

		$field_props  = 'name="'. $fname .'" style="'. $input_style .'"';
		$field_props .= !empty($input_class) ? ' class="'. implode(" ", $input_class) .'"' : '';
		$field_props .= $ftype == 'textarea' ? '' : ' value="'. $fvalue .'"';
		$field_props .= $ftype == 'multiselect_grouped' ? ' data-value="'. $fvalue .'"' : '';
		$field_props .= !empty($field['onchange']) ? ' onchange="'.$field['onchange'].'"' : '';
		$field_props .= isset($field['disabled']) ? ' disabled="disabled"' : '';

		$placeholder = isset($field['placeholder']) ? esc_html($field['placeholder']) : false;
		if($placeholder){
			$field_props .= ' placeholder="'. $placeholder .'"';
			$field_props .= $enhanced_select ? ' data-placeholder="'. $placeholder .'"' : '';
		}

		if( $ftype == 'number' ){
			$min = isset( $field['min'] ) ? $field['min'] : '';
			$max = isset( $field['max'] ) ? $field['max'] : '';
			$field_props .= ' min="'.$min.'" max="'.$max.'"';
		}

		return $field_props;
	}

	private function render_form_field_element_inputtext($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			$field_html = '<input type="text" '. $field_props .' >';
		}
		return $field_html;
	}

	private function render_form_field_element_textarea($field, $args = array()){
		$field_html = '';
		if($field && is_array($field)){
			$args = wp_parse_args( $args, array(
			    'rows' => '5',
				'cols' => '29',
			));

			$fvalue = isset($field['value']) ? $field['value'] : '';
			$field_props = $this->prepare_form_field_props($field, $args);
			$field_html = '<textarea '. $field_props .' rows="'.$args['rows'].'" cols="'.$args['cols'].'" >'.$fvalue.'</textarea>';
		}
		return $field_html;
	}

	private function render_form_field_element_select($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$fvalue = isset($field['value']) ? $field['value'] : '';
			$field_props = $this->prepare_form_field_props($field, $atts);

			$field_html = '<select '. $field_props .' >';
			foreach($field['options'] as $value => $label){
				$selected = $value === $fvalue ? 'selected' : '';
				$field_html .= '<option value="'. esc_attr($value) .'" '.$selected.'>'. esc_attr__($label, 'discount-and-dynamic-pricing') .'</option>';
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}

	private function render_form_field_element_multiselect($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);

			$field_html = '<select multiple="multiple" '. $field_props .'>';
			foreach($field['options'] as $value => $label){
				//$selected = $value === $fvalue ? 'selected' : '';
				$field_html .= '<option value="'. esc_attr($value) .'" >'. esc_attr__($label, 'discount-and-dynamic-pricing') .'</option>';
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}

	private function render_form_field_element_multiselect_grouped($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);

			$field_html = '<select multiple="multiple" '. $field_props .'>';
			foreach($field['options'] as $group_label => $fields){
				$field_html .= '<optgroup label="'. $group_label .'">';

				foreach($fields as $value => $label){
					$value = trim($value);
					if(isset($field['glue']) && !empty($field['glue'])){
						$value = $value.$field['glue'].trim($label);
					}

					$field_html .= '<option value="'. $value .'">'. __($label, 'discount-and-dynamic-pricing') .'</option>';
				}

				$field_html .= '</optgroup>';
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}

	private function render_form_field_element_radio($field, $atts = array()){
		$field_html = '';
		/*if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);

			$field_html = '<select '. $field_props .' >';
			foreach($field['options'] as $value => $label){
				$selected = $value === $fvalue ? 'selected' : '';
				$field_html .= '<option value="'. trim($value) .'" '.$selected.'>'. __($label, 'discount-and-dynamic-pricing') .'</option>';
			}
			$field_html .= '</select>';
		}*/
		return $field_html;
	}

	private function render_form_field_element_checkbox($field, $atts = array(), $render_cell = true){
		$field_html = '';
		if($field && is_array($field)){
			$args = shortcode_atts( array(
				'label_props' => '',
				'cell_props'  => '',
				'input_props' => '',
				'id_prefix'   => 'a_f',
				'render_input_cell' => false,
			), $atts );

			$fid = $args['id_prefix']. $field['name'];
			$fdesc = isset($field['description']) && !empty($field['description']) ? __($field['description'], 'discount-and-dynamic-pricing') : '';

			$field_props  = $this->prepare_form_field_props($field, $atts);
			$field_props .= isset($field['checked']) && $field['checked'] === 'yes' ? ' checked="checked"' : '';
			$field_props .= $args['input_props'];

			$field_html  = '<input type="checkbox" id="'. $fid .'" '. $field_props .' >';
			$field_html .= '<label for="'. $fid .'" '. $args['label_props'] .' > '. $fdesc .'</label>';
		}
		if(!$render_cell && $args['render_input_cell']){
			return '<td '. $args['cell_props'] .' >'. $field_html .'</td>';
		}else{
			return $field_html;
		}
	}

	private function render_form_field_element_colorpicker($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);

			$field_html  = '<span class="thpladmin-colorpickpreview '.$field['name'].'_preview" style=""></span>';
            $field_html .= '<input type="text" '. $field_props .' >';
		}
		return $field_html;
	}

	private function render_form_field_element_number($field, $atts = array() ){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			$field_html = '<input type="number" '. $field_props .' >';
		}
		return $field_html;
	}

	private function render_form_field_element_datepicker($field, $args = array()){
		$field_html = '';
		if($field && is_array($field)){
			$input_width = $args['input_width'] ? 'width:'.$args['input_width'].';' : '';
			unset($args['input_width']);

			$args['input_class'] = array('input', 'thwdpf-datepicker');
			$field_props = $this->prepare_form_field_props($field, $args);

			$field_html  = '<div class="icon-field" style="'. $input_width .'">';
			$field_html .= '<i class="dashicons dashicons-calendar-alt"></i>';
			$field_html .= '<input type="text" '. $field_props .' >';
			$field_html .= '</div>';
		}
		return $field_html;
	}

	private function render_form_field_element_timepicker($field, $args = array()){
		$field_html = '';
		if($field && is_array($field)){
			$input_width = $args['input_width'] ? 'width:'.$args['input_width'].';' : '';
			unset($args['input_width']);

			$args['input_class'] = array('input', 'thwdpf-timepicker');
			$field_props = $this->prepare_form_field_props($field, $args);

			$field_html  = '<div class="icon-field" style="'. $input_width .'">';
			$field_html .= '<i class="dashicons dashicons-clock"></i>';
			$field_html .= '<input type="text" '. $field_props .' >';
			$field_html .= '</div>';
		}
		return $field_html;
	}

	public function render_form_fragment_tooltip($tooltip = false){
		if($tooltip){
			$tooltip = __($tooltip, 'discount-and-dynamic-pricing');
			?>
			<td class="tip" style="width: 26px; padding:0px;">
				<a href="javascript:void(0)" title="<?php echo $tooltip; ?>" class="thwdpf_tooltip"><img src="<?php echo THWDPF_ASSETS_URL_ADMIN; ?>/css/help.png" title=""/></a>
			</td>
			<?php
		}else{
			?>
			<td style="width: 26px; padding:0px;"></td>
			<?php
		}
	}

	public function render_form_fragment_h_spacing($padding = 5){
		$style = $padding ? 'padding-top:'.$padding.'px;' : '';
		?>
        <tr><td colspan="3" style="<?php echo $style ?>"></td></tr>
        <?php
	}

	public function render_form_fragment_h_separator($atts = array()){
		$args = shortcode_atts( array(
			'colspan' 	   => 3,
			'padding-top'  => '5px',
			'border-style' => 'dashed',
    		'border-width' => '1px',
			'border-color' => '#e6e6e6',
			'content'	   => '',
		), $atts );

		$style  = $args['padding-top'] ? 'padding-top:'.$args['padding-top'].';' : '';
		$style .= $args['border-style'] ? ' border-bottom:'.$args['border-width'].' '.$args['border-style'].' '.$args['border-color'].';' : '';

		?>
        <tr><td colspan="<?php echo $args['colspan']; ?>" style="<?php echo $style; ?>"><?php echo $args['content']; ?></td></tr>
        <?php
	}

	public function render_form_field_blank($colspan = 3){
		?>
        <td colspan="<?php echo $colspan; ?>">&nbsp;</td>
        <?php
	}

	public function render_form_section_separator($props, $atts=array()){
		?>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" style="height:10px;"></td></tr>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" class="thpladmin-form-section-title" ><?php echo $props['title']; ?></td></tr>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" style="height:0px;"></td></tr>
		<?php
	}

	/*----- Tab Title -----*/
	public function render_form_tab_main_title($title){
		?>
		<main-title classname="main-title">
			<button class="device-mobile btn--back Button">
				<i class="button-icon button-icon-before i-arrow-back"></i>
			</button>
			<span class="device-mobile main-title-icon text-primary"><i class="i-check drishy"></i><?php esc_html_e($title, 'discount-and-dynamic-pricing') ?></span>
			<span class="device-desktop"><?php esc_html_e($title, 'discount-and-dynamic-pricing') ?></span>
		</main-title>
		<?php
	}

	/*----- Form Element Row -----*/
	public function prepare_settings_row_class( $field ){
		$name = isset($field['name']) ? $field['name'] : '';
		return 'form_field_'.$name;
	}
	
	public function render_form_elm_row($field, $args=array()){
		$row_class = $this->prepare_settings_row_class( $field );
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<?php $this->render_form_field_element($field, $this->cell_props); ?>
		</tr>
		<?php
	}

	public function render_form_elm_row_ta($field, $args=array()){
		$row_class = $this->prepare_settings_row_class( $field );
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<?php $this->render_form_field_element($field, $this->cell_props_TA); ?>
		</tr>
		<?php
	}

	public function render_form_elm_row_cb($field, $args=array()){
		$row_class = $this->prepare_settings_row_class( $field );
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<!-- <td class="label" colspan="2">&nbsp;</td>
			<td class="field"> -->
	    		<?php $this->render_form_field_element($field, $this->cell_props_CB); ?>
	    	<!-- </td> -->
	    </tr>
		<?php
	}

	public function render_form_elm_row_dummy($field, $args=array()){
		$name = isset($field['name']) ? $field['name'] : '';
		$label = isset($field['label']) ? $field['label'] : '';
		$field = array('type'=>'text', 'name'=>$name.'_dummy', 'label'=>$label, 'disabled'=>1);

		$row_class = $this->prepare_settings_row_class( $field );
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<?php $this->render_form_field_element($field, $this->cell_props); ?>
		</tr>
		<?php
	}

	public function render_form_elm_row_cp($field, $args=array()){
		?>
		<tr>
	    	<?php $this->render_form_field_element($field, $this->cell_props_CP); ?>
	    </tr>
		<?php
	}

	public function render_form_elm_row_datetime($date, $time, $args=array()){
		$label      = isset($args['label']) ? $args['label'] : '';
		$sublabel   = isset($args['sub_label']) ? $args['sub_label'] : '';
		$tooltip    = isset($args['hint_text']) ? $args['hint_text'] : '';
		$row_class  = isset($args['row_class']) ? $args['row_class'] : '';

		$label_html = $this->prepare_form_field_label($label, $sublabel);
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<td class="label"><?php echo $label_html; ?></td>
			<?php $this->render_form_fragment_tooltip($tooltip); ?>
			<td class="field">
	    		<?php echo $this->render_form_field_element_datepicker($date, $this->cell_props_DTD, false); ?>
	    		<?php echo $this->render_form_field_element_timepicker($time, $this->cell_props_DTT, false); ?>
	    	</td>
	    </tr>
		<?php
	}

	public function render_form_fragment_bulk_pricing(){
		$tooltip = '';
		//$discount_types = $this->get_discount_types();
		$discount_types = array(
			'fixed'      =>__('Fixed','discount-and-dynamic-pricing'),
			'percentage' => __('Percentage','discount-and-dynamic-pricing'),
		);
		?>
		<tr class="form_field_ranges">
			<td class="label"><label><?php esc_html_e('Range', 'discount-and-dynamic-pricing'); ?></label></td>
			<?php $this->render_form_fragment_tooltip($tooltip); ?>
			<td class="field">
				<table border="0" cellpadding="0" cellspacing="0" class="thwdpf-discount-ranges thpladmin-options-table"><tbody>
					<tr>
						<td class="min-qty"><input type="text" name="i_range_min_qty[]" placeholder="<?php esc_attr_e( 'Min. Qty','discount-and-dynamic-pricing'); ?>"></td>
						<td class="max-qty"><input type="text" name="i_range_max_qty[]" placeholder="<?php esc_attr_e( 'Max. Qty','discount-and-dynamic-pricing'); ?>"></td>
						<td class="discount-type">    
							<select name="i_range_discount_type[]">
							<?php
								foreach ($discount_types as $value => $text) {
									echo '<option value="'. esc_attr($value) .'">'. esc_html($text) .'</option>';
								}
							?>
							</select>
						</td>
						<td class="discount"><input type="text" name="i_range_discount[]" placeholder="<?php esc_attr_e( 'Amount','discount-and-dynamic-pricing'); ?>"></td>
						<td class="action-cell">
							<a href="javascript:void(0)" onclick="thwdpfAddNewRangeRow(this)" class="btn btn-tiny btn-primary" title="<?php esc_attr_e( 'Add new range','discount-and-dynamic-pricing'); ?>">+</a><a href="javascript:void(0)" onclick="thwdpfRemoveRangeRow(this)" class="btn btn-tiny btn-danger" title="<?php esc_attr_e( 'Remove range','discount-and-dynamic-pricing'); ?>">x</a>
							<!-- <span class="btn btn-tiny sort ui-sortable-handle"></span> -->
						</td>
					</tr>
				</tbody></table>            	
			</td>
		</tr>
        <?php
	}

	private function render_form_field_element_product($field, $args = array()){
		$field_html = '';
		if($field && is_array($field)){
			$args['multiple'] = true;
			$field_props = $this->prepare_form_field_props($field, $args);

			$field_html = '<select multiple="multiple" '. $field_props .'></select>';
		}
		return $field_html;
	}

	private function render_form_field_element_term($field, $args = array()){
		$field_html = '';
		if($field && is_array($field)){
			$args['input_class'] = array('thwdpf-select', 'thwdpf-enhanced-multi-select', 'thwdpf-term-select');
			$args['multiple'] = true;

			$field_props = $this->prepare_form_field_props($field, $args);
			$options = isset($field['options']) ? $field['options'] : array();

			if(!empty($options)){
				$field_html = '<select multiple="multiple" '. $field_props .'>';
				foreach($options as $term){
					$field_html .= '<option value="'. esc_attr($term["id"]) .'" >'. esc_attr__($term["title"], 'discount-and-dynamic-pricing') .'</option>';
				}
				$field_html .= '</select>';
			}
		}
		return $field_html;
	}
}

endif;
