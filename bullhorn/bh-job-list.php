<?php

if (!class_exists('BullHorn_Factory')) {
	die('bullhorn plugin must be activated');
}

use SquareChilli\Bullhorn\Client;

include_once(get_template_directory() . '/bullhorn/bh-filters.php');
// include_once(get_template_directory() . '/bullhorn/bh-form-filters.php');

$bhFilters     = BhFilters::instance();
$filterMachine = $bhFilters->getFilterMachine();
$searched      = $bhFilters->isSearched();

$paging = new \SquareChilli\Bullhorn\components\Paging($filterMachine->getFilterValue('page') - 1, 10);

// $bhFormFilters     = BhFormFilters::instance();
// $formFilterMachine = $bhFormFilters->getFilterMachine();

$jobQuery = \SquareChilli\Bullhorn\models\JobOrder::findOpen('jo')
	->addSelect('jo.address_city')
	->addSelect('jo.start_date')
	->orderBy('jo.start_date DESC')
	->paging($paging);


$jobQuery->andWhere('(jo.is_deleted = :isDeleted)', array(':isDeleted' => 0));
$jobQuery->andWhere('(jo.start_date < :start_date)', array(':start_date' => date('Y-m-d H:i:s')));

if ($searched) {
	/** @var QueryBuilder $jobQuery */

	if ($page = $filterMachine->getFilterValue('page')) {
		$jobQuery->offsetPage($page);
	}

	if ($freetext = $filterMachine->getFilterValue('freetext')) {
		$jobQuery->andWhere(
			'(jo.title LIKE :freetext OR jo.description_clean LIKE :freetext)',
			array(':freetext' => '%' . $freetext . '%')
		);
	}

	if ($category = $filterMachine->getFilterValue('field')) {
		$jobQuery->andWhere('ca.name = :category', array(':category' => $category));
	}

	if ($sector = $filterMachine->getFilterValue('sector')) {
		$jobQuery->andWhere('jo.custom_1 LIKE :sector', array(':sector' => '%' . $sector . '%'));
	}
}

/** @var \SquareChilli\Bullhorn\models\JobOrder[] $results */
$results = $jobQuery->all();
?>
<section class="bullhorn bullhorn-job-list">
	<div class="bullhorn-search-form">
		<form method="get">
			<input type="hidden" name="post_type" action="<?= Client::instance()->config()->getJobConfig()->getJobSearchUrl(true) ?>" value="bullhornjoblisting">

			<div class="container">
				<div class="row">
					<div class="col">
						<div class="bullhorn-search-form__field bullhorn-search-form__field--input">
							<div class="row align-items-center">
								<div class="col">
									<?= $filterMachine->getFilter('freetext')->inputField(array(
										'id'          => 'search',
										'placeholder' => esc_html__('Keywords', 'websquare')
									)); ?>
								</div>
								<div class="col-auto">
									<i class="fas fa-search"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md">
						<div class="bullhorn-search-form__field bullhorn-search-form__field--select">

							<?= $filterMachine->getFilter('field')->dropdown(); ?>
							<i class="fas fa-chevron-down"></i>

						</div>
					</div>
					<div class="col-md">
						<div class="bullhorn-search-form__field bullhorn-search-form__field--select">

							<?php
							$sectorFilter = $filterMachine->getFilter('sector');

							echo HtmlHelper::select(
								'sector',
								$sectorFilter->getPossibleValueTexts(false),
								$sectorFilter->getValue(),
								array(
									'class' => 'filter-select'
								),
								esc_html__('Business Sector', 'websquare')
							);

							?>
							<i class="fas fa-chevron-down"></i>
						</div>
					</div>
					<div class="col-md">
						<div class="bullhorn-search-form__field bullhorn-search-form__field--submit">
							<input class="bullhorn-search-form__submit" type="submit" value="<?php esc_html_e('Search', 'websquare'); ?>">
						</div>
					</div>
				</div>
			</div>

		</form>
	</div>
	<div class="bullhorn-search-results">
		<?php if (empty($results)) : ?>
			<div class="bullhorn-search-results--no-results">
				<div class="container">
					<div class="row">
						<h2><?php esc_html_e('Your search found no jobs within our database. Try broadening your search or contact us to discuss your job requirements.', 'websquare'); ?></h2>
					</div>
				</div>
			</div>
		<?php else : ?>
		<?php endif; ?>
		<div class="bullhorn-search-results--results">
			<div class="container">
				<?php foreach ($results as $job) : ?>
					<a href='<?php echo bh_job_url_transform($job->getURL()); ?>' rel="nofollow" class="bullhorn-search-results__item">
						<div class="row">
							<div class="col-md-5">
								<div class="bullhorn-search-results__item--job-title">
									<?= HtmlHelper::encode($job->title) ?>
								</div>
							</div>
							<div class="col-md-5">
								<div class="bullhorn-search-results__item--job-location">
									<?= HtmlHelper::encode($job->custom_1) ?>
								</div>
							</div>
							<div class="col-md-2 d-flex justify-content-end">
								<div class="bullhorn-search-results__item--job-date">
									<?php
									$time_ago = human_time_diff(date(
										'U',
										strtotime($job->start_date)
									));
									// echo $time_ago;
									?>
									<?php esc_html_e($time_ago .' ago', 'websquare'); ?>
								</div>
							</div>
						</div>
					</a>
				<?php endforeach; ?>

			</div>
		</div>
	</div>
	<?php if ($paging->getTotalPages() > 1) :
		$paginationUri = URI::getCurrent(Client::instance()->config()->getJobConfig()->getJobSearchUrl(true));

		$currentPage = $paging->getCurrentPage(false);
		$totalPages  = $paging->getTotalPages();

		$paginationPages = $paging->getPages();
		$previousPage    = $paging->getPreviousPage();
		$nextPage        = $paging->getNextPage();
	?>
		<div class="bullhorn-search-pagination">
			<div class="container">
				<div class="row">
					<div class="col col-lg-auto d-flex justify-content-start">
						<a href="<?= $paginationUri->setQuery('_page', $previousPage)->href() ?>" class="bullhorn-search-pagination__button bullhorn-search-pagination__button--back <?= empty($previousPage) ? 'bullhorn-search-pagination__button--disabled' : ''; ?>">
							<i class="fas fa-chevron-left"></i>
							<span><?php esc_html_e('Previous Page', 'websquare'); ?></span>
						</a>
					</div>
					<div class="col d-none d-lg-block">
						<div class="row justify-content-center">
							<?php
							foreach ($paginationPages as $paginationPage) {
								$paginationPage = (int) $paginationPage;

								$attributes = [];
								$a = [];
								$attributes['class'][] = 'col-auto';
								$a['class'][] = 'bullhorn-search-pagination__button--number';

								if ($currentPage === $paginationPage) {
									$attributes['class'][] = 'active';
									$a['class'][] = 'bullhorn-search-pagination__button--active';
								}

								$paginationUri->setQuery('_page', $paginationPage);

								echo HtmlHelper::tag(
									'div',
									HtmlHelper::a($paginationUri->href(), $paginationPage, $a),
									$attributes
								);
							}
							?>
						</div>
					</div>
					<div class="col col-lg-auto d-flex justify-content-end">
						<a href="<?= $paginationUri->setQuery('_page', $nextPage)->href() ?>" class="bullhorn-search-pagination__button bullhorn-search-pagination__button--next <?= empty($nextPage) ? 'bullhorn-search-pagination__button--disabled' : ''; ?>">
							<span><?php esc_html_e('Next Page', 'websquare'); ?></span>
							<i class="fas fa-chevron-right"></i>
						</a>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</section>
