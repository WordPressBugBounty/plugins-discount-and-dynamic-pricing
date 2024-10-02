<?php
/**
 * The admin specific functionality of the plugin.
 *
 * @link       https://themehigh.com
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Admin')):
 
class THWDPF_Admin {
	private $plugin_name;
	private $version;
	private $screen_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('wp_ajax_thwdpf_dismiss_review_request_notice', array($this, 'dismiss_review_request_notice'));
		add_action('wp_ajax_thwdpf_skip_review_request_notice', array($this, 'skip_review_request_notice'));
	}
	
	public function enqueue_styles_and_scripts($hook) {
		if(strpos($hook, 'thwdpf_settings') !== false) {
			$debug_mode = apply_filters('thwdpf_debug_mode', false);
			$suffix = $debug_mode ? '' : '.min';
			
			$this->enqueue_styles($suffix);
			$this->enqueue_scripts($suffix);
		}
	}
	
	private function enqueue_styles($suffix) {
		wp_enqueue_style('woocommerce_admin_styles');
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_style('thwdpf-timepicker-style', THWDPF_ASSETS_URL_ADMIN.'js/timepicker/jquery.timepicker.css');
		wp_enqueue_style('thwdpf-admin-style', THWDPF_ASSETS_URL_ADMIN . 'css/thwdpf-admin'. $suffix .'.css', $this->version);
	}

	private function enqueue_scripts($suffix) {
		$in_footer = apply_filters('thwdpf_enqueue_script_in_footer', false);
		$deps = array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-tiptip', 'woocommerce_admin', 'select2', 'wp-color-picker', 'wp-i18n');

		wp_enqueue_script('thwdpf-timepicker-script', THWDPF_ASSETS_URL_ADMIN.'js/timepicker/jquery.timepicker.min.js', array('jquery'), '1.0.1', $in_footer);

		wp_enqueue_script('thwdpf-admin-script', THWDPF_ASSETS_URL_ADMIN . 'js/thwdpf-admin'. $suffix .'.js', $deps, $this->version, $in_footer);
		wp_set_script_translations('thwdpf-admin-script', 'discount-and-dynamic-pricing', dirname(THWDPF_BASE_NAME) . '/languages/');

		//$skip_products_loading = THWDPF_Utils::skip_products_loading();
		//$skip_products_loading = $skip_products_loading ? 'yes' : 'no';

		$wdpf_var = array(
            'admin_url' => admin_url(),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
			//'input_operand' => $skip_products_loading,
        );
		wp_localize_script('thwdpf-admin-script', 'wdpf_var', $wdpf_var);
		
	}
	
	public function admin_menu() {
		$capability = THWDPF_Utils::wdpf_capability();
		$page_title = esc_html__('Discount Rules', 'discount-and-dynamic-pricing');
    	$menu_title = esc_html__('Discount Rules', 'discount-and-dynamic-pricing');

		$this->screen_id = add_submenu_page('woocommerce', $page_title, $menu_title, $capability, 'thwdpf_settings', array($this, 'output_settings'));
	}
	
	public function add_screen_id($ids){
		$ids[] = 'woocommerce_page_thwdpf_settings';
		$ids[] = strtolower(esc_html__('WooCommerce', 'discount-and-dynamic-pricing')) .'_page_thwdpf_settings';

		return $ids;
	}

	public function plugin_action_links($links) {
		$settings_link = '<a href="'.admin_url('admin.php?page=thwdpf_settings').'">'. esc_html__('Settings', 'discount-and-dynamic-pricing') .'</a>';
		array_unshift($links, $settings_link);
		if (array_key_exists('deactivate', $links)) {
		    $links['deactivate'] = str_replace('<a', '<a class="thwdpf-deactivate-link"', $links['deactivate']);
		}

		return $links;
	}

	public function get_current_tab(){
		return isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'product_rules';
	}
	
	public function output_settings(){
		if (!current_user_can('manage_options')) {//TODO
			wp_die( __( 'You do not have sufficient permissions to access this page.','discount-and-dynamic-pricing'));
		}

		echo '<div class="wrap">';
		echo '<h2></h2>';
		//$this->output_review_request_link();

		$tab = $this->get_current_tab();

		echo '<div class="thwdpf-wrap">';
		// if($tab === 'premium_info'){
		// 	$pro_details = THWDPF_Admin_Settings_Pro::instance();	
		// 	$pro_details->render_page();

		// }else
		if($tab === 'cart_rules'){
			$cart_rules = THWDPF_Admin_Settings_Rules_Cart::instance();	
			$cart_rules->init();
		}
		elseif($tab === 'advanced_settings'){
			$advanced_settings = THWDPF_Admin_Settings_Advanced::instance();	
			$advanced_settings->render_page();

		}else{
			$product_rules = THWDPF_Admin_Settings_Rules_Product::instance();	
			$product_rules->init();
		}
		echo '</div>';
		echo '</div>';
	}

	private function output_review_request_link(){		
		$is_dismissed = get_transient('thwdpf_review_request_notice_dismissed');
		if($is_dismissed){
			return;
		}

		$is_skipped = get_transient('thwdpf_skip_review_request_notice');
		if($is_skipped){
			return;
		}

		$thwdpf_since = get_option('thwdpf_since');
		if(!$thwdpf_since){
			$now = time();
			update_option('thwdpf_since', $now, 'no');
		}else{
			$now = time();
			$diff_seconds = $now - $thwdpf_since;

			if($diff_seconds > apply_filters('thwdpf_show_review_request_notice_after', 10 * DAY_IN_SECONDS)){
				$this->render_review_request_notice();
			}
		}
		//If you find this plugin useful please show your support and rate it ★★★★★ on WordPress.org - much appreciated! :)
	}

	private function render_review_request_notice(){
		$review_url = 'https://wordpress.org/support/plugin/discount-and-dynamic-pricing/reviews?rate=5#new-post';

		?>
		<div id="thwdpf_review_request_notice" class="notice notice-info is-dismissible thpladmin-notice" data-nonce="<?php echo wp_create_nonce('thwdpf_review_request_notice'); ?>" data-action="thwdpf_dismiss_review_request_notice" style="display:none">
			<h3>
				<?php esc_html_e('Just wanted to say thank you for using our Discount And Dynamic Pricing plugin in your store.', 'discount-and-dynamic-pricing') ?>
			</h3>

			<p>
				<?php esc_html_e('We hope you had a great experience. Please leave us with your feedback to serve best to you and others. Cheers!', 'discount-and-dynamic-pricing') ?>
			</p>

			<p class="action-row">
		        <button type="button" class="btn btn-primary" onclick="window.open('<?php echo $review_url; ?>', '_blank')"><?php esc_html_e('Review Now', 'discount-and-dynamic-pricing') ?></button>
		        <button type="button" class="btn" onclick="thwdpfCloseReviewRequestNotice(this)"><?php esc_html_e('Remind Me Later', 'discount-and-dynamic-pricing') ?></button>
            	<span class="logo">
            		<a target="_blank" href="https://www.themehigh.com">
            			<img src="<?php echo THWDPF_ASSETS_URL_ADMIN ?>css/logo.svg" />
            		</a>
            	</span>
			</p>
		</div>
		<?php
	}

	public function dismiss_review_request_notice(){
		$nonse = isset($_REQUEST['thwdpf_security']) ? $_REQUEST['thwdpf_security'] : false;
		$capability = THWDPF_Utils::wdpf_capability();

		if(!wp_verify_nonce($nonse, 'thwdpf_review_request_notice') || !current_user_can($capability)){
			die();
		}

		$dismiss_lifespan = apply_filters('thwdpf_dismissed_review_request_notice_lifespan', 1 * YEAR_IN_SECONDS);
		set_transient('thwdpf_review_request_notice_dismissed', true, $dismiss_lifespan);
	}

	public function skip_review_request_notice(){
		$nonse = isset($_REQUEST['thwdpf_security']) ? $_REQUEST['thwdpf_security'] : false;
		$capability = THWDPF_Utils::wdpf_capability();

		if(!wp_verify_nonce($nonse, 'thwdpf_review_request_notice') || !current_user_can($capability)){
			die();
		}

		$skip_lifespan = apply_filters('thwdpf_skip_review_request_notice_lifespan', 1 * DAY_IN_SECONDS);
		set_transient('thwdpf_skip_review_request_notice', true, $skip_lifespan);
	}
}

endif;