<?php
/**
 * The PRO feature listing page for the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    discount-and-dynamic-pricing
 * @subpackage discount-and-dynamic-pricing/admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWDPF_Admin_Settings_Pro')):

class THWDPF_Admin_Settings_Pro extends THWDPF_Admin_Settings{
	protected static $_instance = null;

	public function __construct() {
		parent::__construct();
		$this->page_id = 'premium_info';
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function render_page(){
		$this->render_tabs();
		$this->render_content();
	}
	
	//TODO
	private function render_content(){
		$pro_url = 'https://www.themehigh.com/product/woocommerce-dynamic-pricing-pro/?utm_source=free&utm_medium=premium_tab&utm_campaign=wddp_upgrade_link';
		$demo_url = 'https://flydemos.com/wddp/?utm_source=free&utm_medium=banner&utm_campaign=trydemo';

		?>
		<div class="content-wrap">
			<div class="th-nice-box">
			    <h2>Key Features of Discount & Dynamic Pricing Pro</h2>
			    <p><b>Discount & Dynamic Pricing Pro</b> plugin comes with several advanced features that let you xyz.</p>
			    <ul class="feature-list star-list">
			        <li>Feature list item 1</li>
			        <li>Feature list item 2</li>
			        <li>Feature list item 3</li>
			        <li>Feature list item 4</li>
			        <li>Feature list item 5</li>
			    </ul>
			    <p>
			    	<a class="btn btn-primary-alt" target="_blank" href="<?php echo $pro_url; ?>">Upgrade to Premium Version</a>
			    	<a class="btn btn-primary-alt ml-20" target="_blank" href="<?php echo $pro_url; ?>">Try Demo</a>
				</p>
			</div>
			<div class="th-flexbox">
			    <div class="th-flexbox-child th-nice-box">
			        <h2>Main Feature</h2>
			        <p>Main feature content goes here.</p>
			        <ul class="feature-list">
			            <li>Feature list item 1</li>
				        <li>Feature list item 2</li>
				        <li>Feature list item 3</li>
				        <li>Feature list item 4</li>
				        <li>Feature list item 5</li>
				        <li>Feature list item 6</li>
			        </ul>
			    </div>
			    <div class="th-flexbox-child th-nice-box">
			        <h2>Main Feature</h2>
			        <p>Main feature content goes here.</p>
			        <ul class="feature-list">
			            <li>Feature list item 1</li>
				        <li>Feature list item 2</li>
				        <li>Feature list item 3</li>
				        <li>Feature list item 4</li>
				        <li>Feature list item 5</li>
				        <li>Feature list item 6</li>
			        </ul>
			    </div>
			</div>
			<div class="th-flexbox">
			    <div class="th-flexbox-child th-nice-box">
			        <h2>Main Feature</h2>
			        <p>Main feature content goes here.</p>
			        <ul class="feature-list">
			            <li>Feature list item 1</li>
				        <li>Feature list item 2</li>
				        <li>Feature list item 3</li>
				        <li>Feature list item 4</li>
				        <li>Feature list item 5</li>
				        <li>Feature list item 6</li>
			        </ul>
			    </div>
			    <div class="th-flexbox-child th-nice-box">
			        <h2>Main Feature</h2>
			        <p>Main feature content goes here.</p>
			        <ul class="feature-list">
			            <li>Feature list item 1</li>
				        <li>Feature list item 2</li>
				        <li>Feature list item 3</li>
				        <li>Feature list item 4</li>
				        <li>Feature list item 5</li>
				        <li>Feature list item 6</li>
			        </ul>
			    </div>
			</div>
		</div>
		<?php
	}
}

endif;