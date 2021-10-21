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

include_once(get_template_directory() . '/bullhorn/bh-filters.php');
include_once(get_template_directory() . '/bullhorn/bh-form-filters.php');


if ($udesign_options['recaptcha_enabled'] == 'yes') {
	// Add 'async' and 'defer' to the theme's reCAPTCHA enqueued script using the "script_loader_tag" filter
	function udesign_add_async_defer_to_recaptcha_script($tag, $handle)
	{
		if (is_admin() || 'udesign-recaptcha' !== $handle) {
			return $tag;
		}
		global $udesign_options;
		// Add language code. reCAPTCHA supported 40+ languages listed here: https://developers.google.com/recaptcha/docs/language
		$lang = $udesign_options['recaptcha_lang']; // ex. "en"
		$tag = str_replace('?ver=', '?hl=' . $lang . '&amp;ver=', $tag);
		// Add the 'async' and 'defer' to the $tag
		return str_replace('></script>', ' async defer></script>', $tag);
	}
	add_filter('script_loader_tag', 'udesign_add_async_defer_to_recaptcha_script', 10, 2);

	// Register API keys at https://www.google.com/recaptcha/admin
	$user_recaptcha_site_key = $udesign_options['recaptcha_publickey'];
	$user_recaptcha_secret_key = $udesign_options['recaptcha_privatekey'];

	$captcha_verified = false;
	if (isset($_POST['g-recaptcha-response'])) {
		$response = isset($_POST['g-recaptcha-response']) ? esc_attr($_POST['g-recaptcha-response']) : '';
		$remote_ip = $_SERVER["REMOTE_ADDR"];
		// make a GET request to the Google reCAPTCHA Server
		$request = wp_remote_get(
			'https://www.google.com/recaptcha/api/siteverify?secret=' . $user_recaptcha_secret_key . '&response=' . $response . '&remoteip=' . $remote_ip
		);
		// get the request response body
		$response_body = wp_remote_retrieve_body($request);
		$result = json_decode($response_body, true);
		$captcha_verified = $result['success'];
	}
}

$api = BullHorn_Factory::Get()->get_api();

$bhFilters     = BhFilters::instance();
$filterMachine = $bhFilters->getFilterMachine();
$searched      = $bhFilters->isSearched();

$paging = new \SquareChilli\Bullhorn\components\Paging($filterMachine->getFilterValue('page') - 1, 10);

$bhFormFilters     = BhFormFilters::instance();
$formFilterMachine = $bhFormFilters->getFilterMachine();

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

// ------------------------------------------------

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
