<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\perry_master\src\Perry\Setup;
use zeradun\api_manager\includes\Ember\perry_master\src\Perry\Perry;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvECrest;
use zeradun\api_manager\includes\Ember\perry_master\src\Perry\Representation\Eve\v1\MarketTypePriceCollection;

class MarketPrices {
	
	private static $instance;
	
	private $timeRange = 7776000; // 2592999; // 90 Days average
	
	private $cacheTime = 86400; // 1 Day Cache
	
	private $cacheHour = 3600;
	
	private $sysWideMarketData = array();
	
	private $currentData;
	
	private $industryIndexes;
	
	private function __construct() {
		$this->loadRegisteredItems ();
	}
	public static function getInstance() {
		if (empty ( self::$instance )) {
			self::$instance = new MarketPrices ();
		}
		return self::$instance;
	}

	/**
	 * Load the DB into array with all registered items.
	 */
	public function getRegisteredList() {
		if (empty ( $this->currentData )) {
			$res = Database::getInstance ()->sql_query ( "
					SELECT t1.typeID as typeID, t1.adjustedPrice, t1.averagePrice, t1.last_update, t1.showReferencePage, t1.registered, t3.avgHighPrice, t3.avgLowPrice, t3.dailyHighPrice, t3.dailyLowPrice, t3.last_HistoryUpdate, t3.orderCount FROM emb_market_reg_items AS t1
LEFT JOIN emb_update_market_list as t2 ON t2.reference_region = 1
LEFT JOIN emb_market_reg_items_averages t3 ON t2.regionID = t3.regionID AND t1.typeID = t3.typeID
WHERE registered=1
					
					" );
			while ( $row = Database::getInstance ()->sql_fetch_array ( $res ) ) {
				$this->currentData [$row ['typeID']] = $row;
			}
		}
		return $this->currentData;
	}
	
	public function loadSystemWideTempMarketData($systems=array(), $typeIds=array()) {
		if(count($systems) == 0) {
			return array();
		}
		
		$sql = "SELECT t4.avgHighPrice,t4.avgLowPrice, t4.dailyHighPrice, t4.dailyLowPrice, t2.*, 
				t1.registered, t1.showReferencePage,
				t3.contingent, t3.priority, t3.use_contingent, t3.margin,
				t2.regionID,
				esS.sysName, esS.sov_id, esS.sovereignty_name, esS.securityStatus,
				invgroups.groupID, invgroups.groupName
			FROM emb_market_tempmarketdata as t2
				LEFT JOIN emb_market_reg_items as t1 ON t1.typeID = t2.typeID
				LEFT JOIN emb_market_reg_items_settings as t3 ON t3.systemID = t2.systemID and t2.typeID = t3.typeID
				LEFT JOIN emb_market_reg_items_averages AS t4 ON t4.typeID = t2.typeID AND t4.regionID = (SELECT regionID FROM emb_update_market_list WHERE reference_region = 1 LIMIT 0,1)
				LEFT JOIN evestatic_systems AS esS ON t2.systemID = esS.systemID
				LEFT JOIN evestatic_invtypes as invtypes ON t1.typeID = invtypes.typeID
				LEFT JOIN evestatic_invgroups AS invgroups ON invtypes.groupID = invgroups.groupID
				WHERE ";
		$c = 0;
		foreach($systems as $sys) {
			if($c > 0)
				$sqlAdd .= " OR ";
			$sqlAdd .= "t2.systemID = $sys";
			$c++;
		}
		
		if(!empty($typeIds)) {
			$sqlAdd = " (".$sqlAdd.") AND ";
			$ctx = 0;
			$addTypeStr = "";
			foreach($typeIds as $addType) {
				if($ctx > 0)
					$addTypeStr .= " OR";
				$addTypeStr .= " t1.typeID = ".$addType;
				$ctx++;
			}
			$sqlAdd .= "(".$addTypeStr.")";
		}
		
		$result = array();
		$finishedSql = $sql.$sqlAdd." ORDER BY invgroups.groupID";

		$res = Database::getInstance()->sql_query($finishedSql);
		while($row = Database::getInstance()->sql_fetch_array($res)) {
			$result[$row['systemID']][$row['buy']][$row['typeID']] = $row;
		}
		return $result;
	}
	
	/**
	 * In development not used yet
	 * @param unknown $systemID
	 */
	public function loadSystemWideMarketData($systemID) {
		$regList = $this->getRegisteredList();

		$cache_name = "sys_wide_md_$systemID";
		
		if(empty($this->sysWideMarketData[$systemID])) {
			$sql = "DELETE FROM emb_market_tempmarketdata WHERE updateTime < ".(time()-7200).";";
			Database::getInstance()->sql_query($sql);
			
			$tmpData = $this->loadSystemWideTempMarketData(array($systemID));
			
			foreach($tmpData as $sysId => $sys) {
				foreach($sys as $buyId => $buyArr) {
					foreach($buyArr as $tpId => $item) {
						$this->sysWideMarketData[$sysId][$buyId][$tpId] = $item;
					}
				}
			}
			
			/*
			$sql = "SELECT t4.avgHighPrice,t4.avgLowPrice, t4.dailyHighPrice, t4.dailyLowPrice, t2.*, 
					t1.registered, t1.showReferencePage,
					t3.contingent, t3.priority, t3.use_contingent,
					t2.regionID,
					esS.sysName, esS.sov_id, esS.sovereignty_name, esS.securityStatus 
				FROM emb_market_tempmarketdata as t2
					LEFT JOIN emb_market_reg_items as t1 ON t1.typeID = t2.typeID
					LEFT JOIN emb_market_reg_items_settings as t3 ON t3.systemID = t2.systemID and t2.typeID = t3.typeID
					LEFT JOIN emb_market_reg_items_averages AS t4 ON t4.typeID = t2.typeID AND t4.regionID = (SELECT regionID FROM emb_update_market_list WHERE reference_region = 1 LIMIT 0,1)
					LEFT JOIN evestatic_systems AS esS ON t2.systemID = esS.systemID
					WHERE t2.systemID = $systemID";
			$res = Database::getInstance()->sql_query($sql);
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				print_r($row);
				$this->sysWideMarketData[$systemID][$row['buy']][$row['typeID']] = $row;
			}
			*/
			
			if(!empty($this->sysWideMarketData[$systemID])) {
				return $this->sysWideMarketData[$systemID];
			}
			
			$result = array();
			$SQL = "SELECT xT.solarSystemID, xT.locationID, evestatic_invgroups.groupName, evestatic_invgroups.groupID, t1.stationID, evestatic_invtypes.typeName, 
				t1.typeID,
				xT.regionID,
				(sum(t1.price*t1.volume)/sum(t1.volume)) as avgSellPrice,
				max(t1.price) as mPrice, min(t1.price) as lowPrice, 
				t1.buy, 
			 	sum(t1.volume) as totalVolume, 
				sum(t1.volume * t1.price) / sum(t1.volume) AS normalizedAverage, 
				count(t1.typeID) as orderCount,
				t1.insTimestamp as updateTime,
				t3.priority, t3.contingent, t3.use_contingent, t3.margin, t5.registered, t5.showReferencePage
			FROM emb_market_detaildata AS t1
			LEFT JOIN evestatic_invtypes ON evestatic_invtypes.typeID = t1.typeID
			LEFT JOIN evestatic_invgroups ON evestatic_invtypes.groupID = evestatic_invgroups.groupID
			INNER JOIN emb_locations AS xT ON t1.stationID = xT.locationID AND xT.solarSystemID = $systemID
			LEFT JOIN emb_market_reg_items_settings AS t3 ON t3.systemID = xT.solarSystemID and t3.typeID = t1.typeID
			LEFT JOIN emb_market_reg_items_averages AS t4 ON t4.typeID = t1.typeID AND t4.regionID = t3.regionID
			LEFT JOIN emb_market_reg_items AS t5 ON t1.typeID = t5.typeID
			WHERE ";

			$c=0;
			foreach($regList as $tx) {
				if(!empty($tx['typeID'])) {
					if($c>0)
					$SQL .= " OR";
						$SQL .= " t1.typeID = ".$tx['typeID']."";
					$c++;
				}
			}
			$SQL .= " GROUP BY t1.typeID, t1.buy";
	
			
			if($c == 0)
				return array();

			$res = Database::getInstance()->sql_query($SQL);
			$values = "";
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$result[$row['buy']][$row['typeID']] = $row;
				if(!empty($row['groupID']) && !empty($row['groupName'])){
				if(strlen($values) > 0) $values .= ", ";
					$row['typeName'] = str_replace("'", "\\'", $row['typeName']);
					$values .= "(".$row['solarSystemID'].",".$row['regionID'].",'".$row['groupName']."',".$row['groupID'].",".$row['typeID'].",".$row['buy'].",'".$row['typeName']."',".$row['mPrice'].",".$row['avgSellPrice'].",".$row['lowPrice'].",".$row['totalVolume'].",".$row['normalizedAverage'].",".$row['orderCount'].",".time().")";
				}
			}
			
			$insertTempData = "INSERT INTO emb_market_tempmarketdata (systemID, regionID, groupName, groupID, typeID, buy, typeName, mPrice, avgSellPrice, lowPrice, totalVolume, normalizedAverage, orderCount, updateTime) VALUES ".$values;
			if(strlen($values) > 0)
				$res = Database::getInstance()->sql_query($insertTempData);
			
			return $this->sysWideMarketData[$systemID] = $result;
		} else {
			return $this->sysWideMarketData[$systemID];
		}
	}

