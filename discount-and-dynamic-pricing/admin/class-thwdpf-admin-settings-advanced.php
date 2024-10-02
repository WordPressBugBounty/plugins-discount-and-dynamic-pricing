<?php
/**
 * The Advanced settings page for the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Admin_Settings_Advanced')):

class THWDPF_Admin_Settings_Advanced extends THWDPF_Admin_Settings{
	protected static $_instance = null;
	
	private $settings_fields = NULL;
	private $cell_props = array();
	private $cell_props_CB = array();
	private $cell_props_TA = array();

	public function __construct() {
		parent::__construct();
		$this->page_id = 'advanced_settings';
		$this->init_constants();
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function init_constants(){
		$this->cell_props = array( 
			'label_cell_props' => 'class="label"', 
			'input_cell_props' => 'class="field"',
			'input_width' => '260px',
			'label_cell_th' => true
		);

		$this->cell_props_TA = array( 
			'label_cell_props' => 'class="label"', 
			'input_cell_props' => 'class="field"',
			'rows' => 10,
			'cols' => 100,
		);

		$this->cell_props_CB = array( 
			'label_props' => 'style="margin-right: 40px;"', 
		);
		
		$this->settings_fields = $this->get_advanced_settings_fields();
	}

	public function get_advanced_settings_fields(){
		return array(
			'enable_bulk_pricing_table' => array(
				'name'=>'enable_bulk_pricing_table', 'label'=>__('Show Bulk Pricing Table.', 'discount-and-dynamic-pricing'), 'type'=>'checkbox', 'value'=>'1', 'checked'=>1
			),
			'enable_caption_on_bulk_table' => array(
				'name'=>'enable_caption_on_bulk_table', 'label'=>__('Enable caption for bulk pricing table.', 'discount-and-dynamic-pricing'), 'type'=>'checkbox', 'value'=>'1', 'checked'=>1
			),
			'enable_strikeout' => array(
				'name'=>'enable_strikeout', 'label'=>__('Enable strikeout price.', 'discount-and-dynamic-pricing'), 'type'=>'checkbox', 'value'=>'1', 'checked'=>1
			),
			'on_product_page' => array(
				'name'=>'on_product_page', 'label'=>__('Show strikeout price on product page.', 'discount-and-dynamic-pricing'), 'type'=>'checkbox', 'value'=>'1', 'checked'=>1
			),
			'on_shop_page' => array(
				'name'=>'on_shop_page', 'label'=>__('Show strikeout price on shop page.', 'discount-and-dynamic-pricing'), 'type'=>'checkbox', 'value'=>'1', 'checked'=>1
			),
			'on_product_category' => array(
				'name'=>'on_product_category', 'label'=>__('Show strikeout price on product category page.', 'discount-and-dynamic-pricing'), 'type'=>'checkbox', 'value'=>'1', 'checked'=>1
			),
			'strike_sale_price_on_cart' => array(
				'name'=>'strike_sale_price_on_cart', 'label'=>__('Strikeout sale price instead of regular price on cart.(if have)', 'discount-and-dynamic-pricing'), 'type'=>'checkbox', 'value'=>'1', 'checked'=>0
			),
		);
	}

	public function render_page(){
		$this->render_tabs();
		$this->render_content();
	}

	public function save_advanced_settings($settings){
		$result = update_option(THWDPF_Utils::OPTION_KEY_ADVANCED_SETTINGS, $settings);
		return $result;
	}
	
	private function reset_settings(){
		check_admin_referer( 'update_advanced_settings', 'update_advanced_nonce' );

		$capability = THWDPF_Utils::wdpf_capability();
		if(!current_user_can($capability)){
			wp_die();
		}

		delete_option(THWDPF_Utils::OPTION_KEY_ADVANCED_SETTINGS);
		$this->print_notices(__('Settings successfully reset.', 'discount-and-dynamic-pricing'), 'updated', false);

		foreach( $this->settings_fields as $name => $field ) {
			$value = '';
			
			if($field['type'] === 'checkbox'){
				$value = '1';

			}
			if($name === 'strike_sale_price_on_cart'){
				$value = '0';
			}
			$settings[$name] = $value;
		}		
		$this->save_advanced_settings($settings);
	}
	
	private function save_settings(){

		check_admin_referer( 'update_advanced_settings', 'update_advanced_nonce' );

		$capability = THWDPF_Utils::wdpf_capability();
		if(!current_user_can($capability)){
			wp_die();
		}

		$settings = array();
		
		foreach( $this->settings_fields as $name => $field ) {
			$value = '';
			
			if($field['type'] === 'checkbox'){
				$value = !empty( $_POST['i_'.$name] ) ? '1' : '';

			}else if($field['type'] === 'multiselect_grouped'){
				$value = !empty( $_POST['i_'.$name] ) ? $_POST['i_'.$name] : '';
				$value = is_array($value) ? implode(',', wc_clean(wp_unslash($value))) : wc_clean(wp_unslash($value));

			}else if($field['type'] === 'text' || $field['type'] === 'textarea'){
				$value = !empty( $_POST['i_'.$name] ) ? $_POST['i_'.$name] : '';
				$value = !empty($value) ? wc_clean( wp_unslash($value)) : '';

			}else{
				$value = !empty( $_POST['i_'.$name] ) ? $_POST['i_'.$name] : '';
				$value = !empty($value) ? wc_clean( wp_unslash($value)) : '';
			}
			
			$settings[$name] = $value;
		}
				
		$result = $this->save_advanced_settings($settings);
		if ($result == true) {
			$this->print_notices(__('Your changes were saved.', 'discount-and-dynamic-pricing'), 'updated', false);
		} else {
			$this->print_notices(__('Your changes were not saved due to an error (or you made none!).', 'discount-and-dynamic-pricing'), 'error', false);
		}	
	}
	
	//Advanced Settings Tab
	private function render_content(){
		?>
		<div class="content-wrap">
			<h2><?php esc_html_e('General Settings', 'discount-and-dynamic-pricing'); ?></h2>
			<?php
			if(isset($_POST['reset_settings']))
				$this->reset_settings();	
			
			if(isset($_POST['save_settings']))
				$this->save_settings();
				
    		$this->render_plugin_settings();
			?>    
		</div>
		<?php
	}

	private function render_plugin_settings(){
		$settings = THWDPF_Utils::get_advanced_settings();
		?>            
        <div class="wrap" style="padding-left: 13px;">
		    <form id="advanced_settings_form" method="post" action="">
                <table class="thwdpf-settings-table thpladmin-form-table">
                    <tbody>
                    <?php
                    $this->render_general_settings($settings);
                    // $this->render_other_settings($settings);
					?>
                    </tbody>
                </table> 
                <p class="submit">
					<input type="submit" name="save_settings" class="btn btn-small btn-primary mr-15" value="<?php _e('Save changes', 'discount-and-dynamic-pricing'); ?>">
                    <input type="submit" name="reset_settings" class="btn btn-small" value="<?php _e('Reset to default','discount-and-dynamic-pricing'); ?>" 
					onclick="return confirm('Are you sure you want to reset to default settings? all your changes will be deleted.');">
					<?php wp_nonce_field( 'update_advanced_settings', 'update_advanced_nonce' ); ?>
            	</p>
            </form>
    	</div>       
    	<?php
	}

	private function render_general_settings($settings){
		$this->render_form_elm_row_title(__('Bulk Pricing Table', 'discount-and-dynamic-pricing'));
		$this->render_form_elm_row_cb($this->settings_fields['enable_bulk_pricing_table'], $settings, true);
		$this->render_form_elm_row_cb($this->settings_fields['enable_caption_on_bulk_table'], $settings, true);
		$this->render_form_elm_row_title(__('Strikout Price', 'discount-and-dynamic-pricing'));
		$this->render_form_elm_row_cb($this->settings_fields['enable_strikeout'], $settings, true);
		$this->render_form_elm_row_cb($this->settings_fields['on_product_page'], $settings, true);
		$this->render_form_elm_row_cb($this->settings_fields['on_shop_page'], $settings, true);
		$this->render_form_elm_row_cb($this->settings_fields['on_product_category'], $settings, true);
		$this->render_form_elm_row_title(__('Other', 'discount-and-dynamic-pricing'));
		$this->render_form_elm_row_cb($this->settings_fields['strike_sale_price_on_cart'], $settings, true);
		?>
		
		<?php			
	}

	/*----- Form Element Row -----*/
	public function render_form_elm_row_title($title=''){
		?>
		<tr>
			<td colspan="3" class="section-title" ><?php echo $title; ?></td>
		</tr>
		<?php
	}

	private function render_form_elm_row_cb($field, $settings=false, $merge_cells=false){
		$name = $field['name'];
		if(is_array($settings) && isset($settings[$name])){
			if($field['value'] === $settings[$name]){
				$field['checked'] = 1;
			}else{
				$field['checked'] = 0;
			}
		}

		if($merge_cells){
			?>
			<tr>
				<td colspan="3">
		    		<?php $this->render_form_field_element($field, $this->cell_props_CB, false); ?>
		    	</td>
		    </tr>
			<?php
		}else{
			?>
			<tr>
				<td colspan="2"></td>
				<td class="field">
		    		<?php $this->render_form_field_element($field, $this->cell_props_CB, false); ?>
		    	</td>
		    </tr>
			<?php
		}
	}
}

endif;