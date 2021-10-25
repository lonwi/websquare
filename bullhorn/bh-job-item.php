<?php
if (!class_exists('BullHorn_Factory')) {
	die('bullhorn plugin must be activated');
}

use SquareChilli\Bullhorn\Bullhorn;
use SquareChilli\Bullhorn\Client;
use SquareChilli\Bullhorn\components\Logger;
use SquareChilli\Bullhorn\helpers\DevHelper;
use SquareChilli\Bullhorn\helpers\Smarty;
use SquareChilli\Bullhorn\models\Application;

if (is_captcha_enabled()) {
	$recaptcha_v3_site_key = get_option('elementor_pro_recaptcha_v3_site_key');
	$recaptcha_v3_secret_key = get_option('elementor_pro_recaptcha_v3_secret_key');
	$recaptcha_v3_threshold = get_option('elementor_pro_recaptcha_v3_threshold');
	$recaptcha_v3 = true;
}

// print_result($recaptcha_v3);
$api = BullHorn_Factory::Get()->get_api();

include_once(get_template_directory() . '/bullhorn/bh-form-filters.php');
$bhFormFilters     = BhFormFilters::instance();
$formFilterMachine = $bhFormFilters->getFilterMachine();

$filterMachine = FilterMachine::create([
	Filter::create('jobId')->cast('int')->wpParam('bullhorn_joborder_id')->regex('~^/?job/(\d+)~')->minimum(1),
	Filter::create('jobIdAR')->cast('int')->wpParam('bullhorn_joborder_id')->regex('~^/?\w+/?job/(\d+)~')->minimum(1),
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

$applyError         = null;
$applyErrorExtended = null;
$errorMessage       = null;
$applied = $filterMachine->getFilter('applied')->exists();

try {
	$jobIdFilter = $filterMachine->getFilter('jobId');
	$jobIdFilterAR = $filterMachine->getFilter('jobIdAR');
	$jobId       = !empty($jobIdFilter->getValue()) ? $jobIdFilter->getValue() : $jobIdFilterAR->getValue();

	if (!empty($jobId)) {
		$jobOrder = $api->GetJob($jobId, false, false, 'bullhorn_id');
	}

	if (isset($jobOrder) && !empty($jobOrder)) {
		$jobOrderModel = $jobOrder->GetModel();
		// $metaMachine = Bullhorn::instance()->metaMachine()->openGraphJob($jobOrderModel);
		// $metaMachine->addProperty('og:description', 'HRInvest');

		if (!empty($_POST['submit'])) {

			if (isset($recaptcha_v3)) {
				$token = $_POST['token'];
				$action = $_POST['action'];

				// call curl to POST request
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptcha_v3_secret_key, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
			}

			// verify the response
			if (!isset($recaptcha_v3) || ($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= $recaptcha_v3_threshold)) {
				// valid submission
				// go ahead and do necessary stuff

				if (empty($_FILES['job-apply-cv'])) {
					$_FILES['job-apply-cv'] = array(
						'tmp_name' => null,
						'filename' => null,
						'error' => 4, // no file uploaded
						'type' => null,
						'size' => 0,
					);
				}

				$applyResult = $jobOrder->SubmitApplication($_POST, $_FILES['job-apply-cv']);
				if (!empty($_COOKIE['applyDebug'])) {
					echo '<pre>' . print_r($applyResult, true) . '</pre>';
				}
				// print_result($jobOrder);
				if ($applyResult) {
					$application = Application::findByPk($applyResult->id);
					$applicationUser = $application->getUser();

					if (!empty($applicationUser->email)) {
						$email = Client::instance()->config()->getEmailConfig()->getEmail('thankYouJobOrder');
						$email->setData([
							'application' => $application,
							'applicationUser' => $applicationUser,
							'jobOrder' => $jobOrderModel,
						]);
						$email->sendTo($applicationUser->email);
					}
					// $redirect_url = $jobOrder->getURL() . '?applied#applied';
					// wp_redirect($redirect_url);
					// exit;
					$applied = true; // Sometimes the redirect simply fails

				} else {
					$applyError = BullHorn_Errors::toText($jobOrder->GetError(), $jobOrder->GetErrorExtended(), false);
					$applyErrorExtended = $jobOrder->GetErrorExtended();

					if (!empty($_COOKIE['applyDebug'])) {
						var_dump($applyError);
						var_dump($applyErrorExtended);
					}
				}
			} else {
				// Captcha Failed
				echo "<script>alert('Validation Failed');</script>";
			}
		}
	}
} catch (\Exception $e) {
	$errorMessage = $e->getMessage();
	if (!empty($_COOKIE['applyDebug'])) {
		echo '<pre>' . print_r(HtmlHelper::encode($errorMessage), true) . '</pre>';
	}
}
?>

<?php if (empty($jobOrder) && empty($errorMessage)) : ?>
	<section class="bullhorn bullhorn-error">
		<div class="container">
			<div class="row d-flex align-items-center">
				<div class="col-auto">
					<i class="fas fa-exclamation-triangle"></i>
				</div>
				<div class="col">
					<h1 class="elementor-heading-title elementor-size-default">
						<?php esc_html_e('Job details not found!', 'websquare'); ?>
					</h1>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if (!empty($errorMessage)) : ?>
	<section class="bullhorn bullhorn-error">
		<div class="container">
			<div class="row d-flex align-items-center">
				<div class="col-auto">
					<i class="fas fa-exclamation-triangle"></i>
				</div>
				<div class="col">
					<p><?= HtmlHelper::encode($errorMessage) ?></p>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if (empty($errorMessage) && !empty($jobOrder)) : ?>
	<?php if ($applied) : ?>
		<section class="bullhorn bullhorn-thank-you">
			<div class="container">
				<div class="row d-flex align-items-top">
					<div class="col-auto">
						<i class="fas fa-check"></i>
					</div>
					<div class="col">
						<h1 class="elementor-heading-title elementor-size-default">
							<?php esc_html_e('Your application has been successfully submitted.', 'websquare'); ?>
						</h1>
						<p><?php esc_html_e('You will be receiving a confirmation email shortly.', 'websquare'); ?></p>
					</div>
				</div>
			</div>
		</section>
	<?php else : ?>

		<section class="bullhorn bullhorn-job">
			<div class="container">
				<div class="row">
					<div class="col">
						<div class="bullhorn-job__title">
							<h1 class="elementor-heading-title elementor-size-default"><?= HtmlHelper::encode($jobOrder->title) ?></h1>
						</div>
						<div class="bullhorn-job__meta">
							<?php if (isset($jobOrder->address->city) && !empty($jobOrder->address->city)) : ?>
								<div class="bullhorn-job__meta-item bullhorn-job__meta-item--location">
									<span><?php esc_html_e('Location: ', 'websquare'); ?></span>
									<?= HtmlHelper::encode($jobOrder->address->city) ?>
								</div>
							<?php endif; ?>
							<?php if (isset($jobOrder->dateAdded) && !empty($jobOrder->dateAdded)) : ?>
								<div class="bullhorn-job__meta-item bullhorn-job__meta-item--date">
									<span><?php esc_html_e('Date Posted: ', 'websquare'); ?></span>
									<?= date_i18n('F d, Y', strtotime($jobOrder->dateAdded)); ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="bullhorn-job__description">
							<?= $jobOrder->description->GetDescription() ?>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="bullhorn bullhorn-apply">
			<div class="container">
				<h2 class="elementor-heading-title elementor-size-default"><?php esc_html_e('Apply for this Job', 'websquare'); ?></h2>
			</div>

			<form id="bullhorn-apply-form" class="bullhorn-apply-form" method="post" enctype="multipart/form-data" action="<?= URI::getCurrent()->href() ?>" name="candidate-application">
				<div class="container">
					<div class="row">

						<div class="col-md-6">
							<label for="job-apply-firstName" class="bullhorn-apply-form__label"><?php esc_html_e('First Name*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
								<input type="text" id="job-apply-firstName" name="job-apply-firstName" placeholder="<?php esc_html_e('First Name', 'websquare'); ?>" value="<?= $filterMachine->getFilterValue('job-apply-firstName') ?>" required="true">
							</div>
						</div>

						<div class="col-md-6">
							<label for="job-apply-lastName" class="bullhorn-apply-form__label"><?php esc_html_e('Last Name*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
								<input type="text" id="job-apply-lastName" name="job-apply-lastName" placeholder="<?php esc_html_e('Last Name', 'websquare'); ?>" value="<?= $filterMachine->getFilterValue('job-apply-lastName') ?>" required="true">
							</div>
						</div>

						<div class="col-md-6">
							<label for="job-apply-email" class="bullhorn-apply-form__label"><?php esc_html_e('Email Address*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
								<input type="email" id="job-apply-email" name="job-apply-email" placeholder="<?php esc_html_e('Email Address', 'websquare'); ?>" value="<?= $filterMachine->getFilterValue('job-apply-email') ?>" required="true">
							</div>
						</div>

						<div class="col-md-6">
							<label for="job-apply-phone" class="bullhorn-apply-form__label"><?php esc_html_e('Phone Number*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
								<input type="text" id="job-apply-phone" name="job-apply-phone" placeholder="<?php esc_html_e('Phone Number', 'websquare'); ?>" value="<?= $filterMachine->getFilterValue('job-apply-email') ?>" required="true">
							</div>
						</div>

						<div class="col-lg-6">
							<label class="bullhorn-apply-form__label"><?php esc_html_e('Date of Birth*:', 'websquare'); ?></label>
							<div class="row">
								<div class="col-4">
									<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
										<?php
										$day = $formFilterMachine->getFilter('dob-day');
										$day_value = $day->getValue();
										if (!empty($day_value)) {
											$day_value = (int)$day_value;
										}
										echo HtmlHelper::select(
											'dob-day',
											$day->getPossibleValueTexts(false),
											$day_value,
											array(
												'required' => 'true'
											),
											esc_html__('Day', 'websquare')
										);
										?>
										<i class="fas fa-chevron-down"></i>
									</div>
								</div>
								<div class="col-4">
									<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
										<?php
										$month = $formFilterMachine->getFilter('dob-month');
										$month_value = $month->getValue();
										echo HtmlHelper::select(
											'dob-month',
											$month->getPossibleValueTexts(false),
											$month_value,
											array(
												'required' => 'true'
											),
											esc_html__('Month', 'websquare')
										);
										?>
										<i class="fas fa-chevron-down"></i>
									</div>
								</div>
								<div class="col-4">
									<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
										<?php
										$year = $formFilterMachine->getFilter('dob-year');
										$year_value = $year->getValue();
										if (!empty($year_value)) {
											$year_value = (int)$year_value;
										}
										echo HtmlHelper::select(
											'dob-year',
											$year->getPossibleValueTexts(false),
											$year_value,
											array(
												'required' => 'true'
											),
											esc_html__('Year', 'websquare')
										);
										?>
										<i class="fas fa-chevron-down"></i>
									</div>
								</div>
							</div>

						</div>

						<div class="col-md-6">
							<label for="countryID" class="bullhorn-apply-form__label"><?php esc_html_e('Residence Location*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
								<?php
								$nationality = $formFilterMachine->getFilter('countryID');
								$nationality_value = $nationality->getValue();
								if (!empty($nationality_value)) {
									$nationality_value = (int)$nationality_value;
								}
								echo HtmlHelper::select(
									'countryID',
									$nationality->getPossibleValueTexts(false),
									$nationality_value,
									array(
										'required' => 'true'
									),
									esc_html__('Please Select', 'websquare')
								);
								?>
								<i class="fas fa-chevron-down"></i>
							</div>
						</div>

						<div class="col-md-6">
							<label for="customText5" class="bullhorn-apply-form__label"><?php esc_html_e('Nationality / Citizenship*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
								<?php
								$nationality = $formFilterMachine->getFilter('customText5');
								$nationality_value = $nationality->getValue();
								if (!empty($nationality_value)) {
									$nationality_value = (int)$nationality_value;
								}
								echo HtmlHelper::select(
									'customText5',
									$nationality->getPossibleValueTexts(false),
									$nationality_value,
									array(
										'required' => 'true'
									),
									esc_html__('Please Select', 'websquare')
								);
								?>
								<i class="fas fa-chevron-down"></i>
							</div>
						</div>

						<div class="col-md-6">
							<label for="experience" class="bullhorn-apply-form__label"><?php esc_html_e('Years of Experience*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
								<?php
								$experience = $formFilterMachine->getFilter('experience');
								$nationality_value = $nationality->getValue();
								$experience_value = $experience->getValue();
								if (!empty($experience_value) || strlen($experience_value) > 0) {
									$experience_value = (int)$experience_value;
								}
								echo HtmlHelper::select(
									'experience',
									$experience->getPossibleValueTexts(false),
									$experience_value,
									array(
										'required' => 'true'
									),
									esc_html__('Please Select', 'websquare')
								);
								?>
								<i class="fas fa-chevron-down"></i>
							</div>
						</div>

						<div class="col-md-6">
							<label for="customText4" class="bullhorn-apply-form__label"><?php esc_html_e('Notice Period*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
								<?php
								$notice = $formFilterMachine->getFilter('customText4');
								echo HtmlHelper::select(
									'customText4',
									$notice->getPossibleValueTexts(false),
									$notice->getValue(),
									array(
										'required' => 'true'
									),
									esc_html__('Please Select', 'websquare')
								);
								?>
								<i class="fas fa-chevron-down"></i>
							</div>
						</div>

						<div class="col-md-6">
							<label for="occupation" class="bullhorn-apply-form__label"><?php esc_html_e('Current / Last Job Title*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
								<input type="text" id="occupation" name="occupation" placeholder="<?php esc_html_e('Current / Last Job Title', 'websquare'); ?>" value="<?= $filterMachine->getFilterValue('occupation') ?>" required="true">
							</div>
						</div>

						<div class="col-12">
							<label for="businessSectorID" class="bullhorn-apply-form__label"><?php esc_html_e('Industry*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
								<?php
								$sector = $formFilterMachine->getFilter('businessSectorID');
								$sector_value = $sector->getValue();
								echo HtmlHelper::multiSelect(
									'businessSectorID',
									$sector->getPossibleValueTexts(false),
									$sector_value,
									array(
										'required' => 'true',
										'size'  => 10,
										'_promptValue' => ''
									),
									// esc_html__('Please Select', 'websquare')
								);
								?>
								<!-- <i class="fas fa-chevron-down"></i> -->
							</div>
						</div>

						<div class="col-md-6">
							<label for="customText6" class="bullhorn-apply-form__label"><?php esc_html_e('Visa Type*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
								<?php
								$notice = $formFilterMachine->getFilter('customText6');
								echo HtmlHelper::select(
									'customText6',
									$notice->getPossibleValueTexts(false),
									$notice->getValue(),
									array(
										'required' => 'true'
									),
									esc_html__('Please Select', 'websquare')
								);
								?>
								<i class="fas fa-chevron-down"></i>
							</div>
						</div>

						<div class="col-md-6">
							<label for="job-apply-cv" class="bullhorn-apply-form__label"><?php esc_html_e('Upload CV*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--file">
								<input type="file" id="job-apply-cv" name="job-apply-cv" placeholder="<?php esc_html_e('Upload CV', 'websquare'); ?>" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.pdf,.doc,.docx,.txt" required="true">
							</div>
						</div>


						<div class="col-12">
							<label for="job-apply-comments" class="bullhorn-apply-form__label"><?php esc_html_e('Add a message*:', 'websquare'); ?></label>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--textarea">
								<textarea id="job-apply-comments" name="job-apply-comments" placeholder="<?php esc_html_e('Comments', 'websquare'); ?>" rows="4"><?= $filterMachine->getFilterValue('job-apply-comments') ?></textarea>
							</div>
						</div>

						<div class="col-md-6">
							<?php if (isset($recaptcha_v3)) : ?>
								<div id="bullhorn-apply-form__grecaptcha" class="bullhorn-apply-form__grecaptcha" data-sitekey="<?= $recaptcha_v3_site_key; ?>" data-type="v3" data-action="Form" data-badge="bottomright" data-size="invisible"></div>
							<?php endif; ?>
							<div class="bullhorn-apply-form__field bullhorn-apply-form__field--submit">
								<input class="bullhorn-apply-form__submit" name="submit" type="submit" value="<?php esc_html_e('Submit', 'websquare'); ?>">
							</div>
						</div>

					</div>
				</div>
			</form>
		</section>
		<?php if (isset($recaptcha_v3)) : ?>
			<script>
				jQuery(function($) {
					var form = $('#bullhorn-apply-form');
					var captcha = $('#bullhorn-apply-form__grecaptcha');
					var settings = captcha.data();
					var widgetId = window.grecaptcha.render(captcha[0], settings);
					form.submit(function(event) {
						event.preventDefault();
						form.on('reset error', function() {
							window.grecaptcha.reset(widgetId);
						});

						window.grecaptcha.ready(function() {
							window.grecaptcha.execute(widgetId, {
								action: 'apply'
							}).then(function(token) {
								form.prepend('<input type="hidden" name="token" value="' + token + '">');
								form.prepend('<input type="hidden" name="action" value="apply">');
								form.unbind('submit').submit();
							});;
						});
					});
				});
			</script>
		<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>
