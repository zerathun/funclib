<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;


use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvECrest;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UpdateTask;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;


class EveStaticData {
	
	// Instance
	private static $instance;
	
	private $data;

	private $update_status;
	
	public static function getInstance() {
		if(empty(EveStaticData::$instance)) {
			EveStaticData::$instance = new EveStaticData();
		}
		return EveStaticData::$instance;
	}
	
	public static function gI() {
		return EveStaticData::getInstance();
	}
	
	/**
	 * 
	 */
	private function __construct() {

	}
	
	/**
	 * 
	 */
	public function getRegions() {
		if(empty($data['regions'])) {
			$sql = "SELECT * FROM evestatic_regions WHERE regionID < 11000000";
			$res = Database::getInstance ()->sql_query ( $sql );
			while($row = Database::getInstance ()->sql_fetch_array($res)) {
				$this->data['regions'][$row['regionID']] = $row;
			}
		}
		return $this->data['regions'];
	}
	
	/**
	 * 
	 * @param number $region_id
	 */
	public function getConstellation($region_id=0){
		if(empty($this->data['constellations'])) {
			$sql = "SELECT * FROM evestatic_constellations";
			$res = Database::getInstance ()->sql_query ( $sql );
			while($row = Database::getInstance ()->sql_fetch_array($res)) {
				$this->data['constellations'][$row['parentRegionID']][$row['constellationID']] = $row;
			}
		}
		if($region_id==0) {
			$result = array();
			foreach($this->data['constellations'] as $const) {
				foreach($const as $const1) {
					$result[] = $const1;
				}
			}
			return $result;
		}
		return $this->data['constellations'][$region_id];;
	}
	
	public function getSystems($constellation_id=0, $region_id=0) {
		if($constellation_id!= 0) {
			if(empty($this->data['systems'][$constellation_id])) {
				$sql = "SELECT * FROM evestatic_systems WHERE parentConstellationID = $constellation_id";
				$res = Database::getInstance ()->sql_query ( $sql );
				while($row = Database::getInstance ()->sql_fetch_array($res)) {
					$this->data['systems'][$row['parentConstellationID']][$row['systemID']] = $row;
				}
			}
			return $this->data['systems'][$constellation_id];
		}
		
		if($region_id != 0) {
			$sql = "SELECT * FROM evestatic_systems WHERE parentRegionID = $region_id";
				$res = Database::getInstance ()->sql_query ( $sql );
				while($row = Database::getInstance ()->sql_fetch_array($res)) {
					$this->data['systems'][$row['parentRegionID']][$row['systemID']] = $row;
				}
			return $this->data['systems'];
		}
	}
	
	private $job_result;
	
	public function updateAllDependencies() {
		$mctime = microtime(true);
		// Update Regions
		
		$task = $this->getCurrentTask('update_static_region');
		if($task->isFinished() && $task->isRunnable()) {
			$regions = EvECrest::gI()->getRegions();
			$sql = "DELETE FROM evestatic_regions";
			Database::getInstance ()->sql_query ( $sql );
			$sql = "INSERT INTO evestatic_regions (regionID, name, href) VALUES";
			$count = 0;
			
			foreach($regions->items as $region) {
				if($count > 0) 
					$sql .= ",";
				$sql .= " (".$region['id'].", '".$region['name']."', '".$region['href']."')";
				$count ++;
			}
			
			Database::getInstance ()->sql_query ( $sql );
			$task->setPeriod(172800);
			$task->updateTask(1);
			$this->job_result['update_static_region'] = "Regions updated\n";
		}
		
		// Update Constellations
		$constUpdateTask = $this->getCurrentTask('update_static_constellations');
		if($constUpdateTask->isFinished() && $constUpdateTask->isRunnable()) {
			EvECrest::gI()->setLoopCounter(200);
			$regions = $this->getRegions();
			$sql = "DELETE FROM evestatic_constellations";
			Database::getInstance ()->sql_query ( $sql );
			foreach($regions as $region) {
				$regionInfo = EvECrest::gI()->getRegionInfo($region['regionID']);
				$sql = "INSERT INTO evestatic_constellations (constellationID, name, parentRegionID)	VALUES";
				$count = 0;
				foreach($regionInfo->constellations as $constellation) 
				{
					if($count > 0)
						$sql .= ",";
					$constellationInfo = EvECrest::gI()->getConstellation($constellation['id']);
					$sql .= " (".$constellation['id'].", '".$constellationInfo->name."', ".$region['regionID'].")";
					$count++;
				}
				Database::getInstance ()->sql_query ( $sql );
			}
			EvECrest::gI()->setLoopCounter(5);
			
			$constUpdateTask->updateTask(1);
			$this->job_result['update_static_constellations'] = "Constellations updated\n";
		}
				
		$this->updateSystems();
		$this->updateLocations();
		/** Update daily market detaildata **/

		$this->updateRegItems();
		$this->updateMarketHistorydata();
		$this->updateTempMktData();
		$this->updateSystemIndustryIndexes();
		
		if($post_mail == true) {
			$this->job_result['time_run'] = "Runtime: ".number_format(((microtime(true) - $mctime)), 2)."s\n";
			$this->job_result['time'] = (microtime(true) - $mctime);
		}
		return $this->job_result;
	}
	
