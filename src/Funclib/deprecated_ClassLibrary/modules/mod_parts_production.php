<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;
use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_handler;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\database;
use zeradun\api_manager\includes\Ember\ClassLibrary\modules\cap_production\production_job;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvECrest;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\NameFetcher;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EvEJobs;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EveItem;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EveStaticData;
class mod_parts_production extends module_abstract {
	
	protected $template;
	protected $job;
	protected $resourceItemsNeeded = array();
	
	public function __construct($args=array())
	{
		
	}
	
	public function getModuleId() { return 2; }
	
	public function mainOutput($template)
	{
		$this->template = $template;
		$er = ErrorHandler::getErrorHandler();
		
		
		$microtime_total = microtime(true)*1000;

		$existing_job = request_var('jobid', 0);
		if(!empty($existing_job)) {
			$this->job = new production_job($existing_job);
		}
		
		$this->displayJobList($existing_job);
		$this->listCapitalParts();
		
		$template->assign_var('has_error', $er->containsError());
		
		$it = $er->getIterator ();
		if ($it->count () == 0) {
			
		} else {
			$errList = $er->getList();
			foreach ( $errList as $item ) {
				$template->assign_block_vars('errorlist', array('VAR1' => $item->getErrorMsg ()));
			}
		}
		return "Parts Production";
	}
	
	public function shortOutput()
	{
		
	}
	
	public function hasAccess()
	{
	
	}

	private function displayMineralCount() {
		$market = MarketPrices::getInstance();
		if(!empty($this->resourceItemsNeeded))	 {
			/**
			<div class="grid_4 height_1"><img class="title-img" src="https://image.eveonline.com/Type/{minerallist.MINIMG_TYPEID}_32.png"> {minerallist.MIN_NAME}</div>
			<div class="grid_4 height_1">{minerallist.MIN_QUANTITY}</div>
			<div class="grid_4 height_1">{minerallist.MIN_MEQUANTITY}</div>
			<div class="grid_4 height_1">{minerallist.MIN_TOTPRICE}</div>
			**/
			
			if(!empty($this->resourceItemsNeeded))	 {
				$refPrices = $market->getReferencePrices(array_keys($this->resourceItemsNeeded));
				foreach($this->resourceItemsNeeded as $typeID => $item) {
					$total_me_price = $item['quantity_me']*$refPrices[$typeID]['avgPrice'];
					$this->template->assign_block_vars('minerallist', array(
							'MIN_NAME' => $item['typeName'],
							'MINIMG_TYPEID' => $typeID,
							'MIN_QUANTITY' => number_format(round($item['quantity'], 0),0,".","'"),
							'MIN_MEQUANTITY' => number_format(round($item['quantity_me'],0),0,".","'"),
							'MIN_TOTPRICE' => number_format(round($total_me_price,2),2,".","'"),
					));
				}
			} else  {
				$refPrices = $market->getReferencePrices($items);
			}
		}
	}
	
	private function displayMineralPrices() {
		$market = MarketPrices::getInstance();
		$items = array(34,35,36,37,38,39,40);
		
		if(!empty($this->resourceItemsNeeded))	 {	
			$refPrices = $market->getReferencePrices(array_keys($this->resourceItemsNeeded));
		} else  {
			$refPrices = $market->getReferencePrices($items);
		}
		
		foreach($refPrices as $item) {
			$this->template->assign_block_vars('mineral_market_list', array(
					'TYPENAME' => $item['typeName'],
					'TYPEID' => $item['typeID'],
					'AVG_PRICE' => number_format(round($item['avgPrice'], 2),2,".","'"),
					'LOW_PRICE' => number_format(round($item['lowPrice'],2),2,".","'"),
					'HIGH_PRICE' => number_format(round($item['highPrice'],2),2,".","'"),
			));
		}
	}
	
