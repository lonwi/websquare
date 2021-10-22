<?php

if (!class_exists('BullHorn_Factory')) {
	die('bullhorn plugin must be activated');
}

use SquareChilli\Bullhorn\applicationSubmitter\ApplicationSubmitter;
use SquareChilli\Bullhorn\applicationSubmitter\rules\CV;
use SquareChilli\Bullhorn\applicationSubmitter\rules\Email;
use SquareChilli\Bullhorn\applicationSubmitter\rules\Required;
use SquareChilli\Bullhorn\Client;
use SquareChilli\Bullhorn\form\File;
use SquareChilli\Bullhorn\form\RawForm;

include_once(get_template_directory() . '/bullhorn/bh-form-filters.php');
$api = BullHorn_Factory::Get()->get_api();

$bhFormFilters     = BhFormFilters::instance();
$formFilterMachine = $bhFormFilters->getFilterMachine();

$filterMachine = FilterMachine::create([
	Filter::create('jobId')->cast('int')->wpParam('bullhorn_joborder_id')->regex('~^/?job-apply/(\d+)~')->minimum(1),
	//Filter::create( 'jobId' )->cast( 'int' )->wpParam( 'bullhorn_joborder_id' )->minimum( 1 ),
	Filter::create('apply')->regex('~/apply/*$~i'),
	Filter::create('applied'),
	Filter::create('job-apply-name')->methods(Filter::METHOD_POST),
	Filter::create('job-apply-firstName')->methods(Filter::METHOD_POST),
	Filter::create('job-apply-lastName')->methods(Filter::METHOD_POST),
	Filter::create('job-apply-email')->methods(Filter::METHOD_POST),
	Filter::create('job-apply-phone')->methods(Filter::METHOD_POST),

	Filter::create('dob-day')->methods(Filter::METHOD_POST),
	Filter::create('dob-month')->methods(Filter::METHOD_POST),
	Filter::create('dob-year')->methods(Filter::METHOD_POST),

	Filter::create('countryID')->methods(Filter::METHOD_POST),

	Filter::create('customText5')->methods(Filter::METHOD_POST),
	Filter::create('experience')->methods(Filter::METHOD_POST),
	Filter::create('customText4')->methods(Filter::METHOD_POST),
	Filter::create('occupation')->methods(Filter::METHOD_POST),
	Filter::create('businessSectorID')->methods(Filter::METHOD_POST),
	Filter::create('customText6')->methods(Filter::METHOD_POST),

	Filter::create('job-apply-comments')->methods(Filter::METHOD_POST),
]);
?>
