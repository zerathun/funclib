<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules\cap_production;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Updateable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EveItem;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\Taxable;

class production_job extends Taxable implements Storable, Updateable {
	
	protected $jobid;
	protected $jobtype;
	protected $item;
	protected $material_efficiency;
	protected $job_price;
	protected $bpc_cost;
	protected $priority;
	protected $name;
	protected $moreDetails;
	protected $station_me;
	protected $notice;
	protected $timestamp_deadline;
	protected $additional_profit_per_part;

	
	public function __construct($jobid=0) {
		if($jobid!=0) {
			$this->loadDB($jobid);
		} else {
			
		}
	}
	
	public function newJob($typeID) {
		$sql = "INSERT INTO emb_product_job (typeID) VALUES ($typeID)";
		Database::getInstance()->sql_query($sql);
		$sql = "SELECT LAST_INSERT_ID() AS lastID FROM emb_product_job";
		$result= Database::getInstance()->sql_fetch_array(Database::getInstance()->sql_query($sql));
		$this->jobid = $result['lastID'];
	}
	
	public function loadDB($jobid) {
		$this->jobid = $jobid;
		$SQL = "
			SELECT emb_product_job.*, t3.typeID as resultTypeID FROM emb_product_job
				LEFT JOIN industryActivityProducts as t2 ON t2.typeID = emb_product_job.typeID AND t2.activityID = 1
				LEFT JOIN invTypes as t3 ON t2.productTypeID = t3.typeID
				WHERE jobID = '".$this->jobid."'";
		$res = Database::getInstance()->sql_query($SQL);
		$row = Database::getInstance()->sql_fetch_row($res);

		$this->loadRow($row);
	}

	public function loadRow($row) {
		if(empty($row['timestamp_deadline']))
			$row['timestamp_deadline'] = time();

		$this->item = EveItem::gI($row['resultTypeID'], $this->jobid);
		
		$this->setJobType($row['typeID']);
		$this->loadSubSettings($row['typeID'], $this->getJobID());
		
		$this->setME($row['mefficiency']);
		$this->setPrice($row['job_price']);
		$this->setBPCCost($row['bpc_cost']);
		$this->setPriority($row['priority']);

		$this->setStationME($row['stationME']);
		$this->setStationTax($row['stationTax']);
		$this->setNotice($row['notice']);
		$this->additional_profit_per_part($row['additional_profit_perpart']);
		$this->setDeadline($row['timestamp_deadline']);
		
		/**
		 * This part sets the list of itemsettings into the specific database
		 * If there are new values from POST these are set preferred
		 */
		$arr = $this->item->getBuildMaterials();
		foreach($arr as $itemx) {
			$var_string = $this->jobid."_".$itemx['itemType']->getTypeID();
			$resultat = intval(request_var("ME_".$var_string, -1));
			$stationme = request_var("STATIONME_".$var_string, -1.0);
			$stationtax = request_var("STATIONTAX_".$var_string, -1.0);
			$stationcostmod = request_var("COSTMODIFIER_".$var_string, -10000.0);

			if($resultat >= 0) {
				$itemx['itemType']->setME($resultat);
			}
			if($stationme >= 0) {
				$itemx['itemType']->setStationME($stationme);
			}
			if($stationtax >= 0) {
				$itemx['itemType']->setStationTax($stationtax);
			}
			if($stationcostmod > -10000) {
				$itemx['itemType']->setStationCostModifier($stationcostmod);
			}
		}
	}
	
