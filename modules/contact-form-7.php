<?php


class slider_captcha_cf7 extends sliderCaptchaModule {

	public $name = "Contact Form 7";

	public $instance_number = 0;

	const VALID_SESSON = '_slider_captcha_valid';

	public function __construct($machine_name, &$instance) {
		parent::__construct($machine_name, $instance);

		session_start();

		if ($this->is_enabled()) {
			add_action('init', array(&$this, 'add_slider_captcha_shortcode'));
			//Register Contact Form 7 shortcode
			add_action('wpcf7_admin_init', array(&$this, 'add_tag_generator'), 45);
		}

	}

	public function init_hooks() {
		if ($this->sliderCaptcha->is_slider_enabled($this->machine_name)) {
			//Load the messages
			add_filter('wpcf7_messages', array(&$this,'cf7_messages'));
			//Validate the captcha
			add_filter('wpcf7_validate_slidercaptcha', array(&$this, 'cf7_validate_captcha'), 10, 2);
		}

	}

	public function is_enabled() {
		return class_exists('WPCF7_ContactForm');
	}

	public function add_slider_captcha_shortcode() {
		wpcf7_add_shortcode('slidercaptcha', array(&$this, 'slidercaptcha_shortcode'), true);
	}

	public function slidercaptcha_shortcode($tag) {

		if (isset($_SESSION[self::VALID_SESSON])) unset($_SESSION[self::VALID_SESSON]);

		if (!$this->sliderCaptcha->is_slider_enabled($this->machine_name)) return;

		$validation_error = wpcf7_get_validation_error($tag['type']);
		$class = wpcf7_form_controls_class($tag['type']);
		
		//Generate the instance number, to allow multiple sliders on same form.
		$instance = $this->instance_number++;

		$validation = '';

		if ($validation_error)
			$validation = "<span role='alert' style=\"display: block;\" class='wpcf7-not-valid-tip'>$validation_error</span>";

		return	'<span class="wpcf7-form-control-wrap slidercaptcha"><div id="'.$tag['type'].$instance.'"></div>' . $validation . '
		<script type="text/javascript">
		jQuery(function($) {
			$( document ).ready(function() {
					//Load the slider captcha
					$("#'.$tag['type'].$instance.'").sliderCaptcha('.  json_encode($this->sliderCaptcha->get_slider($this->machine_name)) .');
			});
		});
		</script></span>';
	}

	function add_tag_generator() {
		if (!class_exists('WPCF7_TagGenerator'))
			return;

		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add(
			'slidercaptcha',
			__( 'Slider CAPTCHA', 'slider_captcha' ),
			array(&$this, 'tag_generator_rendering')
		);
	}

	function tag_generator_rendering() {
	?>
		<div id="slider-captcha">
			<p><?php echo esc_attr( __( 'To custumize the Slider CAPTCHA you must go to the Slider CAPTCHA\'s settings panel and change the layout.', 'slider_captcha' ) ); ?></p>
						
			<input style="float: left; width: 240px;" type="text" name="slidercaptcha" class="tag code" readonly="readonly" onfocus="this.select()" />
			<span style="float: left;">&nbsp; &nbsp;</span>
			<input style="float: left;" type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'slider_captcha' ) ); ?>" />
		</div>
	<?php
	}


	function cf7_messages($messages){
		return array_replace_recursive(
			$messages,
			array(
				'bypassed' => array(
					'description' => __('Invalid CAPTCHA value.', 'slider-captcha'),
					'default' => __("ERROR: Something went wrong with the CAPTCHA validation. Please make sure you have JavaScript & Cookies enabled on your browser.",'slider_captcha')
				)
			)
		);
	}

	function cf7_validate_captcha($result, $tag) {

		if (!is_admin()) {
			if (!$this->sliderCaptcha->valid_request()) {

				if (isset($_SESSION[self::VALID_SESSON])) return $result;

				$tag['name'] = 'slidercaptcha';

				$tag = new WPCF7_Shortcode( $tag );
				$result->invalidate( $tag, wpcf7_get_message('bypassed') );
				$result['valid'] = 0;

			} else {
				$_SESSION[self::VALID_SESSON] = 1;
			}
		}

		return $result;
	}

}