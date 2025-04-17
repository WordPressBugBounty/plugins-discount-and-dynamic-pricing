<?php
/**
 * The product discount rule form.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){ die; }

if(!class_exists('THWDPF_Admin_Form_Product_Rule')):

class THWDPF_Admin_Form_Product_Rule extends THWDPF_Admin_Form{
	public function __construct() {
		parent::__construct();
		$this->init_constants();
	}

	private function init_constants(){
		$this->rule_props = $this->get_rule_form_props();
		$this->restriction_props = $this->get_restriction_form_props();
	}

	public function get_rule_form_props(){		
		$form_props = $this->get_discount_rule_form_props();
		return $form_props;
	}

	public function get_restriction_form_props(){
		$form_props = $this->get_discount_rule_restriction_form_props();
		return $form_props;
	}

	public function output_rule_forms(){
		$this->output_rule_form_pp();
	}

	private function output_rule_form_pp(){
		?>
        <div id="thwdpf_rule_form_pp" class="thpladmin-modal-mask">
        	<?php $this->output_popup_form_rules(); ?>
        </div>
        <?php
	}

	/*****************************************/
	/********** POPUP FORM WIZARD ************/
	/*****************************************/
	private function output_popup_form_rules(){
		?>
		<div class="thpladmin-modal">
			<div class="modal-container">
				<span class="modal-close" onclick="thwdpfCloseModal(this)">×</span>
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-wizard wizard">
							<aside>
								<side-title class="wizard-title"><?php esc_html_e('Save Field', 'discount-and-dynamic-pricing') ?></side-title>
								<ul class="pp_nav_links">
									<li class="text-primary first pp-nav-link-general" data-index="0">
										<i class="dashicons dashicons-admin-generic text-primary"></i>
										<?php esc_html_e('General Properties', 'discount-and-dynamic-pricing') ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary pp-nav-link-restrictions" data-index="1">
										<i class="dashicons dashicons-remove text-primary"></i>
										<?php esc_html_e('Restrictions', 'discount-and-dynamic-pricing') ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary last pp-nav-link-conditions" data-index="2">
										<i class="dashicons dashicons-star-empty text-primary"></i>
										<?php esc_html_e('More Restrictions', 'discount-and-dynamic-pricing') ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
								</ul>
							</aside>
							<main class="form-container main-full">
								<form method="post" id="thwdpf_rule_form" action="">
									<?php wp_nonce_field('update_discount_rules', 'update_discount_rules_nonce'); ?>
                    				<input type="hidden" name="i_action" value="" >
                    				<input type="hidden" name="i_context" value="product" >
                    				<input type="hidden" name="i_name" value="" >
                    				<input type="hidden" name="i_priority" value="" >
                    				<input type="hidden" name="i_range_discounts" value="" >
                    				<input type="hidden" name="i_restrictions_other" value="" >

									<div class="data-panel data_panel_0">
										<?php $this->render_form_tab_general_props(); ?>
									</div>
									<div class="data-panel data_panel_1">
										<?php $this->render_form_tab_restrictions(); ?>
									</div>
									<div class="data-panel data_panel_2">
										<?php $this->render_form_tab_conditions(); ?>
									</div>
								</form>
							</main>
							<footer>
								<span class="Loader"></span>
								<div class="btn-toolbar">
									<button class="prev-btn pull-right btn btn-primary-alt" onclick="thwdpfWizardPrevious(this)">
										<span><?php esc_html_e('Back', 'discount-and-dynamic-pricing') ?></span>
										<i class="i i-plus"></i>
									</button>
									<button class="next-btn pull-right btn btn-primary-alt" onclick="thwdpfWizardNext(this)">
										<span><?php esc_html_e('Next', 'discount-and-dynamic-pricing') ?></span>
										<i class="i i-plus"></i>
									</button>
									<button class="save-btn pull-right btn btn-primary" onclick="thwdpfSaveRule(this)">
										<span><?php esc_html_e('Save & Close', 'discount-and-dynamic-pricing') ?></span>
									</button>
								</div>
							</footer>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/*----- TAB - General Info -----*/
	private function render_form_tab_general_props(){
		$this->render_form_tab_main_title('General Properties');

		$args_from_dt = array(
			'label' => 'Start From',
			'row_class' => 'form_field_startfrom',
		);
		$args_end_dt = array(
			'label' => 'End By',
			'row_class' => 'form_field_endby',
		);

		?>
		<div style="display: inherit;" class="data-panel-content">
			<div class="err_msgs"></div>
			<table class="thwdpf_pp_table compact thwdpf-general-props">
	        	<?php
				$this->render_form_elm_row($this->rule_props['label']);
				$this->render_form_elm_row($this->rule_props['method']);
				$this->render_form_elm_row($this->rule_props['discount_type']);
				$this->render_form_elm_row($this->rule_props['discount_amount']);
				$this->render_form_fragment_bulk_pricing();

				$this->render_form_fragment_h_separator();
				$this->render_form_elm_row_datetime($this->rule_props['start_date'], $this->rule_props['start_time'], $args_from_dt);
				$this->render_form_elm_row_datetime($this->rule_props['end_date'], $this->rule_props['end_time'], $args_end_dt);

				$this->render_form_fragment_h_separator();
				$this->render_form_elm_row($this->rule_props['apply_when']);
				$this->render_form_elm_row_cb($this->rule_props['enabled']);
				?>
	        </table>
		</div>
		<?php
	}

	/*----- TAB - Restrictions -----*/
	private function render_form_tab_restrictions(){
		$this->render_form_tab_main_title('Restrictions');

		?>
		<div style="display: inherit;" class="data-panel-content mt-10">
			<table class="thwdpf_pp_table compact thwdpf-restrictions">
				<?php
				$this->render_form_elm_row_cb($this->rule_props['need_login']);
				$this->render_form_elm_row($this->rule_props['allowed_roles']);
				$this->render_form_elm_row_dummy($this->rule_props['allowed_roles']);
				$this->render_form_fragment_h_separator();
				$this->render_form_elm_row($this->restriction_props['allowed_products']);
				$this->render_form_elm_row($this->restriction_props['restricted_products']);
				$this->render_form_fragment_h_separator();
				$this->render_form_elm_row($this->restriction_props['allowed_cats']);
				$this->render_form_elm_row($this->restriction_props['restricted_cats']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Conditions -----*/
	private function render_form_tab_conditions(){
		$this->render_form_tab_main_title('More Restrictions');

		?>
		<div style="display: inherit;" class="data-panel-content mt-10">
			<table class="thwdpf_pp_table compact thwdpf-conditions">
				<?php
				$this->render_form_fragment_restrictions('product', 'thwdpf-restictions-produt');
				?>
			</table>
		</div>
		<?php
	}

	/*----------------------------------------*/
	/*------ Form Fragment Restrictions ------*/
	/*----------------------------------------*/
	public function render_form_fragment_restrictions($type='product', $unique_class=''){
		$wrapper_class  = 'thwdpf-cr-wrapper';
		$wrapper_class .= !empty($unique_class) ? ' '.$unique_class : '';

		?>
        <tr>
        	<td class=""><?php esc_html_e('Apply this discount rule if all the below conditions are met.', 'discount-and-dynamic-pricing') ?></td>
        </tr>
        <tr>                
            <td class="<?php echo $wrapper_class; ?>">
            	<table class="thwdpf_conditional_rules"><tbody>
                    <tr class="thwdpf_rule_set_row">                
                        <td>
                            <table class="thwdpf_rule_set"><tbody>
                                <tr class="thwdpf_rule_row">
                                    <td>
                                        <table class="thwdpf_rule"><tbody>
                                            <tr class="thwdpf_condition_set_row">
                                                <td>
                                                    <table class="thwdpf_condition_set" style=""><tbody>
                                                        <tr class="thwdpf_condition">
                                                            <td class="operand-type">
                                                                <select name="i_rule_operand_type">
                                                                    <option value=""><?php esc_html_e('Select an option...', 'discount-and-dynamic-pricing') ?></option>
	                                                                <option value="prod_price"><?php esc_html_e('Product Price', 'discount-and-dynamic-pricing') ?></option>
	                                                                <option value="cart_qty"><?php esc_html_e('Cart Quantity', 'discount-and-dynamic-pricing') ?></option>
	                                                                <!-- <option value="cart_subtotal">Cart Subotal</option> -->
                                                                </select>
                                                            </td>
                                                            <td class="operator">
                                                                <select name="i_rule_operator">
                                                                    <option value=""></option>
                                                                    <option value="value_gt"><?php esc_html_e('Greater than', 'discount-and-dynamic-pricing') ?></option>
                                                                    <option value="value_lt"><?php esc_html_e('Less than', 'discount-and-dynamic-pricing') ?></option>
                                                                </select>
                                                            </td>
                                                            <td class="operand thpladmin_rule_operand">
                                                            	<input type="text" name="i_rule_operand">
                                                            </td>
                                                            <td class="actions">
                                                                <a href="javascript:void(0)" class="thpl_logic_link" onclick="thwdpfAddNewConditionRow(this, 1)" title=""><?php esc_html_e('AND', 'discount-and-dynamic-pricing') ?></a>
                                                                <a href="javascript:void(0)" class="thpl_logic_link" onclick="thwdpfAddNewConditionRow(this, 2)" title=""><?php esc_html_e('OR', 'discount-and-dynamic-pricing') ?></a>
                                                                <a href="javascript:void(0)" class="thpl_delete_icon dashicons dashicons-no" onclick="thwdpfRemoveRuleRow(this)" title="Remove"></a>
                                                            </td>
                                                        </tr>
                                                    </tbody></table>
                                                </td>
                                            </tr>
                                        </tbody></table>
                                    </td>
                                </tr>
                            </tbody></table>            	
                        </td>            
                    </tr> 
        		</tbody></table>
        	</td>
        </tr>
        <?php
	}
}

endif;