	public function DB_Store()
	{
		// Check if the JobID already exists
		$rawMaterialCost = $job->getItem()->calculateMaterialCost(1,0);
		$factoryCost = $this->getItem()->getFactoryCost();
		$materialCostWFC = $job->getItem()->calculateMaterialCost(1,1);
		
		$SQL = "INSERT INTO emb_product_job (
					typeID, mefficiency, job_price,
					ownerID, priority,
					bpc_cost,
					producercorpID,
					calculatedFactoryPrice,
					calculatedMaterialPrice,
					update_timestamp,
					calculatedRawMaterial,
					stationME,
					notice,
					timestamp_deadline,
					additional_profit_perpart,
					stationTax,
					costReduction
					)
				VALUES ('".$this->getJobType()."',
						'".$this->getME()."',
						'".$this->getPrice()."',
						'0','".$this->getPriority()."',
						'".$this->getBPCCost()."',
						'0', $factoryCost,
						$materialCostWFC, ".time().", $rawMaterialCost, ".$this->getStationME()."
						'".str_replace("'", "\'", $this->getNotice())."',
						".$this->getDeadline().",
						".$this->getAdditional_profit_per_part().",
						".$this->getStationTax().",
						".$this->getStationCostModifier()."
								);";
		$res = Database::getInstance()->sql_query($SQL);
		$this->saveSubItems();
	}
	
	public function DB_Delete()
	{
		$sql = "DELETE FROM emb_product_job WHERE jobID = ".$this->jobid.";
				DELETE FROM emb_product_job_subitems WHERE jobID = ".$this->jobid.";
				DELETE FROM emb_product_job_partsbuilder WHERE jobID = ".$this->jobid.";			
						";
			
		 Database::getInstance()->sql_query($sql);
	}
	
	public function DB_Update()
	{
		// Update the DB with the current price calculations
		$rawMaterialCost = $this->getItem()->calculateMaterialCost(1,0);
		$factoryCost = $this->getItem()->getFactoryCost();
		$materialCostWFC = $this->getItem()->calculateMaterialCost(1,1);
		
		$SQL = "UPDATE emb_product_job SET
				typeID = '".$this->getJobType()."',
				mefficiency = '".$this->getME()."',
				job_price = '".$this->getPrice()."',
				ownerID = 0,
				priority = ".$this->getPriority().",
				bpc_cost = ".$this->getBPCCost().",
				producercorpID = 0,
				calculatedFactoryPrice = $factoryCost,
				calculatedMaterialPrice = $materialCostWFC,
				calculatedRawMaterial = $rawMaterialCost,
				update_timestamp = ".time().",
				stationME = ".$this->getStationME().",
				notice = '".str_replace("'", "\'", $this->getNotice())."',
				timestamp_deadline = ".$this->getDeadline() .",
				additional_profit_perpart = ".$this->getAdditional_profit_per_part().",
				stationTax = ".$this->getStationTax().",
				costReduction = ".$this->getStationCostModifier()."
		WHERE jobid = '".$this->jobid."'";
		Database::getInstance()->sql_query($SQL);
		$sql = "SELECT LAST_INSERT_ID() as  lastJobID FROM emb_product_job";
		$lastIdROw = Database::getInstance()->sql_fetch_array(Database::getInstance()->sql_query($sql));

		$this->saveSubItems();
	}
	
	public function saveSubItems() {
		$sql = "
			INSERT INTO emb_product_job_subitems
				(jobID, subTypeID, update_timestamp, materialEfficiency, stationEfficiency, stationTax, costReduction)
				VALUES
				";
		$buildMats = $this->getItem()->getBuildMaterials();
		$c = 0;
		
		foreach($buildMats as $material) {
			if(count($material['itemType']->getBuildMaterials())>0) {
				if($c > 0)
					$sql .= ", ";
				//$sql .= "(".$this->jobid.", ".$material['itemType']->getTypeID().",".time().",".$material['itemType']->getME().", '".$material['itemType']->getStationME()."','".$material['itemType']->getStationTax()."', '".$material['itemType']->getStationCostModifier()."')";
				$sql .= $this->getSQLValue($material['itemType']);
				$c++;
			}
		}
		$this->getSQLValue($this->getItem());
		
		$sql .= " ON DUPLICATE KEY UPDATE
		jobID=VALUES(jobID),
		subTypeID=VALUES(subTypeID),
		update_timestamp=VALUES(update_timestamp),
		materialEfficiency=VALUES(materialEfficiency),
		stationEfficiency=VALUES(stationEfficiency),
		stationTax=VALUES(stationTax)
				";
		
		if($c > 0) {
			Database::getInstance()->sql_query($sql);
		}
	}
	
	private function getSQLValue($itemType) {
		$sqladd = "(".$this->jobid.",
					".$itemType->getTypeID().",
					".time().",
					".$itemType->getME().",
					'".$itemType->getStationME()."',
					'".$itemType->getStationTax()."',
					'".$itemType->getStationCostModifier()."')";
		return $sqladd;
	}
	
	public function getItem() {
		return $this->item;
	}
	
	public function getJobID() {
		return $this->jobid;
	}
	
	public function setJobType($typeID) {
		$this->jobtype = intval($typeID);
	}
	
	public function getJobType() {
		return $this->jobtype;
	}

	public function setPrice($job_price) {
		$this->job_price = intval($job_price);
	}
	
	public function setNotice($string) {
		$this->notice = $string;
	}
	
	public function getNotice() {
		return $this->notice;
	}
	
	public function setDeadline($timestamp) {
		$this->timestamp_deadline = $timestamp;
	}
	
	public function getDeadline() {
		return $this->timestamp_deadline;
	}
	
	public function additional_profit_per_part($ppp) {
		$ppp = intval($ppp);
		if($ppp > 100)
			$ppp = 100;
		if($ppp <= 0)
			$ppp = 0;
		
		$this->additional_profit_per_part = $ppp;
	}
	
	public function getAdditional_profit_per_part() {
		if(empty($this->additional_profit_per_part))
			return 0;
		return $this->additional_profit_per_part;
	}
	
	
	// Inherited functions from Taxable

	
	public function setME($me) {
		parent::setME($me);
		$this->getItem()->setME($me);
	}
	
	public function getME() {
		if(!empty($this->getItem())) {
			return $this->getItem()->getME();
		} else {
			return parent::getME();
		}
	}
	
	public function setStationME($me) {
		parent::setStationME($me);
		$this->getItem()->setStationME($me);
	}
	
	public function getStationME() {
		if(!empty($this->getItem())) {
			return $this->getItem()->getStationME();
		} else {
			return parent::getStationME();
		}
	}
	
	public function setStationTax($me) {
		parent::setStationTax($me);
		$this->getItem()->setStationTax($me);
	}
	
	public function getStationTax() {
		if(!empty($this->getItem())) {
			return $this->getItem()->getStationTax();
		} else {
			return parent::getStationTax();
		}
	}
	
	public function setFactoryIndex($index) {
		parent::setFactoryIndex($index);
		$this->getItem()->setFactoryIndex($index);
	}
	
	public function getFactoryIndex() {
		if(!empty($this->getItem())) {
			return $this->getItem()->getFactoryIndex();
		} else {
			return parent::getFactoryIndex();
		}
	}

	public function setStationCostModifier($stationCostModifier)
	{
		$this->getItem()->setStationCostModifier($stationCostModifier);
		parent::setStationCostModifier($stationCostModifier);
	}
	
	public function getStationCostModifier() {
		if(!empty($this->getItem())) {
			return $this->getItem()->getStationCostModifier();
		} else {
			return parent::getStationCostModifier();
		}
	}
	
	public function deprecated_getTotalME() {
		return $this->getStationME() + $this->getME();
	}
	
	public function setBPCCost($bpc_cost) {
		$this->bpc_cost = intval($bpc_cost);
	}
	
	public function getBPCCost() {
		return $this->bpc_cost;
	}
	
	public function getPrice() {
		return $this->job_price;
	}
	
	public function setPriority($priority) {
		$this->priority = intval($priority);
	}
	
	public function getPriority() {
		if(empty($this->priority))
			return 0;
		return $this->priority;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getProductName() {
		return $this->moreDetails['typeName'];
	}
	
	public function getProductDetails() {
		return $this->moreDetails;
	}
	
}