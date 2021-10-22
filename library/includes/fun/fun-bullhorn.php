<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!function_exists('bh_job_list_function')) {
	function bh_job_list_function()
	{
		ob_start();
		include_once(get_template_directory() . '/bullhorn/bh-job-list.php');
		return ob_get_clean();
	}
}

add_shortcode('bh_job_list', 'bh_job_list_function');


if (!function_exists('bh_job_item_function')) {
	function bh_job_item_function()
	{
		ob_start();
		include_once(get_template_directory() . '/bullhorn/bh-job-item.php');
		return ob_get_clean();
	}
}

add_shortcode('bh_job_item', 'bh_job_item_function');


if (!function_exists('bh_job_apply_function')) {
	function bh_job_apply_function()
	{
		ob_start();
		// include_once(get_template_directory() . '/bullhorn/bh-job-apply.php');
		return ob_get_clean();
	}
}

add_shortcode('bh_job_apply', 'bh_job_apply_function');