	public function updateRegItems() {
		$task = $this->getCurrentTask('update_regitems_adjprices');
		if($task->isFinished() && $task->isRunnable()) {
			$adjPrices = EvECrest::getInstance()->getMarketPrices();
			$mItemList = MarketPrices::getInstance()->getRegisteredList();

			$sql = "INSERT INTO emb_market_reg_items (typeID, adjustedPrice, averagePrice) VALUES ";
			$c = 0;
			foreach($adjPrices->items as $item) {
				if(!empty($mItemList[$item->type->id]) && $mItemList[$item->type->id]['registered'] == 1) {
					if(empty($item->averagePrice))
						$item->averagePrice = 0;
					if(empty($item->adjustedPrice))
						$item->adjustedPrice = 0;
					if($c>0) $sql .= ", ";
					$sql .= "(".$item->type->id.", ".$item->adjustedPrice.", ".$item->averagePrice.")";
					$c++;
				}
			}
			$sql .= "
					ON DUPLICATE KEY UPDATE typeID=VALUES(typeID),
			adjustedPrice=VALUES(adjustedPrice),
			averagePrice=VALUES(averagePrice);
					";
			if($c>0)
				Database::getInstance()->sql_query($sql);
				
			$task->setPeriod(86400);
			$task->updateTask(1,0);
		}
	}
	
