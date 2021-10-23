<?php

if (!class_exists('BullHorn_Factory')) {
	die('bullhorn plugin must be activated');
}

$recaptcha_v3_site_key = get_option('elementor_pro_recaptcha_v3_site_key');
$recaptcha_v3_secret_key = get_option('elementor_pro_recaptcha_v3_secret_key');
$recaptcha_v3_threshold = get_option('elementor_pro_recaptcha_v3_threshold');

if (!empty($recaptcha_v3_site_key) && !empty($recaptcha_v3_secret_key)) {
	wp_enqueue_script('elementor-recaptcha_v3-api-js');
	$recaptcha_v3 = true;
}

use SquareChilli\Bullhorn\applicationSubmitter\ApplicationSubmitter;
use SquareChilli\Bullhorn\applicationSubmitter\rules\CV;
use SquareChilli\Bullhorn\applicationSubmitter\rules\Email;
use SquareChilli\Bullhorn\applicationSubmitter\rules\Required;
use SquareChilli\Bullhorn\Client;
use SquareChilli\Bullhorn\form\File;
use SquareChilli\Bullhorn\form\RawForm;
use SquareChilli\Bullhorn\filters\Filter;

include_once(get_template_directory() . '/bullhorn/bh-form-filters.php');
$api = BullHorn_Factory::Get()->get_api();

$bhFormFilters     = BhFormFilters::instance();
$formFilterMachine = $bhFormFilters->getFilterMachine();
$cvFile = $postData = null;
$submittedApplication = false;

class BaseDataClass
{
}
class ApplicationForm extends RawForm
{
	#customText4 = Notice Period
	#customText5 = Nationality / Citizenship
	#businessSectorID = Industry
	#customText6 = Visa Type
	public function attributes()
	{
		return [
			'name', 'firstName', 'lastName', 'email', 'phone',
			'dob-day', 'dob-month', 'dob-year',
			'countryID',
			'customText5', 'experience', 'customText4',
			'experience', 'occupation', 'businessSectorID',
			'customText6',
			'resume',
			'comments'
		];
	}

	public function requiredAttributes()
	{
		return [
			'firstName', 'lastName', 'email', 'phone',
			'date_of_birth',
			'countryID',
			'customText5', 'experience', 'customText4',
			'occupation', 'businessSectorID',
			'customText6',
			'resume'
		];
	}

	public function attributeLabels()
	{
		return [
			'name'          => 'Name',
			'firstName'     => 'First Name',
			'lastName'      => 'Last Name',
			'email'         => 'Email Address',
			'phone'         => 'Phone Number',
			'date_of_birth' => 'Date of Birth',
			'countryID'     => 'Residence Location',
			'customText5'   => 'Nationality / Citizenship',
			'experience'    => 'Experience',
			'customText4'   => 'Notice Period',
			'occupation'    => 'Current / Last Job Title',
			'businessSectorID'  => 'Industry',
			'customText6'   => 'Visa Type',
			'resume'        => 'Upload CV',
			'comments'      => 'Add a message',
		];
	}

	public function submitApplication()
	{
		if (!$this->validate()) {
			return false;
		}
		$application = ApplicationSubmitter::init(null, $this->getAttributes(), $this->getAttribute('resume'));

		$application->addRules([
			//'name'        => [Required::className()],
			'firstName'     => [Required::className()],
			'lastName'      => [Required::className()],
			'email'         => [Required::className(), Email::className()],
			'phone'         => [Required::className()],
			'date_of_birth' => [Required::className()],
			'countryID'     => [Required::className()],
			'customText5'   => [Required::className()],
			'experience'    => [Required::className()],
			'customText4'   => [Required::className()],
			'occupation'    => [Required::className()],
			'businessSectorID'   => [Required::className()],
			'customText6'   => [Required::className()],
			'cv'            => [Required::className(), CV::className()],
		]);

		//$application->loadName($application->getAttribute('name'));

		if (!$application->submit()) {
			$applicationErrors = $application->getErrors();

			foreach ($applicationErrors as $field => $error) {
				/*if ($field === 'firstName' || $field === 'lastName') {
                    $field = 'name';
                } else */
				if ($field === 'cv') {
					$field = 'resume';
				}

				$this->addError($field, $error);
			}

			return false;
		} else {
			$user_email = $application->getAttribute('email');
			if (!empty($user_email)) {
				$email = Client::instance()->config()->getEmailConfig()->getEmail('thankYou');
				$applicationData = new BaseDataClass();
				$applicationUser = new BaseDataClass();
				$applicationData->{'time_of_application'} = current_time('mysql');

				$applicationUser->{'first_name'} = $application->getAttribute('firstName');
				$applicationUser->{'last_name'} = $application->getAttribute('lastName');

				$email->setData([
					'application' => $applicationData,
					'applicationUser' => $applicationUser
				]);
				$email->sendTo($user_email);
			}
		}
		return true;
	}

