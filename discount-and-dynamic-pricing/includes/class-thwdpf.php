<?php
/**
 * The file that defines the core plugin class.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF')):

class THWDPF {
	protected $plugin_name;
	protected $version;
	const TEXT_DOMAIN = 'discount-and-dynamic-pricing';

	public function __construct() {
		$this->load_dependencies();
		$this->set_plugin_info();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		add_action('admin_footer-plugins.php', array($this, 'wdpf_deactivation_form'));
		add_action('wp_ajax_thwdpf_deactivation_reason', array($this, 'thwdpf_deactivation_reason'));

		add_action('init', array($this, 'init'));
	}

	private function set_plugin_info(){
		$plugin_data = get_plugin_data( THWDPF_FILE );

		$this->plugin_name = 'discount-and-dynamic-pricing';
		$this->version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '1.0.0';
		define('THWDPF_VERSION', $this->version);
	}

	private function load_dependencies() {
		if(!function_exists('is_plugin_active')){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwdpf-autoloader.php';
	}

	private function set_locale() {
		add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
	}

	public function load_plugin_textdomain(){
		$locale = apply_filters('plugin_locale', get_locale(), self::TEXT_DOMAIN);
	
		load_textdomain(self::TEXT_DOMAIN, WP_LANG_DIR.'/discount-and-dynamic-pricing/'.self::TEXT_DOMAIN.'-'.$locale.'.mo');
		load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname(THWDPF_BASE_NAME) . '/languages/');
	}

	private function define_admin_hooks() {
		$plugin_admin = new THWDPF_Admin( $this->get_plugin_name(), $this->get_version() );

		add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles_and_scripts'));
		add_action('admin_menu', array($plugin_admin, 'admin_menu'));
		add_filter('woocommerce_screen_ids', array($plugin_admin, 'add_screen_id'));
		add_filter('plugin_action_links_'.THWDPF_BASE_NAME, array($plugin_admin, 'plugin_action_links'));
		//add_filter('plugin_row_meta', array($plugin_admin, 'plugin_row_meta'), 10, 2);

		$wdpf_data = THWDPF_Data::instance();
	}

	private function define_public_hooks() {
		$discount_product = new THWDPF_Public_Discount_Product($this->get_plugin_name(), $this->get_version());
		$discount_cart = new THWDPF_Public_Discount_Cart($this->get_plugin_name(), $this->get_version());
		$plugin_public = new THWDPF_Public( $this->get_plugin_name(), $this->get_version() );

		add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_public_styles_and_scripts'));
	}

	public function init(){
		$this->define_constants();
	}
	
	private function define_constants(){
		!defined('THWDPF_ASSETS_URL_ADMIN') && define('THWDPF_ASSETS_URL_ADMIN', THWDPF_URL . 'admin/assets/');
		!defined('THWDPF_ASSETS_URL_PUBLIC') && define('THWDPF_ASSETS_URL_PUBLIC', THWDPF_URL . 'public/assets/');
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}


	public function wdpf_deactivation_form(){
		$is_snooze_time = get_user_meta( get_current_user_id(), 'thwdpf_deactivation_snooze', true );
		$now = time();

		if($is_snooze_time && ($now < $is_snooze_time)){
			return;
		}

		$deactivation_reasons = $this->get_deactivation_reasons();
		?>
		<div id="thwdpf_deactivation_form" class="thpladmin-modal-mask">
			<div class="thpladmin-modal">
				<div class="modal-container">
					<!-- <span class="modal-close" onclick="thwdpfCloseModal(this)">×</span> -->
					<div class="modal-content">
						<div class="modal-body">
							<div class="model-header">
								<img class="th-logo" src="<?php echo esc_url(THWDPF_URL .'admin/assets/css/themehigh.svg'); ?>" alt="themehigh-logo">
								<span><?php echo __('Quick Feedback', 'discount-and-dynamic-pricing'); ?></span>
							</div>

							<!-- <div class="get-support-version-b">
								<p>We are sad to see you go. We would be happy to fix things for you. Please raise a ticket to get help</p>
								<a class="thwdpf-link thwdpf-right-link thwdpf-active" target="_blank" href="https://help.themehigh.com/hc/en-us/requests/new?utm_source=thwdpf_free&utm_medium=feedback_form&utm_campaign=get_support"><?php echo __('Get Support', 'discount-and-dynamic-pricing'); ?></a>
							</div> -->

							<main class="form-container main-full">
								<p class="thwdpf-title-text"><?php echo __('If you have a moment, please let us know why you want to deactivate this plugin', 'discount-and-dynamic-pricing'); ?></p>
								<ul class="deactivation-reason" data-nonce="<?php echo wp_create_nonce('thwdpf_deactivate_nonce'); ?>">
									<?php 
									if($deactivation_reasons){
										foreach($deactivation_reasons as $key => $reason){
											$reason_type = isset($reason['reason_type']) ? $reason['reason_type'] : '';
											$reason_placeholder = isset($reason['reason_placeholder']) ? $reason['reason_placeholder'] : '';
											?>
											<li data-type="<?php echo esc_attr($reason_type); ?>" data-placeholder="<?php echo esc_attr($reason_placeholder); ?> ">
												<label>
													<input type="radio" name="selected-reason" value="<?php echo esc_attr($key); ?>">
													<span><?php echo esc_html($reason['radio_label']); ?></span>
												</label>
											</li>
											<?php
										}
									}
									?>
								</ul>
								<p class="thwdpf-privacy-cnt"><?php echo __('This form is only for getting your valuable feedback. We do not collect your personal data. To know more read our ', 'discount-and-dynamic-pricing'); ?> <a class="thwdpf-privacy-link" target="_blank" href="<?php echo esc_url('https://www.themehigh.com/privacy-policy/');?>"><?php echo __('Privacy Policy', 'discount-and-dynamic-pricing'); ?></a></p>
							</main>
							<footer class="modal-footer">
								<div class="thwdpf-left">
									<a class="thwdpf-link thwdpf-left-link thwdpf-deactivate" href="#"><?php echo __('Skip & Deactivate', 'discount-and-dynamic-pricing'); ?></a>
								</div>
								<div class="thwdpf-right">
									<a class="thwdpf-link thwdpf-right-link thwdpf-active" target="_blank" href="https://help.themehigh.com/hc/en-us/requests/new?utm_source=thwdpf_free&utm_medium=feedback_form&utm_campaign=get_support"><?php echo __('Get Support', 'discount-and-dynamic-pricing'); ?></a>
									<a class="thwdpf-link thwdpf-right-link thwdpf-active thwdpf-submit-deactivate" href="#"><?php echo __('Submit and Deactivate', 'discount-and-dynamic-pricing'); ?></a>
									<a class="thwdpf-link thwdpf-right-link thwdpf-close" href="#"><?php echo __('Cancel', 'discount-and-dynamic-pricing'); ?></a>
								</div>
							</footer>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style type="text/css">
			.th-logo{
			    margin-right: 10px;
			}
			.thpladmin-modal-mask{
			    position: fixed;
			    background-color: rgba(17,30,60,0.6);
			    top: 0;
			    left: 0;
			    width: 100%;
			    height: 100%;
			    z-index: 9999;
			    overflow: scroll;
			    transition: opacity 250ms ease-in-out;
			}
			.thpladmin-modal-mask{
			    display: none;
			}
			.thpladmin-modal .modal-container{
			    position: absolute;
			    background: #fff;
			    border-radius: 2px;
			    overflow: hidden;
			    left: 50%;
			    top: 50%;
			    transform: translate(-50%,-50%);
			    width: 50%;
			    max-width: 960px;
			    /*min-height: 560px;*/
			    /*height: 80vh;*/
			    /*max-height: 640px;*/
			    animation: appear-down 250ms ease-in-out;
			    border-radius: 15px;
			}
			.model-header {
			    padding: 21px;
			}
			.thpladmin-modal .model-header span {
			    font-size: 18px;
			    font-weight: bold;
			}
			.thpladmin-modal .model-header {
			    padding: 21px;
			    background: #ECECEC;
			}
			.thpladmin-modal .form-container {
			    margin-left: 23px;
			    clear: both;
			}
			.thpladmin-modal .deactivation-reason input {
			    margin-right: 13px;
			}
			.thpladmin-modal .thwdpf-privacy-cnt {
			    color: #919191;
			    font-size: 12px;
			    margin-bottom: 31px;
			    margin-top: 18px;
			    max-width: 75%;
			}
			.thpladmin-modal .deactivation-reason li {
			    margin-bottom: 17px;
			}
			.thpladmin-modal .modal-footer {
			    padding: 20px;
			    border-top: 1px solid #E7E7E7;
			    float: left;
			    width: 100%;
			    box-sizing: border-box;
			}
			.thwdpf-left {
			    float: left;
			}
			.thwdpf-right {
			    float: right;
			}
			.thwdpf-link {
			    line-height: 31px;
			    font-size: 12px;
			}
			.thwdpf-left-link {
			    font-style: italic;
			}
			.thwdpf-right-link {
			    padding: 0px 20px;
			    border: 1px solid;
			    display: inline-block;
			    text-decoration: none;
			    border-radius: 5px;
			}
			.thwdpf-right-link.thwdpf-active {
			    background: #0773AC;
			    color: #fff;
			}
			.thwdpf-title-text {
			    color: #2F2F2F;
			    font-weight: 500;
			    font-size: 15px;
			}
			.reason-input {
			    margin-left: 31px;
			    margin-top: 11px;
			    width: 70%;
			}
			.reason-input input {
			    width: 100%;
			    height: 40px;
			}
			.reason-input textarea {
			    width: 100%;
			    min-height: 80px;
			}
			input.th-snooze-checkbox {
			    width: 15px;
			    height: 15px;
			}
			input.th-snooze-checkbox:checked:before {
			    width: 1.2rem;
			    height: 1.2rem;
			}
			.th-snooze-select {
			    margin-left: 20px;
			    width: 172px;
			}

			/* Version B */
			.get-support-version-b {
			    width: 100%;
			    padding-left: 23px;
			    clear: both;
			    float: left;
			    box-sizing: border-box;
			    background: #0673ab;
			    color: #fff;
			    margin-bottom: 20px;
			}
			.get-support-version-b p {
			    font-size: 12px;
			    line-height: 17px;
			    width: 70%;
			    display: inline-block;
			    margin: 0px;
			    padding: 15px 0px;
			}
			.get-support-version-b .thwdpf-right-link {
			    background-image: url(<?php echo esc_url(THWDPF_URL .'admin/assets/css/get_support_icon.svg'); ?>);
			    background-repeat: no-repeat;
			    background-position: 11px 10px;
			    padding-left: 31px;
			    color: #0773AC;
			    background-color: #fff;
			    float: right;
			    margin-top: 17px;
			    margin-right: 20px;
			}
			.thwdpf-privacy-link {
			    font-style: italic;
			}
			.thwdpf-review-link {
			    margin-top: 7px;
			    margin-left: 31px;
			    font-size: 16px;
			}
			span.thwdpf-rating-link {
			    color: #ffb900;
			}
			.thwdpf-review-and-deactivate {
			    text-decoration: none;
			}
		</style>

		<script type="text/javascript">
			(function($){
				var popup = $("#thwdpf_deactivation_form");
				var deactivation_link = '';

				$('.thwdpf-deactivate-link').on('click', function(e){
					e.preventDefault();
					deactivation_link = $(this).attr('href');
					popup.css("display", "block");
					popup.find('a.thwdpf-deactivate').attr('href', deactivation_link);
				});

				popup.on('click', 'input[type="radio"]', function () {
					var parent = $(this).parents('li:first');
	                popup.find('.reason-input').remove();

	                var type = parent.data('type');
	                var placeholder = parent.data('placeholder');

	                var reason_input = '';
	                if('text' == type){
	                	reason_input += '<div class="reason-input">';
	                	reason_input += '<input type="text" placeholder="'+ placeholder +'">';
	                	reason_input += '</div>';
	                }else if('textarea' == type){
	                	reason_input += '<div class="reason-input">';
	                	reason_input += '<textarea row="5" placeholder="'+ placeholder +'">';
	                	reason_input += '</textarea>';
	                	reason_input += '</div>';
	                }else if('checkbox' == type){
	                	reason_input += '<div class="reason-input ">';
	                	reason_input += '<input type="checkbox" id="th-snooze" name="th-snooze" class="th-snooze-checkbox">';
	                	reason_input += '<label for="th-snooze">Snooze this panel while troubleshooting</label>';
	                	reason_input += '<select name="th-snooze-time" class="th-snooze-select" disabled>';
	                	reason_input += '<option value="<?php echo HOUR_IN_SECONDS ?>">1 Hour</option>';
	                	reason_input += '<option value="<?php echo 12*HOUR_IN_SECONDS ?>">12 Hour</option>';
	                	reason_input += '<option value="<?php echo DAY_IN_SECONDS ?>">24 Hour</option>';
	                	reason_input += '<option value="<?php echo WEEK_IN_SECONDS ?>">1 Week</option>';
	                	reason_input += '<option value="<?php echo MONTH_IN_SECONDS ?>">1 Month</option>';
	                	reason_input += '</select>';
	                	reason_input += '</div>';
	                }else if('reviewlink' == type){
	                	reason_input += '<div class="reason-input thwdpf-review-link">';
	                	/*
	                	reason_input += '<?php _e('Deactivate and ', 'discount-and-dynamic-pricing');?>'
	                	reason_input += '<a href="#" target="_blank" class="thwdpf-review-and-deactivate">';
	                	reason_input += '<?php _e('leave a review', 'discount-and-dynamic-pricing'); ?>';
	                	reason_input += '<span class="thwdpf-rating-link"> &#9733;&#9733;&#9733;&#9733;&#9733; </span>';
	                	reason_input += '</a>';
	                	*/
	                	reason_input += '<input type="hidden" value="<?php _e('Upgraded', 'discount-and-dynamic-pricing');?>">';
	                	reason_input += '</div>';
	                }

	                if(reason_input !== ''){
	                	parent.append($(reason_input));
	                }
				});

				popup.on('click', '.thwdpf-close', function () {
					popup.css("display", "none");
				});

				/*
				popup.on('click', '.thwdpf-review-and-deactivate', function () {
					e.preventDefault();
	                window.open("https://wordpress.org/support/plugin/discount-and-dynamic-pricing/reviews/?rate=5#new-post");
	                console.log(deactivation_link);
	                window.location.href = deactivation_link;
				});
				*/

				popup.on('click', '.thwdpf-submit-deactivate', function (e) {
	                e.preventDefault();
	                var button = $(this);
	                if (button.hasClass('disabled')) {
	                    return;
	                }
	                var radio = $('.deactivation-reason input[type="radio"]:checked');
	                var parent_li = radio.parents('li:first');
	                var parent_ul = radio.parents('ul:first');
	                var input = parent_li.find('textarea, input[type="text"], input[type="hidden"]');
	                var thwdpf_deacive_nonce = parent_ul.data('nonce');

	                $.ajax({
	                    url: ajaxurl,
	                    type: 'POST',
	                    data: {
	                        action: 'thwdpf_deactivation_reason',
	                        reason: (0 === radio.length) ? 'none' : radio.val(),
	                        comments: (0 !== input.length) ? input.val().trim() : '',
	                        security: thwdpf_deacive_nonce,
	                    },
	                    beforeSend: function () {
	                        button.addClass('disabled');
	                        button.text('Processing...');
	                    },
	                    complete: function () {
	                        window.location.href = deactivation_link;
	                    }
	                });
	            });

	            popup.on('click', '#th-snooze', function () {
	            	if($(this).is(':checked')){
	            		popup.find('.th-snooze-select').prop("disabled", false);
	            	}else{
	            		popup.find('.th-snooze-select').prop("disabled", true);
	            	}
				});

			}(jQuery))
		</script>
		<?php 
	}

	private function get_deactivation_reasons(){
		return array(
			// 'upgraded_to_wdp_pro' => array(
			// 	'radio_val'          => 'upgraded_to_wdp_pro',
			// 	'radio_label'        => __('Upgraded to premium.', 'discount-and-dynamic-pricing'),
			// 	'reason_type'        => 'reviewlink',
			// 	'reason_placeholder' => '',
			// ),

			'feature_missing'=> array(
				'radio_val'          => 'feature_missing',
				'radio_label'        => __('A specific feature is missing', 'discount-and-dynamic-pricing'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('Propose the feature', 'discount-and-dynamic-pricing'),
			),

			'error_or_not_working'=> array(
				'radio_val'          => 'error_or_not_working',
				'radio_label'        => __('Found an error in the plugin/ Plugin was not working', 'discount-and-dynamic-pricing'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('Specify the issue', 'discount-and-dynamic-pricing'),
			),

			'hard_to_use' => array(
				'radio_val'          => 'hard_to_use',
				'radio_label'        => __('It was hard to use', 'discount-and-dynamic-pricing'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('How can we improve your experience?', 'discount-and-dynamic-pricing'),
			),

			'found_better_plugin' => array(
				'radio_val'          => 'found_better_plugin',
				'radio_label'        => __('I found a better Plugin', 'discount-and-dynamic-pricing'),
				'reason_type'        => 'text',
				'reason_placeholder' => __('Could you please mention the plugin?', 'discount-and-dynamic-pricing'),
			),

			// 'not_working_as_expected'=> array(
			// 	'radio_val'          => 'not_working_as_expected',
			// 	'radio_label'        => __('The plugin didn’t work as expected', 'discount-and-dynamic-pricing'),
			// 	'reason_type'        => 'text',
			// 	'reason_placeholder' => __('Specify the issue', 'discount-and-dynamic-pricing'),
			// ),

			'temporary' => array(
				'radio_val'          => 'temporary',
				'radio_label'        => __('It’s a temporary deactivation - I’m troubleshooting an issue', 'discount-and-dynamic-pricing'),
				'reason_type'        => 'checkbox',
				'reason_placeholder' => __('Could you please mention the plugin?', 'discount-and-dynamic-pricing'),
			),

			'other' => array(
				'radio_val'          => 'other',
				'radio_label'        => __('Not mentioned here', 'discount-and-dynamic-pricing'),
				'reason_type'        => 'textarea',
				'reason_placeholder' => __('Kindly tell us your reason, so that we can improve', 'discount-and-dynamic-pricing'),
			),
		);
	}

	public function thwdpf_deactivation_reason(){
		global $wpdb;

		check_ajax_referer('thwdpf_deactivate_nonce', 'security');

		if(!isset($_POST['reason'])){
			return;
		}

		if($_POST['reason'] === 'temporary'){

			$snooze_period = isset($_POST['th-snooze-time']) && $_POST['th-snooze-time'] ? $_POST['th-snooze-time'] : MINUTE_IN_SECONDS ;
			$time_now = time();
			$snooze_time = $time_now + $snooze_period;

			update_user_meta(get_current_user_id(), 'thwdpf_deactivation_snooze', $snooze_time);

			return;
		}
		
		$data = array(
			'plugin'        => 'thwdpf',
			'reason' 	    => sanitize_text_field($_POST['reason']),
			'comments'	    => isset($_POST['comments']) ? sanitize_textarea_field(wp_unslash($_POST['comments'])) : '',
	        'date'          => gmdate("M d, Y h:i:s A"),
	        'software'      => $_SERVER['SERVER_SOFTWARE'],
	        'php_version'   => phpversion(),
	        'mysql_version' => $wpdb->db_version(),
	        'wp_version'    => get_bloginfo('version'),
	        'wc_version'    => (!defined('WC_VERSION')) ? '' : WC_VERSION,
	        'locale'        => get_locale(),
	        'multisite'     => is_multisite() ? 'Yes' : 'No',
	        'plugin_version'=> THWDPF_VERSION
		);

		$response = wp_remote_post('https://feedback.themehigh.in/api/add_feedbacks', array(
	        'method'      => 'POST',
	        'timeout'     => 45,
	        'redirection' => 5,
	        'httpversion' => '1.0',
	        'blocking'    => false,
	        'headers'     => array( 'Content-Type' => 'application/json' ),
	        'body'        => json_encode($data),
	        'cookies'     => array()
	            )
	    );

	    wp_send_json_success();
	}
}

endif;