	private function displayJobList($only_with_id=0) {
		$sql = "SELECT emb_product_job.*,t2.typeName FROM emb_product_job
			LEFT JOIN industryActivityProducts as t1 ON emb_product_job.typeID = t1.typeID AND t1.activityID = 1
			LEFT JOIN invTypes AS t2 on t1.productTypeID = t2.typeID";
		if($only_with_id != 0) {
			$sql .= " WHERE jobID = ".intval($only_with_id);
		}
		$res = Database::getInstance()->sql_query($sql);

		while($row = Database::getInstance()->sql_fetch_row($res)) {
			if(!empty($this->job))
				$job = $this->job;
			else {
				$job = new production_job($row['jobID']);
				$job->loadDB($row['jobID']);	
			}
			$link = module_handler::getInstance()->getModuleURL(array('jobid' => $row['jobID']));
			$materialCostWFC = $job->getItem()->calculateMaterialCost(1,1);
			$rawMaterialCost = $job->getItem()->calculateMaterialCost(1,0);
			$factoryCost = $job->getItem()->getFactoryCost();
			$TaxCost = $job->getItem()->getTaxCost();
			// Calculate effective Price of the job
			$parts_total_profit = $materialCostWFC*($job->getAdditional_profit_per_part()/100);
			$cost = $factoryCost+$TaxCost+$job->getBPCCost()+$materialCostWFC;
			$totalProfit = ($job->getPrice()-$cost)-$parts_total_profit;
			
			$this->template->assign_block_vars('joblist', array('TYPE_ID' => $row['typeID'],
					'JOB_PRICE' => number_format(round($row['job_price'], 2),2,".","'"),
					'JOB_ME' => $row['mefficiency'],
					'JOB_LIST_HREF' => $link,
					'JOBID' => $job->getJobID(),
					'JOB_NAME' => $row['typeName'].$job->getProductName(),
					'MATERIAL_COST' => number_format($materialCostWFC,2,".","'"),
					'INSTALLATION_PRICE' => number_format($factoryCost,2,".","'"), 
					'INSTALLATION_TAX' => number_format(round($TaxCost,2),2,".","'"), 
					'EFFECTIVE_PRICE' => number_format($cost,2,".","'"),
					'PROFIT' => number_format($totalProfit,2,".","'"),
					'PARTS_PROFIT_PARTICIPATION' => number_format($parts_total_profit,2,".","'"),
					'ONLY_RESOURCES_COST' => number_format($rawMaterialCost,2,".","'"),
					'DEADLINE' => date("d.m.Y - H:i", $job->getDeadline()),
			));
		}
	}
	
