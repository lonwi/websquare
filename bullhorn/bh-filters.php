<?php
use SquareChilli\Bullhorn\Client;
use SquareChilli\Bullhorn\filters\Filter;
use SquareChilli\Bullhorn\models\Entity;

/**
 * Created by PhpStorm.
 * User: andre
 * Date: 19/10/2016
 * Time: 20:50
 */
class BhFilters {
	private static $bhFilters;
	/** @var \SquareChilli\Bullhorn\helpers\URI */
	protected $uri;
	/** @var \SquareChilli\Bullhorn\filters\FilterMachine */
	protected $filterMachine;

	public function __construct() {
		if ( ! class_exists('BullHorn_Factory')) {
			die('bullhorn plugin must be activated');
		}

		if (isset($_COOKIE['debug'])) {
			error_reporting(E_ALL);
			ini_set('display_errors', 'on');

			set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
				// error was suppressed with the @-operator
				if (0 === error_reporting()) {
					return false;
				}

				echo '<pre>' . print_r(func_get_args(), true) . '</pre>';
				die();
			});

			set_exception_handler(function () {
				echo '<pre>' . print_r(func_get_args(), true) . '</pre>';
				die();
			});
		}

		$this->init();
	}

	public function init() {
		$this->uri = URI::getCurrent();

		$bullhorn = BullHorn_Factory::Get();
		$api      = $bullhorn->get_api();

		$fields = Entity::getEntitiesQuery('Category')
		                ->select('DISTINCT value')
		                ->andWhere(['name' => 'name'])
		                ->fetchColumn();

		$sectors = Entity::getEntitiesQuery('BusinessSector')
		                 ->select('DISTINCT value')
		                 ->andWhere(['name' => 'name'])
		                 ->fetchColumn();

		$fields  = $this->getValues($fields);
		$sectors = $this->getValues($sectors);

		ksort($fields);
		ksort($sectors);

		$this->filterMachine = new FilterMachine(array(
			Filter::create(['_page', 'page', BullHornAPI_List::PAGE_GET_PARAM], 1)
			      ->cast('int')
			      ->minimum(1)
			      ->regex(sprintf('~^/(?:%s|job-search)/(\d+)/~',
				      preg_quote(trim(Client::instance()->config()->getJobConfig()->getJobSearchUrl(false), '/')),
				      '~')),

			Filter::create(['freetext']),

			Filter::create(['field'], null)
			      ->defaultText('Job Function')
			      ->possibleValues($fields, false),

			Filter::create(['sector'], null)
			      ->defaultText('Business Sector')
			      ->possibleValues($sectors, false),
		));
	}

	protected function getValues($array) {
		if (empty($array)) {
			return [];
		}

		$items = [];

		foreach ($array as $item) {
			$item = trim($item);

			if (empty($item)) {
				continue;
			}

			$items[ $item ] = $item;
		}

		return $items;
	}

	public static function instance() {
		if (self::$bhFilters === null) {
			self::$bhFilters = new BhFilters();
		}

		return self::$bhFilters;
	}

	public function getUri() {
		return $this->uri;
	}

	public function getFilterMachine() {
		return $this->filterMachine;
	}

	public function isSearched() {
		return $this->filterMachine->hasFilterValueAny();
	}
}

BhFilters::instance();
