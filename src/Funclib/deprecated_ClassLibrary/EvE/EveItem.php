<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\Taxable;

class EveItem extends Taxable {
	
	private static $instance;
	
	/**
	 * private constructor for singleton
	 * @param Integer $typeID
	 */
	private function __construct($typeID,$jobID) {
		if(empty($typeID)) {
			print " NO TYPE ID Supported !!";
		}
		$this->typeID = $typeID;
		$this->loadSubSettings($typeID, $jobID);
	}
	
	/**
	 * allow only one instance of this item to avoid multiple database requests
	 * @param integer $typeID
	 */
	public static function gI($typeID,$jobID) {
		if(empty(EveItem::$instance[$typeID][$jobID])) {
			EveItem::$instance[$typeID][$jobID] = new EveItem($typeID,$jobID);
		}
		return EveItem::$instance[$typeID][$jobID];
	}
	
	public function setStandards($array) {
		if(!empty($array['stationME']))
			$this->setStationME($array['stationME']);
		if(!empty($array['ME']))
			$this->setME($array['ME']);
		if(!empty($array['stationTax']))
			$this->setStationTax($array['stationTax']);
		if(!empty($array['costModifier'])) {
			$this->setStationCostModifier($array['costModifier']);
		}
		
		$items = $this->getBuildMaterials();
		foreach($items as $item) {
			$item['itemType']->setStandards($array);
		}
	}
}
?>