	private function listCapitalParts() {
		$existing_job = request_var('jobid', 0);

		$mineral_quantity = array();
		if(!empty($existing_job)) {
			$build_mats = $this->job->getItem()->getBuildMaterials();
			$this->template->assign_var('DISPLAY_PARTS', 1);
			
			/** Delete Parts Builder if Link is shown **/
			$delPa = request_var('delPartID',0);
			$delTypeId = request_var('delTypeID', 0);
			if($delPa > 0 && $delTypeId > 0 && $this->hasWriteAccess()) {
				$SQL = "DELETE FROM emb_product_job_partsbuilder WHERE jobID = $existing_job AND partID = $delPa AND typeID = $delTypeId";
				Database::getInstance()->sql_query($SQL);
			}
			$okPa = request_var('okPartID', 0);
			$okTypeID = request_var('okTypeID', 0);
			if($okPa > 0 && $okTypeID > 0) {
				$SQL = "UPDATE emb_product_job_partsbuilder SET finished = 1 WHERE jobID = $existing_job AND partID = $okPa AND typeID = $okTypeID";
				Database::getInstance()->sql_query($SQL);
			}
			
			foreach($build_mats as $material) {
				$number_afterme = round($material['quantity']*(1-($this->job->getME()+$this->job->getStationME())/100),0);
				$factoryCost = $material['itemType']->getFactoryCost();
				$materialCostME = $material['itemType']->calculateMaterialCost(1,0);
				$materialCostRaw = $material['itemType']->calculateMaterialCost(false);
				$stationTaxCost = $material['itemType']->getTaxCost();
				
				$produceCost = $materialCostME+$factoryCost+$stationTaxCost-(0);
				
				$profitIncluded = $produceCost*(1+($this->job->getAdditional_profit_per_part()/100));
				$profitIncluded = round($profitIncluded,2,PHP_ROUND_HALF_EVEN);
				$totalItemCost = $profitIncluded*$number_afterme;
				$totalSubProfitPerParts = $totalItemCost-($materialCostME*$number_afterme);
				/** Prepare data for Producer Information */

				$tID = $material['typeID'];
				$contractor = request_var("contractor_".$tID,"");
				$contractor = $this->makeSQLSafe($contractor);
				$ProducedAmount = request_var("amount_".$tID,0);
				
				$sql = "SELECT * FROM emb_product_job_partsbuilder WHERE jobID = $existing_job AND typeID = ".$tID;
				$dataPartsbuilder[$existing_job][$material['typeID']] = array();
				$countNrs = 0;
				if($ProducedAmount > 0 && strlen($contractor) > 0) {
					$res = Database::getInstance()->sql_query($sql);
					
					while($dbData = Database::getInstance()->sql_fetch_array($res)) {
						$countNrs = $countNrs+$dbData['amount'];
						$dataPartsbuilder[$existing_job][$material['typeID']][$dbData['partID']] = $dbData;
					}
					
					if($number_afterme - ($countNrs+$ProducedAmount) >= 0) {
						ksort($dataPartsbuilder[$existing_job][$material['typeID']]);
						$dbKeys = array_keys(	$dataPartsbuilder[$existing_job][$material['typeID']]);
						$key = $dbKeys[count($dbKeys)-1]+1;
							
						$UPDATE = "INSERT INTO emb_product_job_partsbuilder (jobID, typeID, partID, builder, amount,contractedPrice)
						VALUES ($existing_job, $tID, $key, '".$contractor."',$ProducedAmount,$profitIncluded)
						ON DUPLICATE KEY UPDATE
						jobID=VALUES(jobID),
						typeID=VALUES(typeID),
						partID=VALUES(partID),
						builder=VALUES(builder),
						amount=VALUES(amount),
						contractedPrice=VALUES(contractedPrice)
						";
						Database::getInstance()->sql_query($UPDATE);
					}
				}
				
				$mpp = Database::getInstance()->sql_query($sql);
				$countNrs = 0;
				while($mpData = Database::getInstance()->sql_fetch_array($mpp)) {
					$countNrs = $countNrs+$mpData['amount'];
					$dataPartsbuilder[$existing_job][$material['typeID']][$mpData['partID']] = $mpData;
					ksort($dataPartsbuilder[$existing_job][$material['typeID']]);
				}
				$remainingAmount = $number_afterme - $countNrs;
				
				/** End of producer Information **/

				
				$this->template->assign_block_vars('partlist', array(
						'TYPENAME' => $material['typeName'],
						'IMG_TYPEID' => $material['typeID'],
						'QUANTITY' => number_format($material['quantity'],0,".","'"),
						'QUANTITY_ME' => number_format($number_afterme,0,".","'"),
						'MINERAL_PRICE' => number_format($materialCostRaw,2,".","'"),
						'MINERAL_PRICEME' => number_format(round($materialCostME,2),2,".","'"),
						'FACTORY_COST_PP' => number_format(round($factoryCost,2),2,".","'"),
						'FACTORY_TAX' => number_format(round($stationTaxCost,2),2,".","'"),
						'ITEM_PRICE_FC' => number_format(round($produceCost,2),2,".","'"),
						'PROFIT_INCLUDED_PRICE' => number_format($profitIncluded,2,".","'"),
						'TOTAL_PROFIT' => number_format($totalSubProfitPerParts,2,".","'"),
						'TOTALCOST_ME' => number_format(round($totalItemCost,2,PHP_ROUND_HALF_EVEN),2,".","'"),
						'TYPEID' => $material['typeID'],
						'VAL_REMAINING_AMOUNT' => $remainingAmount,
						'parts_all_reserved' => $remainingAmount > 0,
				));

				$subMaterial = $material['itemType']->getBuildMaterials();
				$materialEfficiency = $material['itemType']->getME();
				$statefficiency = $material['itemType']->getStationME();
				$stationTax = $material['itemType']->getStationTax();
				$stationCostModifier = $material['itemType']->getStationCostModifier();
				
				$c = 0;
				$rowspan = count($subMaterial);
				foreach($subMaterial as $subMat) {
					$countwMe = round($subMat['quantity']*(1-(($materialEfficiency+$statefficiency)/100)),0);
					// Prepare to count the total minerals / resources needed
					
					if($subMat['itemType']->isNotBuildable()) {
						$typeID = $subMat['itemType']->getTypeID();
						$this->resourceItemsNeeded[$typeID]['quantity'] = $this->resourceItemsNeeded[$typeID]['quantity']+$subMat['quantity']*$material['quantity'];
						$this->resourceItemsNeeded[$typeID]['quantity_me'] = $this->resourceItemsNeeded[$typeID]['quantity_me']+$countwMe*$number_afterme;
						$this->resourceItemsNeeded[$typeID]['typeName'] = $subMat['typeName'];
					}
					// End of count

					$this->template->assign_block_vars('partlist.materiallist',
						array(	'FIRSTLINE' => ($c <= 0)?"1":0,
								'ROWSPAN' => $rowspan,
								'QUANTITY' => number_format($subMat['quantity'],0,".","'"),
								'TYPENAME' => $subMat['typeName'],
								'IMG_TYPEID' => $subMat['typeID'],
								'TOTAL_SUBITEM' => number_format($countwMe*$material['quantity'],0,".","'"),
								'TOTAL_SUBITEM_ME' => number_format($countwMe*$number_afterme,0,".","'"),
								'QUANTITY_ME' => number_format($countwMe,0,".","'"),
								'MINERAL_PRICE' => number_format(round($subMat['itemType']->calculateMaterialCost(),2),2,".","'"),
								'PARTME' => $materialEfficiency,
								'STATIONME' => $statefficiency,
								'STATIONTAX' => $stationTax,
								'COST_MODIFIER' => $stationCostModifier,
								'JOBID' => $existing_job,
								'ME_TYPEID' => $material['typeID'],
								'SUBDIV_MINERAL_PRICE' => number_format(round($countwMe*$number_afterme*$subMat['itemType']->calculateMaterialCost(1,1),2),2,".","'"),
						));
					$c++;
				}

				foreach($dataPartsbuilder[$existing_job][$material['typeID']] as $kx => $mpDat) {
					$href = module_handler::getInstance()->getModuleURL(array('delTypeID' => $material['typeID'], 'delPartID' => $mpDat['partID']));
					$okhref = module_handler::getInstance()->getModuleURL(array('okTypeID' => $material['typeID'], 'okPartID' => $mpDat['partID']));
					
					$this->template->assign_block_vars('partlist.contractorlist',
							array(	'CONTRACTOR' => $mpDat['builder'],
									'AMOUNT' => $mpDat['amount'],
									'TYPEID' => $mpDat['typeID'],
									'CONTRACTED_PRICE' => number_format($mpDat['contractedPrice'],2,",","'"),
									'TOTALCONTRACTED_PRICE' => number_format(round($mpDat['contractedPrice']*$mpDat['amount'],2),2,".",""),
									'CONTRACT_DELIVERED' => $okhref,
									'SHOW_DEL_LINK' => $mpDat['finished'] == 0 ? 1:0,
									'SHOW_OK_LINK' => $mpDat['finished'] == 0 ? 1:0,
									'DEL_HREF' => $href,
									'CONTRACT' => $mpDat['finished'] == 0 ? 0:1,
								));
				}
			}
		} else {
			
		}
	}
	
	
	private function displayJobDetails() {
		global $PDDB;
		
		$existing_job = request_var('jobid', 0);
		if($existing_job != 0) {
			$this->template->assign_var('NEW_JOB_HIDDEN_VAL', $existing_job);
			$job = $this->job;
		} else {
			// Set Default Values
			$job = new production_job();
			$job->setPrice(0);
			$job->setPriority(0);
			$job->setBPCCost(0);
			$job->setJobType(41583);
		}
		
		// This variable defines if the job has to be updated or saved
		$var = request_var('new_job_hidden_val', 0);
		
		if($var == 1) {
			//Request all form data
			$jobtype = request_var('new-job', 0);
			$me = request_var('job-me', 0);
			$bpc_cost = request_var('bpc-cost', 0);
			$job_price = request_var('job-price', 0);
			$priority = request_var('job-priority', 0);
			$stationME = request_var('station_me_modifier',0.0);
			$additional_profit_perpart = request_var('additional_profit', 0);
			$stationTax = request_var("station_tax_main", 0.0);
			$stationCostModifier = request_var('station_cost_modifier', 0.0);
				
			$notice = request_var('job_notice', "");
			$notice = str_replace("SELECT", "", $notice);
			$notice = str_replace("GRANT", "", $notice);
			$notice = str_replace("DELETE", "", $notice);
			$notice = str_replace("UPDATE", "", $notice);
			$notice = str_replace("INSERT", "", $notice);
			$notice = htmlspecialchars($notice);
				
			$date = request_var('input_date',"");
			$date = preg_split("(\.)", $date);
			if(empty($date[1]))
				$date[1] = 0;
			if(empty($date[2]))
				$date[2] = 0;
			foreach($date as $k => $t) {
				$date[$k] = intval($t);
			}
			
			$time = request_var('input_time', "");
			$time = preg_split("(:)", $time);
			foreach($time as $k => $t)
				$time[$k] = intval($t);
			if(empty($time[1]))
				$time[1] = 0;
			$timestamp = mktime((int)$time[0],$time[1],0,$date[1], $date[0], $date[2]);
				
			// If the job has not been created yet - define a new job ID
			if($existing_job == 0) {
				$job->newJob($jobtype);
				$job->loadDB($job->getJobID());
				$job->getItem()->setStandards(array('stationME' => $stationME, 'ME' => $me, 'stationTax' => $stationTax, 'costModifier' => $stationCostModifier));
			}
			
			// Set all form data into the Job
			$job->setNotice($notice);
			$job->setDeadline($timestamp);
			$job->setJobType($jobtype);
			$job->setPrice($job_price);
			$job->setBPCCost($bpc_cost);
			$job->setPriority($priority);
			$job->additional_profit_per_part($additional_profit_perpart);
			
			$job->setME($me);
			$job->setStationME($stationME);
			$job->setStationTax($stationTax);
			$job->setStationCostModifier($stationCostModifier);

			$job->DB_Update();
		} else {
			// If a job has been selected fill the form with current values
		}
		
		$sql = "SELECT * FROM invTypes 
				LEFT JOIN invGroups ON invTypes.groupID = invGroups.groupID WHERE invGroups.groupID = 110 OR invGroups.groupID = 1013 OR invGroups.groupID = 537  OR  invGroups.groupID = 643  OR invGroups.groupID = 525  OR invGroups.groupID = 945 OR invGroups.groupID = 944 LIMIT 0,1000";
		
		$sql = "SELECT * FROM invGroups AS grp
				LEFT JOIN invTypes ON grp.groupID = invTypes.groupID
				WHERE (
grp.groupID = 105
OR grp.groupID = 106
OR grp.groupID = 107
OR grp.groupID = 108
OR grp.groupID = 109
OR grp.groupID = 110
OR grp.groupID = 111
OR grp.groupID = 477
OR grp.groupID = 487
OR grp.groupID = 489
OR grp.groupID = 503
OR grp.groupID = 516
OR grp.groupID = 525
OR grp.groupID = 537
OR grp.groupID = 643
OR grp.groupID = 651
OR grp.groupID = 914
OR grp.groupID = 915
OR grp.groupID = 944
OR grp.groupID = 945
OR grp.groupID = 996
OR grp.groupID = 1013
OR grp.groupID = 1048
OR grp.groupID = 1137
OR grp.groupID = 1317
OR grp.groupID = 1462
OR grp.groupID = 1542
OR grp.groupID = 1679
OR grp.groupID = 1703
OR grp.groupID = 1707
OR grp.groupID = 1708
OR grp.groupID = 1718  )
				ORDER BY invTypes.groupID,invTypes.typeID ASC";
		
		$res = Database::getInstance()->sql_query($sql);
		$jobID = $job->getJobID();
		
		if(!empty($jobID)) {
			$this->template->assign_var('DISABLE_BLUEPRINT_CHOOSE', (!empty($jobID))?" DISABLED":"");
			$this->template->assign_var('CHOSEN_TYPEID', $job->getJobType());
			$this->template->assign_var('CHOOSE_TYPE_DISABLED', 1);
		}
		
		$this->template->assign_var('JOB_PRICE', $job->getPrice());
		$this->template->assign_var('BPC_COST', $job->getBPCCost());
		$this->template->assign_var('STATION_ME_MODIFIER', number_format($job->getStationME(),2,".","'"));
		$this->template->assign_var('NEW_JOB_HIDDEN_VAL', "1");
		$this->template->assign_var('JOB_LIST_HREF', $mod);
		
		$time = date("H:i", $job->getDeadline());
		$date = date("d.m.Y", $job->getDeadline());
		$this->template->assign_var('DATE_VALUE', $date);
		$this->template->assign_var('TIME_VALUE', $time);
		$this->template->assign_var('JOB_NOTICES_VALUE', $job->getNotice());
		$this->template->assign_var('ADD_PROFIT', $job->getAdditional_profit_per_part());
		$this->template->assign_var('STATION_TAX', round($job->getStationTax(),2));
		$this->template->assign_var('COST_MODIFIER', round($job->getStationCostModifier(), 2));
		
		while($row = Database::getInstance()->sql_fetch_array($res)) {
			if($job->getJobType() != 0 && $job->getJobType() == $row['typeID']) {
				$selected = " selected";
			} else $selected = "";

			if(!empty($row['typeName']) && !empty($row['typeID'])) {
				$this->template->assign_block_vars('choosejobtype',
								array('JOB_TYPE_NAME' => $row['typeName'],
										'JOB_TYPE_VALUE' => $row['typeID'],
										'JOB_TYPE_SELECTED' => $selected,
				));
			}
		}
		
		for($x = 10; $x >= 0;$x-- ) {
			if($x == $job->getME())
				$selected = " selected";
			else $selected = "";
			$this->template->assign_block_vars('job_me_list', array('JOB_ME_VALUE' =>$x, 'JOB_ME_NAME' => $x."%", 'JOB_ME_SELECTED' => $selected));
		}
	}
	
	/**
	 * Inherited function; Return a single array with names to exclude in the Main-Navigation Elements
	 */
	public function urlExcludeArr() {
		return array('jobid' => 0);
	}
	
}