<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!function_exists('print_result')) {
	function print_result($result = "", $admin = false)
	{
		if ($result != "") {
			if ($admin) {
				if (current_user_can('manage_options')) {
					echo '<pre>';
					print_r($result);
					echo '</pre>';
				}
			} else {
				echo '<pre>';
				print_r($result);
				echo '</pre>';
			}
		}
	}
}

if (!function_exists('is_captcha_enabled')) {
	function is_captcha_enabled() {
		$recaptcha_v3_site_key = get_option('elementor_pro_recaptcha_v3_site_key');
		$recaptcha_v3_secret_key = get_option('elementor_pro_recaptcha_v3_secret_key');
		if (!empty($recaptcha_v3_site_key) && !empty($recaptcha_v3_secret_key)) {
			return true;
		}else{
			return false;
		}
	}
}