	/**
	 * Return an array with information about the current market situation of the reference system
	 * @param unknown $typeIDs
	 * @throws Exception
	 */
	public static function getReferencePrices($typeIDs, $referenceRegion=10000002) {
		if(!is_array($typeIDs))
			throw new \Exception("Given Argument $typeIDs is not an array in MarketPrices.php");
		
		$SQL = "SELECT t1.*,t2.typeName, t2.groupID, t3.groupName FROM embin.emb_market_historydata as t1
				LEFT JOIN evestatic_invtypes AS t2 ON t2.typeID = t1.typeID
				LEFT JOIN evestatic_invgroups AS t3 ON t3.groupID = t2.groupID
				WHERE ";
		$cx = 0;
		foreach($typeIDs as $tId) {
			if($cx > 0) {
				$SQL .= " OR";
			}
			
			$SQL .= "
					(t1.regionID = $referenceRegion AND t1.typeID = $tId
				AND t1.timestamp_eveavg = (SELECT MAX(timestamp_eveavg) AS tstamp FROM embin.emb_market_historydata AS T4 WHERE T4.regionID = $referenceRegion AND T4.typeID = $tId))
					";
			
			
			$cx++;
		}

		$ers = Database::getInstance()->sql_query($SQL);
		$resultat = array();
		while($row = Database::getInstance()->sql_fetch_array($ers)) {
			$resultat[$row['typeID']] = $row;
		}
		return $resultat;
	}
	
	
	/**
	 *
	 * @param Integer $typeId        	
	 */
	public function deprecated_addItem($typeId, $location = 10000002) {
		if (empty ( $this->regItems [$itemId] [$location] )) {
			$data = $this->getMarketAverage ( $typeId );
			$sql = "INSERT INTO emb_market_reg_items (typeID, avgPrice, adjustedPrice, last_update, locationID, manually_added)
			VALUES ('$typeId','" . $data ['avgPrice'] . "','" . $data ['averagePrice'] . "','" . time () . "','" . $location . "', 1)";
			
			Database::getInstance ()->sql_query ( $sql );
			$this->regItems [$itemId] [$location] = $itemCache;
		}
	}
	
