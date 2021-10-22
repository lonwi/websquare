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
use SquareChilli\Bullhorn\filters\Filter;

include_once(get_template_directory() . '/bullhorn/bh-form-filters.php');
$api = BullHorn_Factory::Get()->get_api();

$bhFormFilters     = BhFormFilters::instance();
$formFilterMachine = $bhFormFilters->getFilterMachine();
$cvFile = $postData = null;
$submittedApplication = false;
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
	$applicationForm->loadData(File::normalize($_FILES['candidate-application']));
}

$applicationFormSubmitted = null;
if (!empty($_POST)) {
	$g_recaptcha_response = trim($_POST['g-recaptcha-response']);
	if (($udesign_options['recaptcha_enabled'] == 'yes') && (!$captcha_verified || $g_recaptcha_response == '')) {
		$recaptchaError = __('Please respond to the reCAPTCHA question', 'udesign');
	} else {
		$applicationFormSubmitted = $applicationForm->submitApplication();
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
	<section class="bullhorn bullhorn-apply">

		<form class="bullhorn-apply-form" method="post" enctype="multipart/form-data" action="<?= URI::getCurrent()->href() ?>" name="candidate-application">
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
							<?php $applicationForm->textField('occupation', ['id' => 'occupation', 'required' => 'true', 'placeholder' => esc_html__('Current / Last Job Title', 'websquare')]) ?>
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
						<label for="resume" class="bullhorn-apply-form__label"><?php esc_html_e('Upload CV*:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--file">
							<?php $applicationForm->file('resume', ['id' => 'resume', 'required' => 'true']) ?>
						</div>
					</div>


					<div class="col-12">
						<label for="comments" class="bullhorn-apply-form__label"><?php esc_html_e('Add a message:', 'websquare'); ?></label>
						<div class="bullhorn-apply-form__field bullhorn-apply-form__field--textarea">
							<?php $applicationForm->textarea('comments', ['id' => 'comments', 'rows'=>'4', 'placeholder'=> esc_html__('Comments', 'websquare')]) ?>
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