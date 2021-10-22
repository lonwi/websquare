<?php
if (!class_exists('BullHorn_Factory')) {
	die('bullhorn plugin must be activated');
}

$api = BullHorn_Factory::Get()->get_api();
$filterMachine = FilterMachine::create([
	Filter::create('jobId')->cast('int')->wpParam('bullhorn_joborder_id')->regex('~^/?job/(\d+)~')->minimum(1),
]);

try {
	$jobIdFilter = $filterMachine->getFilter('jobId');
	$jobId       = $jobIdFilter->getValue();
	if (empty($jobId)) {
		// wp_redirect('/recruiting/explore-job-opportunities/');
		exit;
	}
	$jobOrder = $api->GetJob($jobId, false, false, 'bullhorn_id');
	if (empty($jobOrder)) {
		throw new \Exception(sprintf('Could not find job by ID "%s"', $jobId));
	}

} catch (\Exception $e) {
	$errorMessage = $e->getMessage();
	if (!empty($_COOKIE['applyDebug'])) {
		echo '<pre>' . print_r(HtmlHelper::encode($errorMessage), true) . '</pre>';
	}
}
?>
<section class="bullhorn bullhorn-job">
	<div class="bullhorn-job__title">
		<h1 class="elementor-heading-title elementor-size-default"><?= HtmlHelper::encode($jobOrder->title) ?></h1>
	</div>
	<div class="bullhorn-job__meta">
		<?php if(isset($jobOrder->address->city) && !empty($jobOrder->address->city)):?>
		<div class="bullhorn-job__meta-item bullhorn-job__meta-item--location">
			<span><?php esc_html_e('Location: ', 'websquare'); ?></span>
			<?= HtmlHelper::encode($jobOrder->address->city) ?>
		</div>
		<?php endif;?>
		<?php if(isset($jobOrder->dateAdded) && !empty($jobOrder->dateAdded)):?>
		<div class="bullhorn-job__meta-item bullhorn-job__meta-item--date">
			<span><?php esc_html_e('Date Posted: ', 'websquare'); ?></span>
			<?= date_i18n('F d, Y', strtotime($jobOrder->dateAdded)); ?>
		</div>
		<?php endif;?>
	</div>
	<div class="bullhorn-job__description">
		<?= $jobOrder->description->GetDescription() ?>
	</div>

</section>