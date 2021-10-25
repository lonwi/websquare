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
		include_once(get_template_directory() . '/bullhorn/bh-job-apply.php');
		return ob_get_clean();
	}
}

add_shortcode('bh_job_apply', 'bh_job_apply_function');

if (!function_exists('bh_document_title_parts_function')) {
	function bh_document_title_parts_function($titles)
	{
		$api = BullHorn_Factory::Get()->get_api();
		$filterMachine = FilterMachine::create([
			Filter::create('jobId')->cast('int')->wpParam('bullhorn_joborder_id')->regex('~^/?job/(\d+)~')->minimum(1),
		]);
		$jobIdFilter = $filterMachine->getFilter('jobId');
		$jobId       = $jobIdFilter->getValue();
		if (!empty($jobId)) {
			$jobOrder = $api->GetJob($jobId, false, false, 'bullhorn_id');
			if (isset($jobOrder) && !empty($jobOrder)) {
				$titles['title'] = sprintf(__('Job: %s', 'websquare'), $jobOrder->title);
			} else {
				$titles['title'] = __('Job not found', 'websquare');
			}
		}
		return $titles;
	}
}

add_filter('document_title_parts', 'bh_document_title_parts_function', 10, 1);

if (!function_exists('bh_job_rewrite_rule')) {
	function bh_job_rewrite_rule()
	{
		add_rewrite_rule('^job/([0-9]{1,})/(.*)$', 'index.php?pagename=job&jobId=$matches[1]', 'top');
		add_rewrite_rule('^ar/job/([0-9]{1,})/(.*)$', 'index.php?pagename=job&jobId=$matches[1]', 'top');
	}
}

add_action('init', 'bh_job_rewrite_rule', 10, 0);


if (!function_exists('bh_job_url_transform')) {
	function bh_job_url_transform($url)
	{
		$current_lang = pll_current_language();
		$default_lang = pll_default_language();
		if ($current_lang != $default_lang) {
			$url = str_replace('/job/', '/' . $current_lang . '/job/', $url);
		}
		return $url;
	}
}


if (!function_exists('bh_websquare_register_scripts')) {
	function bh_websquare_register_scripts()
	{
		global $post;

		if (is_captcha_enabled() && is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'bh_job_item') || has_shortcode($post->post_content, 'bh_job_apply'))) {
			$src = 'https://www.google.com/recaptcha/api.js?render=explicit';
			wp_register_script('elementor-recaptcha_v3-api-js', $src, '3.4.1', true);
			wp_enqueue_script('elementor-recaptcha_v3-api-js');
		}
	}
}

add_action('wp_enqueue_scripts', 'bh_websquare_register_scripts');