	public function updateMarketHistorydata() {
		$task = $this->getCurrentTask('update_historydata');
		if($task->isFinished() && $task->isRunnable()) {
			$sql = "SELECT * FROM embin.emb_update_market_list WHERE update_region = 1";
			$res = Database::getInstance()->sql_query($sql);
			
			$mItemList = MarketPrices::getInstance()->getRegisteredList();
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$this->updateMarketData($row['regionID']);
				/** Update market history of each item wanted **/
				foreach($mItemList as $typeID => $toUpdateItem) {
					$this->updateHistorydataTypeID($typeID, $row['regionID']);
				}
			}
			$task->setPeriod(3600);
			$task->updateTask(1);
		}
	}
	
	public function updateSystemIndustryIndexes() {
		$task = $this->getCurrentTask('update_systemindustry_indexes');
		if($task->isFinished() && $task->isRunnable()) {
			$industry_indexes = EvECrest::getInstance()->getIndustryIndexes();
			$insertSql = "DELETE FROM evestatic_industry_sysindex; INSERT INTO evestatic_industry_sysindex (systemID, activityID, costIndex, activityName) VALUES ";
			$cins_count = 0;
			foreach($industry_indexes->items as $indexItem) {
				foreach($indexItem->systemCostIndices as $indices) {
					if($cins_count > 0)
						$insertSql .= ", ";
						$insertSql .= "
				('".$indexItem->solarSystem->id."',
						'".$indices['activityID']."',
						'".$indices['costIndex']."',
						'".$indices['activityName']."'
							)";
						$cins_count++;
				}
			}
			$insertSql .= ";";
			Database::getInstance()->sql_query($insertSql);
			$task->setPeriod(3600);
			$task->updateTask(1);
		}
	}
	
	public function updateSystems() {
		$task = $this->getCurrentTask('update_static_systems_1');
		if($task->isFinished() && $task->isRunnable()) {
			$task->updateTask(0,0);
			
			$alreadyPut = array();
			$sql = "SELECT * FROM evestatic_systems";
			$res = Database::getInstance()->sql_query($sql);
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$alreadyPut[$row['parentRegionID']][$row['parentConstellationID']][$row['systemID']] = $row;
			}
			
			// Take last index of the constellation array
			$constellations = $this->getConstellation();
			$count = 0;
			foreach($constellations as $cID => $const) {
				$constCrest = EvECrest::gI()->getConstellation($const['constellationID']);
			
				foreach($constCrest->systems as $system) {
					$solarsystem = EvECrest::gI()->getSolarsystem($system['id']);
					if(!empty($solarsystem->sovereignty)) {
						$sov_holder_name = htmlspecialchars($solarsystem->sovereignty->__get('name'));
						$sov_holder_name = str_replace("'", "\\'", $sov_holder_name);
						$sov_holder_id = $solarsystem->sovereignty->__get('id');
					}
					$inputArray[$const['parentRegionID']][$const['constellationID']][$system['id']]
					= array('systemID' => $system['id'],
							'sysName' => $solarsystem->name,
							'sovereignty_name' =>$sov_holder_name,
							'sov_id' => $sov_holder_id,
							'parentConstellationID' => $const['constellationID'],
							'parentRegionID' => $const['parentRegionID'],
							'securityStatus' => round($solarsystem->securityStatus,2),
							'update' => !empty($alreadyPut[$const['parentRegionID']][$const['constellationID']][$system['id']])?1:0,
					);
					$count++;
				}
			}
			
			$sql = "INSERT INTO evestatic_systems (systemID, sysName, sovereignty_name, sov_id, parentConstellationID, parentRegionID, securityStatus, tmp, updated_last)	VALUES";
			$cX = 0;
			foreach($inputArray as $rId => $fRow) {
				foreach($fRow as $cId => $cRow) {
					foreach($cRow as $sId => $sRow) {
						$ac = array_keys($sRow);
						if($cX > 0)
							$sqlAdd .= ", ";
							$sqlAdd .= "(".$sId.",'".$sRow['sysName']."','".$sRow['sovereignty_name']."',".$sRow['sov_id'].",".$cId.",".$sRow['parentRegionID'].",".$sRow['securityStatus'].", 1, ".time().")";
							$cX++;
					}
				}
			}
			
				$this->job_result['update_static_systems'] = "Systems updated\n";
				Database::getInstance ()->sql_query ( $sql.$sqlAdd );
				Database::getInstance()->sql_query('DELETE FROM evestatic_systems WHERE tmp = 0; UPDATE evestatic_systems SET tmp = 0;');
				$task->updateTask(1);
				$this->job_result['update_locations'] = "Systems updated\n";
			}
		}

	public function updateInvTypes() {
		$task = $this->getCurrentTask('update_evestatic_itemtypesngroups');
		if($task->isFinished() && $task->isRunnable()) {
			$task->updateTask(0,0);
			$page = 1;
			do {
				$invGroups[$page] = $group = EvECrest::gI()->getInventoryGroups($page);
				$pageCount = $group->pageCount;
				$page++;
				$flag = ($page <= $pageCount);
			} while($flag);
			
			$sql = "INSERT INTO evestatic_invgroups (groupID, groupName, tmp) VALUES ";
			$sqlInvTypes = "INSERT INTO evestatic_invtypes (typeID, typeName, groupID, tmp) VALUES ";
			$c=0;
			foreach($invGroups as $page => $crestFetch) {
				foreach($crestFetch->items as $item) {
					if($item->id > 0) {
						if($c > 0)
							$sql.= ", ";
						$sql .= "(".$item->id.", '".str_replace("'", "\\'", $item->name)."',1)";
						$c++;
							$iPage = 1;
							$invC =0;
							$sqlData = "";
							do {
								$invItems = EvECrest::gI()->getInventoryGroup($item->id, $iPage);
								if(empty($invItems->types)) {
									$invItems->types = array();
								}
								foreach($invItems->types as $typex) {
									if($invC>0) {
										$sqlData .= ", ";
									}
									$sqlData .= "(".$typex->id.", '".str_replace("'", "\\'", $typex->name)."', ".$item->id.", 1)";
									$invC++;
								}
								$iPage++;
								$iflag = ($iPage <= $invItems->pageCount);
							} while($iflag);
							if(strlen($sqlData) > 0) {
								Database::getInstance()->sql_query($sqlInvTypes.$sqlData);
								sleep(500000);
							}
					}
				}
			}
			Database::getInstance()->sql_query($sql);
			Database::getInstance()->sql_query("DELETE FROM evestatic_invgroups WHERE tmp = 0");
			Database::getInstance()->sql_query("UPDATE evestatic_invgroups SET tmp = 0");
			Database::getInstance()->sql_query("DELETE FROM evestatic_invtypes WHERE tmp = 0;");
			Database::getInstance()->sql_query("UPDATE evestatic_invtypes SET tmp = 0 WHERE tmp = 1");
			$task->updateTask(1,0);
		}
	}
	
	public function updateLocations() {
		$task = $this->getCurrentTask('update_static_locations');
		if($task->isFinished() && $task->isRunnable()) {
			
			EvECrest::gI()->setLoopCounter(200);
			$taskCounter = $this->getCurrentTask("update_static_location_loop_counter");
			if($taskCounter->isFinished()) {
				$sql = "DELETE FROM emb_locations";
				Database::getInstance ()->sql_query ( $sql );
				$taskCounter->updateTask(0, 0);
			} 
			
			$sql = "SELECT * FROM emb_locations";
			$res = Database::getInstance ()->sql_query ( $sql );
			while($row = Database::getInstance ()->sql_fetch_array($res)) {
				$data[$row['locationID']] = $row;
			}
			
			$sql = "SELECT stationID FROM embin.emb_market_detaildata GROUP BY stationID";
			$res = Database::getInstance ()->sql_query ( $sql );
			
			$locSQL = "INSERT INTO emb_locations (locationID, regionID, constellationID, solarSystemID, name) VALUES";
			$sql_addendum = "";
			while($row = Database::getInstance ()->sql_fetch_array($res)) {
				$locationIDs[] = $row['stationID'];
				if(empty($data[$row['stationID']]) && intval($row['stationID']) < 62000000) {
					// Get Crest Information of System
					if(strlen($sql_addendum) > 0)
						$sql_addendum .= ",";
					$locationDetail = EvECrest::gI()->getLocationDetail($row['stationID']);
					$station_name = str_replace("'", "&#39", htmlspecialchars($locationDetail->station->name) );
					$sql_addendum .= " (".$row['stationID'].", (SELECT parentRegionID FROM evestatic_constellations WHERE constellationID = '".$locationDetail->constellation->id."'),".$locationDetail->constellation->id.",".$locationDetail->solarSystem->id.",'".$station_name."')";
				}
			}
			
			Database::getInstance ()->sql_query ( $locSQL.$sql_addendum );
			
			$taskCounter->updateTask(1,0);
			EvECrest::gI()->setLoopCounter(5);
			$task->updateTask(1,0);
			$this->job_result['update_locations'] = "Locations updated\n";
		}
	}

	/**
	 * update the temporary table periodically
	 */
	public function updateTempMktData($update_anyway = false) {
		$mp = MarketPrices::getInstance();
		
		$task = $this->getCurrentTask('update_temp_cron_marketdata');
		if(($task->isFinished() && $task->isRunnable())) {
			
			$sql = "SELECT t1.systemID FROM embin.emb_market_reg_items_settings as t1
			LEFT JOIN emb_market_reg_items as t2 ON t1.typeID = t2.typeID
			WHERE use_contingent = 1 GROUP BY t1.systemID;";
				$res = Database::getInstance ()->sql_query ( $sql );
			$task->updateTask(0);
			while($row = Database::getInstance ()->sql_fetch_array ( $res )) {
				$mp->loadSystemWideMarketData($row['systemID']);
				$this->job_result['update_temp_'.$row['systemID']] = "Load System Temp Data from ".$row['systemID'];
				
				/*$t1_taskname = "update_temp_mkdata_system_id_".$row['systemID'];
				$t1MarketDataTask = $this->getCurrentTask($t1_taskname);
				if($t1MarketDataTask->isFinished() && $t1MarketDataTask->isRunnable()) {
					$t1MarketDataTask->updateTask(0);
					
					$this->job_result['update_temp_'.$row['systemID']] = "Load System Temp Data from ".$row['systemID'];
					$t1MarketDataTask->setPeriod(3530);
					$t1MarketDataTask->updateTask(1);
				}
				*/
			}
			$task->setPeriod(600);
			$task->updateTask(1);
		}
	}
	
	/**
	 * Updates the Market Data from CREST
	 * @param unknown $region_id
	 */
	private function updateMarketData($region_id) {
		$task_name = 'update_market_data_'.$region_id;
		$marketBlock = $this->getCurrentTask('allow_updating_market_data');
		$task = $this->getCurrentTask($task_name);
		if($marketBlock->isFinished() && $task->isFinished() && $task->isRunnable()) {
			ini_set ( 'max_execution_time', 300 );
			$task->updateTask(0, 0);
			Database::getInstance ()->sql_query ( "DELETE FROM emb_market_detaildata WHERE regionID = ".intval($region_id). " AND temp = 1" );
			$pageCount = 1;
			do {
				$task->updateTask(0, $pageCount);
				$data = EvECrest::getInstance ()->getMarketDetailData ( $region_id, $pageCount );
				$this->insertMarketData($data, $region_id, $pageCount);
				$pageCount++;
				
			} while($pageCount <= intval($data->pageCount));
			
			Database::getInstance ()->sql_query ( "DELETE FROM emb_market_detaildata WHERE temp = 0 AND regionID = ".intval($region_id) ) ;
			Database::getInstance ()->sql_query ( "UPDATE emb_market_detaildata SET temp = 0 WHERE regionID = ".intval($region_id) ) ;
			$marketBlock->updateTask(1,0);
			$task->updateTask(1, 0);
			$this->job_result[$task_name] = "Marketdata from $region_id updated\n";
			//$this->updateTempMktData(true);
		}
	}
	
	/**
	 * Insert the Market Data Query
	 * @param unknown $data
	 * @param unknown $regionID
	 * @param unknown $page
	 */
	private function insertMarketData($data, $regionID, $page) {
		$sql = "INSERT INTO emb_market_detaildata (typeID, regionID, price, volume, volumeEntered, stationID, buy, insTimestamp, pageNr, temp)
				VALUES";
		$count = 0;
		foreach ( $data->items as $objData ) {
			if ($count > 0)
				$sql .= ", ";
				$appendix = "
			 (" . $objData->type . ", $regionID, " . $objData->price . ", " . $objData->volume . ", " . $objData->volumeEntered . ", " . $objData->stationID . ", " . intval ( $objData->buy ) . ", " . time () . ", " . $page . ", 1)";
				$count ++;
			
				$sql .= $appendix;
		}
		unset ( $objData );
		unset ( $data );
		Database::getInstance ()->sql_query ( $sql );
	}
	

	
	/**
	 * public function to ask if task is active or not
	 * @param String $task
	 * @return bool
	 */
	public function isTaskActive($task) {
		$arr = $this->getCurrentTask($task);
		return !$arr->isFinished();
	}
	
	
	/**
	 * 
	 * @param string $table_name
	 */
	private function updateCurrentTask($task, $finished, $lastQuery) {
		if(empty($this->update_status[$task]))
			$this->update_status[$task] = new UpdateTask($task);
		
		$finished = $finished ? 1:0;
		$lastQuery = intval($lastQuery);
		$this->update_status[$task]->updateTask($finished, $lastQuery);
	}
	
	private function getCurrentTask($task) {
		if(empty($this->update_status[$task]))
			$this->update_status[$task] = new UpdateTask($task);
		return $this->update_status[$task];
	}
	
	/** This section will parse the current Market Historydata into the Database **/
	private function updateHistorydataTypeID($typeID,$regionID) {
		$task_name = "update_historydata_".$typeID."_".$regionID;
		
		$task = $this->getCurrentTask($task_name);
		
		if($task->isFinished() && $task->isRunnable()) {
			$task->updateTask(0,0);
			$avg_prices = EvECrest::getInstance ()->getMarketAveragesHistoryOfType_and_Region ( $typeID, $regionID );
			$storage_time = 24*60*60*91; // Three Month in seconds
			$delete_data = "DELETE FROM emb_market_historydata WHERE typeID = $typeID AND regionID = $regionID";
			Database::getInstance ()->sql_query ($delete_data);
			
			$sql = "INSERT INTO emb_market_historydata (typeID,
																regionID,
																volume,
																orderCount,
																timestamp_update,
																timestamp_eveavg, highPrice, lowPrice, avgPrice)
															VALUES ";
			
			$count = 0;
			for($x = $avg_prices->totalCount; $x>($avg_prices->totalCount-91); $x --) {
				$mvalue = $avg_prices->items [$x];
				if(!empty($mvalue['date'])) {
					$unix_time = strtotime($mvalue['date']);
					if($unix_time > time()-$storage_time) {
						if($count > 0) {
							$sql .= ",";
						}
						$count++;
						$sql .= "($typeID, $regionID, ".intval($mvalue->offsetGet('volume')).", ".intval($mvalue->offsetGet('orderCount_str')).",".time().", ".intval($unix_time).",".$mvalue->offsetGet('highPrice').",".$mvalue->offsetGet('lowPrice').",".$mvalue->offsetGet('avgPrice').")";
					}
				} else {
					
				}
			}
			if($count > 0) {
				Database::getInstance ()->sql_query ($sql);
			}
			$task->setPeriod(86400);
			$task->updateTask(1);
			
			/** Do the market average stuff on region */
			$sql = "SELECT * FROM emb_update_market_list WHERE reference_region = 1";
			$row = Database::getInstance()->sql_fetch_array(Database::getInstance()->sql_query($sql));
			if($row['regionID'] == $regionID && $row['reference_region'] == 1) {
				// Only one reference region can exist (Checkbox)
				$sql = "
						SELECT AVG(highPrice) AS sellAvg, AVG(lowPrice) AS buyAvg,
							(SELECT highPrice as hP FROM emb_market_historydata AS t1
							INNER JOIN (SELECT typeID, regionID, MAX(timestamp_eveavg) as maxr FROM emb_market_historydata AS innerT1 GROUP BY typeID, regionID) AS t2 ON t2.typeID = t1.typeID AND t2.regionID = t1.regionID AND t2.maxr = t1.timestamp_eveavg
							WHERE t1.typeID = $typeID AND t1.regionID = $regionID) as dailyHighPrice,
							(SELECT lowPrice as hP FROM emb_market_historydata AS t1
							INNER JOIN (SELECT typeID, regionID, MAX(timestamp_eveavg) as maxr FROM emb_market_historydata AS innerT1 GROUP BY typeID, regionID) AS t2 ON t2.typeID = t1.typeID AND t2.regionID = t1.regionID AND t2.maxr = t1.timestamp_eveavg
							WHERE t1.typeID = $typeID AND t1.regionID = $regionID) as dailyLowPrice,
							(SELECT orderCount as hP FROM emb_market_historydata AS t1
							INNER JOIN (SELECT typeID, regionID, MAX(timestamp_eveavg) as maxr FROM emb_market_historydata AS innerT1 GROUP BY typeID, regionID) AS t2 ON t2.typeID = t1.typeID AND t2.regionID = t1.regionID AND t2.maxr = t1.timestamp_eveavg
							WHERE t1.typeID = $typeID AND t1.regionID = $regionID) as odCount
							FROM emb_market_historydata as resT
							WHERE regionID = $regionID AND typeID = $typeID
						";
				
				$row = Database::getInstance()->sql_fetch_array(Database::getInstance ()->sql_query ( $sql ) );
				
				
				$sql = "UPDATE emb_market_reg_items SET
					dailyHighPrice = " . ($row ['dailyHighPrice']*0) . ",
					dailyLowPrice = '" . ($row ['dailyLowPrice']*0) . "',
					avgHighPrice = '" . ($row['sellAvg']*0) . "',
					avgLowPrice = '" . ($row['buyAvg']*0) . "',
					orderCount = '". ($row['odCount']*0) ."',
					last_HistoryUpdate = ".time()."
						WHERE typeID = '" . $typeID ."'
						";
				Database::getInstance ()->sql_query ( $sql );
				$sql = "SELECT * FROM emb_market_reg_items_averages WHERE typeID = $typeID AND regionID = $regionID";
				$checkAvgTable = Database::getInstance()->sql_fetch_array(Database::getInstance ()->sql_query ( $sql ) );
				if(empty($checkAvgTable)) {
					$sql = "INSERT INTO emb_market_reg_items_averages (typeID, regionID, avgHighPrice, avgLowPrice, dailyHighPrice, dailyLowPrice, orderCount, last_HistoryUpdate )
							VALUES ($typeID, $regionID, ".$row['sellAvg'].", ".$row['buyAvg'].", ".$row['dailyHighPrice'].",  ".$row['dailyLowPrice'].", ".$row['odCount'].",".time().")";
				} else {
					$sql = "UPDATE emb_market_reg_items_averages SET
								avgHighPrice = ".$row['sellAvg'].",
								avgLowPrice = ".$row['buyAvg'].",
								dailyHighPrice = ".$row['dailyHighPrice'].",
								dailyLowPrice = ".$row['dailyLowPrice'].",
								orderCount = ".$row['odCount'].",
								last_HistoryUpdate = ".time()."
							WHERE typeID = $typeID AND regionID = $regionID
							";
				}
				Database::getInstance ()->sql_query ( $sql );
			}
			
			
			$this->job_result[$task_name] = "Historydata $typeID in $regionID updated\n";
			
			// Refresh all temporary Database
			//$this->updateTempMktData(true);
		}
	}
}

?>