	private $marketTempData = array ();
	
	public function getMarketAvgPrice($typeID) {
		$regList = $this->getRegisteredList();
		if(empty($regList[$typeID])) {
			$sql = "SELECT count(typeID) as cExist FROM emb_market_reg_items WHERE typeID = $typeID";
			$cEx = Database::getInstance()->sql_fetch_array(Database::getInstance()->sql_query($sql));
			if($cEx['cExist'] > 0)
				$sql = "UPDATE emb_market_reg_items SET registered = 1 WHERE typeID = $typeID";
			else
				$sql = "INSERT INTO emb_market_reg_items (typeID, last_update, showReferencePage, registered)
					VALUES (".$typeID.", 0, 0, 1)";
			
			
			Database::getInstance()->sql_query($sql);
			unset($this->currentData);
			$regList = $this->getRegisteredList();
		}
		return $regList[$typeID]['adjustedPrice'];
	}
	
	/**
	 * all items, that are checked in the market need to be registered,
	 * that they get updated by cron job.
	 * If they are not registered
	 * the market value will not be updated
	 */
	private function loadRegisteredItems() {
		global $db;
		// Load all items from database
		$sql = "SELECT emb_market_reg_items.*,invTypes.groupID, invTypes.typeName, invGroups.* FROM emb_market_reg_items
				LEFT JOIN invTypes ON emb_market_reg_items.typeID = invTypes.typeID
				LEFT JOIN invGroups ON invTypes.groupID = invGroups.groupID";
		$res = Database::getInstance ()->sql_query ( $sql );
		while ( $row = Database::getInstance ()->sql_fetch_array ( $res ) ) {
			$this->regItems [$row ['typeID']] [$row ['locationID']] = $row;
		}
	}
	
