<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;
abstract class Taxable {
	

	protected $material_efficiency = 0;
	
	protected $station_me = 0;
	
	protected $station_tax = 0;
	
	protected $stationCostModifier = 0;
	
	protected $calculatedMaterialCost = array();
	
	protected $buildSystemID = 30003740;

	protected $factoryCostIndex = 0;

	protected $buildMaterials;
	
	protected $typeID;
	

	public function loadSubSettings($typeID, $jobID) {
		$sql = "
		SELECT
		iT.typeName,
		mats.materialTypeID,
		bpo.typeID AS bopID,
		bpoName.typeName as bpoName,
		iT.typeID AS resultTypeID,
		mats.quantity,
		matsName.typeName,
		Meff.materialEfficiency AS MatEff,
		Meff.stationEfficiency AS StationEfficiency,
		Meff.stationTax AS StationTax
		FROM
		invTypes AS iT
		LEFT JOIN
		industryActivityProducts AS bpo ON iT.typeID = bpo.productTypeID AND bpo.activityID = 1
		RIGHT JOIN
		industryActivityMaterials AS mats ON bpo.typeID = mats.typeID AND bpo.activityID = mats.activityID
		LEFT JOIN
		invTypes AS bpoName ON bpoName.typeID = bpo.typeID
		LEFT JOIN
		invTypes AS matsName ON matsName.typeID = mats.materialTypeID
		LEFT JOIN
		emb_product_job_subitems AS Meff ON Meff.jobID = $jobID AND Meff.subTypeID = mats.materialTypeID
		WHERE
		iT.typeID = $typeID AND bpo.activityID = 1;
		";
	
		$res = Database::getInstance()->sql_query($sql);
		while($row = Database::getInstance()->sql_fetch_array($res)) {
		$this->buildMaterials[$row['materialTypeID']] = array('itemType' => EveItem::gI($row['materialTypeID'],$jobID),
				'quantity' => $row['quantity'],
						'typeName' => $row['typeName'],
								'typeID' => $row['materialTypeID'],
										'bpoID' => $row['bopID'],
		);
		$this->buildMaterials[$row['materialTypeID']]['itemType']->setME($row['MatEff']);
	
		if($row['StationEfficiency'] == NULL || empty($row['StationEfficiency'] && $row['StationEfficiency'] != 0)) {
			$StatEff = $this->getStationME();
		}
		else
			$StatEff = $row['StationEfficiency'];
	
		$this->buildMaterials[$row['materialTypeID']]['itemType']->setStationME($row['StationEfficiency']);
		$this->buildMaterials[$row['materialTypeID']]['itemType']->setStationTax($row['StationTax']);
		$this->buildMaterials[$row['materialTypeID']]['itemType']->setStationCostModifier($row['costReduction']);
		}
	}
	
	
	public function setME($me) {
		$me = intval($me);
		if($me < 0)
			$me = 0;
		if($me > 10)
			$me = 10;
		$this->material_efficiency = $me;
	}
	
	public function getME() {
		if(empty($this->material_efficiency))
			return 0;
		return intval($this->material_efficiency);
	}
	
	public function setStationME($me) {
		$me = round($me,5);
		if($me < 0)
			$me = 0;
		if($me > 10)
			$me = 10;
		$this->station_me = $me;
	}
	
	public function getStationME() {
		if(empty($this->station_me))
			return 0;
			return round($this->station_me,5);
	}
	
	public function setStationTax($me) {
		$me = round($me,5);
		if($me < 0)
			$me = 0;
		if($me > 10000)
			$me = 10000;
		$this->station_tax = $me;
	}
	
	public function getStationTax() {
		if(empty($this->station_tax))
			return 0;
		return round($this->station_tax,5);
	}
	
	public function setFactoryIndex($index) {
		print "Factory INdex: $index <br>";
		$this->factoryCostIndex = (float) $index;
	}
	
	public function getFactoryIndex() {
		print "Get Factory Index<br>";
		return $this->factoryCostIndex;
	}
	
	public function setStationCostModifier($cost) {
		$this->stationCostModifier = round($cost,5);
	}
	
	public function getStationCostModifier() {
		return $this->stationCostModifier;
	}
	
	public function getBuildMaterials() {
		if(empty($this->buildMaterials))
			return array();
		return $this->buildMaterials;
	}
	
	public function setBuildSystemID($systemID) {
		$this->buildSystemID = $systemID;
	}
	
	public function getTypeID() {
		return $this->typeID;
	}
	
	public function getBuildSystemID() {
		return $this->buildSystemID;
	}
	
	public function isNotBuildable() {
		return empty($this->buildMaterials);
	}

	/**
	 *
	 * @param string $includeMe
	 * @param Boolean $includesubpartFCP // Set 1 if sub-parts factory price has to be included
	 */
	public function calculateMaterialCost($includeMe=true, $includesubpartFCP=0) {
		if(empty($this->buildSystemID))
			$indexModifier = 0.01;
		else
			$indexModifier = MarketPrices::getInstance()->getIndustryIndex($this->buildSystemID, 1);
	
		if(!empty($this->calculatedMaterialCost[$includeMe][$includesubpartFCP]))
			return $this->calculatedMaterialCost[$includeMe][$includesubpartFCP];
	
		if(empty($this->buildMaterials)) {
			$avg = MarketPrices::getReferencePrices(array($this->typeID));
			return $this->calculatedMaterialCost[$includeMe][$includesubpartFCP] = $avg[$this->typeID]['avgPrice'];
		} else {
			if(!$includeMe)
				$ME = 0;
			else
				$ME = $this->getME()+$this->getStationME();
			$price = 0;
				
			$factoryCost = 0;
			foreach($this->buildMaterials as $material) {
				$matCost = $material['itemType']->calculateMaterialCost();
				$px = round(intval($material['quantity']) * (1 - $ME/100)) * ($matCost+($matCost*$indexModifier*$includesubpartFCP));
	
				$price = $price + $px;
			}
			return $this->calculatedMaterialCost[$includeMe][$includesubpartFCP] = round($price,2);
		}
	}
	
	public function getFactoryCost() {
		if(empty($this->buildSystemID))
			$indexModifier = 0.01;
		else
			$indexModifier = MarketPrices::getInstance()->getIndustryIndex($this->buildSystemID, 1);

		$factoryCost = 0;
		if(!isset($this->buildMaterials))
			$this->buildMaterials = array();
	
		foreach($this->buildMaterials as $material) {
			$subItemMat = $material['itemType']->getBuildMaterials();
			if(count($subItemMat) > 0) {
				$factoryCost = $factoryCost + ($material['itemType']->getFactoryCost()*round(intval($material['quantity']) * (1 - $this->getME()/100)));
			}
		}
	
		if(count($this->buildMaterials) > 0) {
			$factoryCost = $factoryCost +  ($this->calculateMaterialCost()*$indexModifier);
		} else
			return 0;
	
		return $factoryCost-($factoryCost*($this->getStationCostModifier()/100));
	}
	
	public function getTaxCost() {
		$cost = $this->getFactoryCost()*($this->getStationTax()/100);
		return round($cost,2);
	}
}

?>