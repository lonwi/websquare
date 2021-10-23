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


add_filter('document_title_parts', function ($titles) {
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
});

function bh_job_rewrite_rule()
{
	add_rewrite_rule('job/([0-9]{1,})/(.*)$', 'index.php?pagename=job&jobId=$matches[1]', 'top');
}
add_action('init', 'bh_job_rewrite_rule', 10, 0);