	public function loadDOB()
	{
		$day = $this->getAttribute('dob-day');
		$month = $this->getAttribute('dob-month');
		$year = $this->getAttribute('dob-year');
		if ($day != null && $month != null && $year != null) {
			$this->setAttribute('date_of_birth', "$year-$month-$day");
		}
	}
}

$applicationForm = new ApplicationForm('candidate-application');

if (!empty($_POST['candidate-application'])) {
	$applicationForm->loadData($_POST['candidate-application']);
	$applicationForm->loadDOB();
}

if (!empty($_FILES['candidate-application'])) {
	$applicationForm->loadData(File::normalize($_FILES['candidate-application']['resume']));
}

$applicationFormSubmitted = null;
if (!empty($_POST)) {

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
	if (!isset($recaptcha_v3) || ($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= $recaptcha_v3_threshold)) {
		$applicationFormSubmitted = $applicationForm->submitApplication();
	} else {
		echo "<script>alert('Validation Failed');</script>";
	}
}
?>

<?php if ($applicationFormSubmitted) : ?>

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
	<?php if (isset($recaptcha_v3)) : ?>
		<script>
			$('#bullhorn-apply-form').submit(function(event) {
				event.preventDefault();
				grecaptcha.ready(function() {
					grecaptcha.execute(<?= $recaptcha_v3_site_key ?>, {
						action: 'apply'
					}).then(function(token) {
						$('#bullhorn-apply-form').prepend('<input type="hidden" name="token" value="' + token + '">');
						$('#bullhorn-apply-form').prepend('<input type="hidden" name="action" value="apply">');
						$('#bullhorn-apply-form').unbind('submit').submit();
					});;
				});
			});
		</script>
	<?php endif; ?>
	<section class="bullhorn bullhorn-apply">

		<form id="bullhorn-apply-form" class="bullhorn-apply-form" method="post" enctype="multipart/form-data" action="<?= URI::getCurrent()->href() ?>" name="candidate-application">
			<div class="container">
				<div class="row">

					<div class="col-md-6">
						<label for="firstName" class="bullhorn-apply-form__label"><?php esc_html_e('First Name*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
							<?php $applicationForm->textField('firstName', ['id' => 'firstName', 'required' => 'true', 'placeholder' => esc_html__('First Name', 'websquare')]) ?>
						</div>
					</div>

					<div class="col-md-6">
						<label for="lastName" class="bullhorn-apply-form__label"><?php esc_html_e('Last Name*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
							<?php $applicationForm->textField('lastName', ['id' => 'lastName', 'required' => 'true', 'placeholder' => esc_html__('Last Name', 'websquare')]) ?>
						</div>
					</div>

					<div class="col-md-6">
						<label for="email" class="bullhorn-apply-form__label"><?php esc_html_e('Email Address*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
							<?php $applicationForm->textField('email', ['type' => 'email', 'id' => 'email', 'required' => 'true', 'placeholder' => esc_html__('Email Address', 'websquare')]) ?>
						</div>
					</div>

					<div class="col-md-6">
						<label for="phone" class="bullhorn-apply-form__label"><?php esc_html_e('Phone Number*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
							<?php $applicationForm->textField('phone', ['id' => 'phone', 'required' => 'true', 'placeholder' => esc_html__('Phone Number', 'websquare')]) ?>
						</div>
					</div>

					<div class="col-lg-6">
						<label class="bullhorn-apply-form__label"><?php esc_html_e('Date of Birth*:', 'websquare'); ?></label>
						<div class="row">
							<div class="col-4">
								<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
									<?php

									$day = $formFilterMachine->getFilter('dob-day');
									echo $applicationForm->select(
										'dob-day',
										$day->getPossibleValueTexts(false),
										array(
											'required' => 'true',
											'_prompt' => esc_html__('Day', 'websquare')
										)
									);
									?>
									<i class="fas fa-chevron-down"></i>
								</div>
							</div>
							<div class="col-4">
								<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
									<?php
									$month = $formFilterMachine->getFilter('dob-month');
									echo $applicationForm->select(
										'dob-month',
										$month->getPossibleValueTexts(false),
										array(
											'required' => 'true',
											'_prompt' => esc_html__('Month', 'websquare')
										)
									);
									?>
									<i class="fas fa-chevron-down"></i>
								</div>
							</div>
							<div class="col-4">
								<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
									<?php
									$year = $formFilterMachine->getFilter('dob-year');
									echo $applicationForm->select(
										'dob-year',
										$year->getPossibleValueTexts(false),
										array(
											'required' => 'true',
											'_prompt' => esc_html__('Year', 'websquare')
										)
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
							echo $applicationForm->select(
								'countryID',
								$nationality->getPossibleValueTexts(false),
								array(
									'required' => 'true',
									'_prompt' => esc_html__('Please Select', 'websquare')
								)
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
							echo $applicationForm->select(
								'customText5',
								$nationality->getPossibleValueTexts(false),
								array(
									'required' => 'true',
									'_prompt' => esc_html__('Please Select', 'websquare')
								)
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
							echo $applicationForm->select(
								'experience',
								$experience->getPossibleValueTexts(false),
								array(
									'required' => 'true',
									'_prompt' => esc_html__('Please Select', 'websquare'),
									'_promptValue' => '' // Issue with 0 and Empty so need an override
								)
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
							echo $applicationForm->select(
								'customText4',
								$notice->getPossibleValueTexts(false),
								array(
									'required' => 'true',
									'_prompt' => esc_html__('Please Select', 'websquare')
								)
							);
							?>
							<i class="fas fa-chevron-down"></i>
						</div>
					</div>

					<div class="col-md-6">
						<label for="occupation" class="bullhorn-apply-form__label"><?php esc_html_e('Current / Last Job Title*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--input">
							<?php $applicationForm->textField('occupation', ['id' => 'occupation', 'required' => 'true', 'placeholder' => esc_html__('Current / Last Job Title', 'websquare')]) ?>
						</div>
					</div>

					<div class="col-12">
						<label for="businessSectorID" class="bullhorn-apply-form__label"><?php esc_html_e('Industry*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
							<?php
							$sector = $formFilterMachine->getFilter('businessSectorID');
							echo $applicationForm->multiSelect(
								'businessSectorID',
								$sector->getPossibleValueTexts(false),
								array(
									'required' => 'true',
									'size'  => 10,
									'_promptValue' => ''
								)
							);
							?>
							<!-- <i class="fas fa-chevron-down"></i> -->
						</div>
					</div>

					<div class="col-md-6">
						<label for="customText6" class="bullhorn-apply-form__label"><?php esc_html_e('Visa Type*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--select">
							<?php
							$visa = $formFilterMachine->getFilter('customText6');
							echo $applicationForm->select(
								'customText6',
								$visa->getPossibleValueTexts(false),
								array(
									'required' => 'true',
									'_prompt' => esc_html__('Please Select', 'websquare')
								)
							);
							?>
							<i class="fas fa-chevron-down"></i>
						</div>
					</div>

					<div class="col-md-6">
						<label for="resume" class="bullhorn-apply-form__label"><?php esc_html_e('Upload CV*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--file">
							<?php $applicationForm->file('resume', ['id' => 'resume', 'required' => 'true']) ?>
						</div>
					</div>


					<div class="col-12">
						<label for="comments" class="bullhorn-apply-form__label"><?php esc_html_e('Add a message:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--textarea">
							<?php $applicationForm->textarea('comments', ['id' => 'comments', 'rows' => '4', 'placeholder' => esc_html__('Comments', 'websquare')]) ?>
						</div>
					</div>

					<div class="col-md-6">
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--submit">
							<input class="bullhorn-apply-form__submit" name="submit" type="submit" value="<?php esc_html_e('Submit', 'websquare'); ?>">
						</div>
					</div>

				</div>
			</div>
		</form>
	</section>

<?php endif; ?>
