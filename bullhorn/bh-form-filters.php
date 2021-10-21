<?php
use SquareChilli\Bullhorn\Client;
use SquareChilli\Bullhorn\filters\Filter;
use SquareChilli\Bullhorn\models\Entity;

class BhFormFilters {
	private static $bhFormFilters;
	/** @var \SquareChilli\Bullhorn\helpers\URI */
	protected $uri;
	/** @var \SquareChilli\Bullhorn\filters\FilterMachine */
	protected $filterMachine;

	public function __construct() {
		if ( ! class_exists('BullHorn_Factory')) {
			die('bullhorn plugin must be activated');
		}

		$this->init();
	}

	public function init() {
		$this->uri = URI::getCurrent();

		$countries = \SquareChilli\Bullhorn\models\Country::find()
						->select('id, country as value')
						->orderBy('country')->fetchAll();

		$countries  = $this->getValues($countries);
		$experiences = SquareChilli\Bullhorn\data\Experiences::getDataFlipped();
		$notices = SquareChilli\Bullhorn\data\NoticePeriods::getData();
		$sectors = Entity::getEntitiesQuery('BusinessSector')
		                 ->select('DISTINCT bullhorn_id as id, value')
						 ->andWhere(['name' => 'name'])
						 ->andWhere('value != :value', array(':value' => 'All Sector'))
						 ->orderBy('value')
		                 ->fetchAll();
		$sectors  = $this->getValues($sectors);
		$visas = SquareChilli\Bullhorn\data\VisaTypes::getData();

		$days = [];
		for ($i = 1; $i <= 31; $i++){
			$days[$i] = $i;
		}
		$months = array(
			'Jan'	=> 1,
			'Feb'	=> 2,
			'Mar'	=> 3,
			'Apr'	=> 4,
			'May'	=> 5,
			'Jun'	=> 6,
			'Jul'	=> 7,
			'Aug'	=> 8,
			'Sep'	=> 9,
			'Oct'	=> 10,
			'Nov'	=> 11,
			'Dec'	=> 12
		);
		$years = [];
		$year_start = (int)date('Y') - 16;
		for ($i = $year_start; $i >= $year_start - 99; $i--){
			$years[$i] = $i;
		}

		$this->filterMachine = new FilterMachine(
			array(

				Filter::create(['countryID'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Residence Location')
				->possibleValues($countries, false),

				Filter::create(['customText5'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Nationality / Citizenship')
				->possibleValues($countries, false),

				Filter::create(['experience'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Years of Experience')
				->possibleValues($experiences, false),

				Filter::create(['customText4'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Notice Period')
				->possibleValues($notices, false),

				Filter::create(['businessSectorID'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Industry')
				->possibleValues($sectors, false),

				Filter::create(['customText6'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Visa Type')
				->possibleValues($visas, false),

				Filter::create(['dob-day'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Day')
				->possibleValues($days, false),

				Filter::create(['dob-month'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Month')
				->possibleValues(array_flip($months), false),

				Filter::create(['dob-year'], null)
				->methods( Filter::METHOD_POST )
				->defaultText('Year')
				->possibleValues($years, false),
			)
		);
	}

	protected function getValues($array) {
		if (empty($array)) {
			return [];
		}

		$items = [];

		foreach ($array as $item) {
			$value = trim($item['value']);

			if (empty($value)) {
				continue;
			}

			$items[ $item['id'] ] = $value;
		}

		return $items;
	}

	public static function instance() {
		if (self::$bhFormFilters === null) {
			self::$bhFormFilters = new BhFormFilters();
		}

		return self::$bhFormFilters;
	}

	public function getUri() {
		return $this->uri;
	}

	public function getFilterMachine() {
		return $this->filterMachine;
	}
}

BhFormFilters::instance();
