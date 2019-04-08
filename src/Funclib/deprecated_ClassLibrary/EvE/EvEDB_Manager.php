<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvECrest;

class EvEDB_Manager {
	
	private $rate_per_second = 30;
	
	private function __construct() {
		
	}
	
	private static $instance;
	public static function getInstance() {
		if(empty(EvEDB_Manager::$instance)) {
			EvEDB_Manager::$instance = new EvEDB_Manager();
		}
		return EvEDB_Manager::$instance;
	}
	
	public function updateDatabaseWithRates() {
		
		
		
	}
	
	public function updateCategories() {
		$mvalue = EvECrest::getInstance()->getMarketPrices();
		$itemType = EvECrest::getInstance()->getTypeId(34);
	}
	
	
	
}


?>