	private $regItems;
	
	public function getRegisteredItems() {
		return $this->regItems;
	}
	
	/**
	 * get array sorted after group
	 * 
	 * @param number $location_id        	
	 * @return Ambigous <multitype:, unknown>
	 */
	public function getRegisteredItemsGroupsorted($location_id = 10000002) {
		$new_arr = array ();
		foreach ( $this->regItems as $id => $item ) {
			$grp_id = $item [$location_id] ['groupID'];
			$new_arr [$grp_id] [$id] = $item [$location_id];
		}
		ksort ( $new_arr );
		return $new_arr;
	}
	
	public function deprectade_setManuallyAdd($bool) {
		$this->manually_added = $bool ? 1 : 0;
	}
	
	private $manually_added = 0;
	
	/**
	 *
	 * @return float
	 */
	public static function harmonic_mean($array) {
		$sum = 0;
		for($i = 0; $i < sizeof ( $array ); $i ++) {
			if ($array [$i] != 0) {
				$sum += (1 / $array [$i]);
			}
		}
		if ($sum != 0) {
			$result = sizeof ( $array ) / $sum;
			return $result;
		} else {
			return 0;
		}
	}
	
	protected $itemValue;

	/**
	 *
	 * @param unknown $systems        	
	 * @param number $activityID
	 *        	// 1 Manufacturing; 8 Invention; 3 Researching Time Efficiency 4 Researching Material Efficiency; 5 Copying
	 * @return multitype:unknown
	 */
	public function getIndustryIndex($system, $activity) {
		if(empty($this->industryIndex[$system])) {
			$sql = "SELECT * FROM evestatic_industry_sysindex WHERE systemID = ".intval($system);
			$res = Database::getInstance()->sql_query($sql);
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$this->industryIndex[$system][$row['activityID']] = $row;
			}
		}
		return (float)$this->industryIndex[$system][$activity]['costIndex'];
	}
	
	/**
	 * Parse all current market data
	 */
	public function deprecated_getMarketAvgPrice($typeID) {
		global $cache;
		$cache_name = "emb_market_prices";
		$cacheIndex = $cache->get ( $cache_name );
		if (empty ( $cacheIndex ) || true) {
			$market_data = EvECrest::getInstance ()->getMarketPrices ();
			
			// $url = "https://public-crest.eveonline.com/market/prices/";
			// $rawData = Perry::fromUrl($url);
			
			$cacheIndex = array ();
			foreach ( $rawData->items as $item ) {
				$cacheIndex [$item->type->id] ['adjustedPrice'] = $item->adjustedPrice;
				$cacheIndex [$item->type->id] ['averagePrice'] = $item->averagePrice;
				$cacheIndex [$item->type->id] ['averagePrice'] = $item->averagePrice;
				$cacheIndex [$item->type->id] ['name'] = $item->type->name;
				$cacheIndex [$item->type->id] ['id'] = $item->type->id;
			}
			$cache->put ( $cache_name, $cacheIndex, $this->cacheTime );
		}
		return $cacheIndex [$typeID];
	}
}

