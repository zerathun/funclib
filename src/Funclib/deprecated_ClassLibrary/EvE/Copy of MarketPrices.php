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

	private function __construct() {
		$this->initializeMarketData ();
	}
	public static function getInstance() {
		if (empty ( self::$instance )) {
			self::$instance = new MarketPrices ();
		}
		return self::$instance;
	}
	protected function initializeMarketData() {
		global $cache;
		$cache_name = 'update_current_marketprices';
		$itemCache = $cache->get ( $cache_name );
		
		//$this->updateAverageMarketData ();
		//$this->loadDailyMarketData_IntoDB();
		// $this->calculateMarketMeans();
		$this->loadRegisteredItems ();
	}
	
	/**
	 * This function does load all the market data into database
	 * and should only be used by cron (system load)
	 */
	public function updateAverageMarketData() {
		// Market Averages will load average data into the DB
		$marketPrices = EvECrest::getInstance ()->getMarketPrices ();
		$currentData = $this->getRegisteredList ();
		
		$c = 0;
		
		$sql = "";
		for($x = 0; $x < $marketPrices->totalCount; $x ++) {
			$mvalue = $marketPrices->items [$x];
			if (! empty ( $currentData [$mvalue->offsetGet ( 'type' )->__get ( 'id' )] )) {
				$sql .= "\nUPDATE emb_market_reg_items SET avgPrice = '" . ( float ) $mvalue->offsetGet ( 'averagePrice' ) . "',
							adjustedPrice = '" . ( float ) $mvalue->offsetGet ( 'adjustedPrice' ) . "',
							last_update = " . time () . "
						WHERE typeID = '" . $mvalue->offsetGet ( 'type' )->__get ( 'id' ) . "'; ";
			}
		}
		
		Database::getInstance ()->sql_query ( $sql );
	}
	
	public function calculateMarketMeans() {
		ini_set ( 'max_execution_time', 300 );
		Database::getInstance ()->sql_query ( "DELETE FROM emb_market_tempdata" );
		
		foreach ( $this->regions as $regionID ) {
			$y = 1;
			for($x = 1; $x <= $y; $x ++) {
				$data = EvECrest::getInstance ()->getMarketHistoryData ( $x, $regionID );
				$y = intval ( $data->pageCount );
				$this->prepareTempDB ( $data, $regionID, $x );
				unset ( $data );
			}
		}
		
		$this->parseMarketHistoryData ();
	}
	
	private function prepareTempDB($data, $regionID, $page) {
		$sql = "INSERT INTO emb_market_tempdata (typeID, locationID, price, volume, volumeEntered, stationID, buy, insTimestamp, pageNr)
				VALUES";
		$count = 0;
		foreach ( $data->items as $objData ) {
			if ($count > 0)
				$sql .= ", ";
			$sql .= "
			 (" . $objData->type . ", $regionID, " . $objData->price . ", " . $objData->volume . ", " . $objData->volumeEntered . ", " . $objData->stationID . ", " . intval ( $objData->buy ) . ", " . time () . ", " . $page . ")";
			$count ++;
		}
		
		unset ( $objData );
		unset ( $data );
		Database::getInstance ()->sql_query ( $sql );
	}
	
	private $currentData;
	
	/**
	 * Load the DB into array with all registered items.
	 */
	public function getRegisteredList() {
		if (empty ( $this->currentData )) {
			$res = Database::getInstance ()->sql_query ( "SELECT * FROM emb_market_reg_items" );
			while ( $row = Database::getInstance ()->sql_fetch_array ( $res ) ) {
				$this->currentData [$row ['typeID']] = $row;
			}
		}
		return $this->currentData;
	}
	
	/**
	 * In development not used yet
	 * @param unknown $systemID
	 */
	public function loadSystemWideMarketData($systemID) {
		$regList = $this->getRegisteredList();

		if(empty($this->sysWideMarketData[$systemID])) {
			$result = array();
			$SQL = "SELECT invgroups.groupName, invgroups.groupID, t1.stationID, invtypes.typeName, 
				t1.typeID, 
				(sum(t1.price*t1.volume)/sum(t1.volume)) as avgSellPrice, 
				max(t1.price) as mPrice, min(t1.price) as lowPrice, 
				t1.buy, 
				sum(t1.volume) as totalVolume, 
				sum(t1.volume * t1.price) / sum(t1.volume) AS normalizedAverage, 
				count(t1.typeID) as orderCount,
				t1.insTimestamp as updateTime
			FROM emb_market_detaildata AS t1
			LEFT JOIN invtypes ON invtypes.typeID = t1.typeID
			LEFT JOIN invgroups ON invtypes.groupID = invgroups.groupID
			INNER JOIN emb_locations ON t1.stationID = emb_locations.locationID and emb_locations.solarSystemID = $systemID
			WHERE ";
			
			$c=0;
			foreach($regList as $tx) {
				
				
				if($c>0)
				$SQL .= " OR";
					$SQL .= " t1.typeID = ".$tx['typeID']."";
				$c++;
			}
			$SQL .= " GROUP BY t1.typeID, t1.buy";
	
			if($c == 0)
				return array();
			$res = Database::getInstance()->sql_query($SQL);
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$result[$row['buy']][$row['typeID']] = $row;
			}
			return $this->sysWideMarketData[$systemID] = $result;
		} else {
			return $this->sysWideMarketData[$systemID];
		}
	}
	
	private $sysWideMarketData = array();
	
	
	
	
	
	private $tempMarketData;

	
	/**
	 * NEW Version
	 * @param unknown $typeId
	 * @param unknown $region_Id
	 */
	private function loadDailyMarketData_IntoDBSpec($typeId, $region_Id) {
		$avg_prices = EvECrest::getInstance()->getMarketAveragesHistoryOfType_and_Region($typeId, $region_Id);
		
		$storage_time = 24*60*60*91; // Three Month in seconds
		if($avg_prices->totalCount > 90) {
			$delete_data = "DELETE FROM emb_market_historydata WHERE typeID = $typeId AND regionID = $region_Id";
			Database::getInstance ()->sql_query ($delete_data);
		}
			
		$sql = "INSERT INTO emb_market_historydata (typeID,
																regionID,
																sellAvg,
																buyAvg,
																volume,
																timestamp_update,
																timestamp_eveavg, highPrice, lowPrice)
															VALUES ";
			
		$count = 0;
		for($x = 0; $x < $avg_prices->totalCount; $x ++) {
			$mvalue = $avg_prices->items [$x];
		
			if(!empty($mvalue['date'])) {
				$unix_time = strtotime($mvalue['date']);
				if($unix_time > time()-$storage_time) {
					if($count > 0) {
						$sql .= ",";
					}
					$count++;
					$sql .= "($typeId, $region_Id, 0,0,".$mvalue->offsetGet('volume').", ".time().", ".intval($unix_time).",".$mvalue->offsetGet('highPrice').",".$mvalue->offsetGet('lowPrice').")";
				}
			}
		}
		Database::getInstance ()->sql_query ($sql);
		Database::getInstance()->sql_query("UPDATE emb_market_reg_items SET last_historyUpdate = ".time()." WHERE typeID = $typeId");
	}
	
	/**
	 * load data into db incrementally / Execute this function only daily
	 * Restrict the Function timely to not disturb the CRON Process or loose data
	 */
	public function loadDailyMarketData_IntoDB() {
		
		$reg_items = $this->getRegisteredList();
		
		$region_Id = 10000002;
		
		$microtime = microtime(true);
		foreach($reg_items as $rItem) {
			if($rItem['last_historyUpdate'] < time()-86399) {
				if((microtime(true)-$microtime) < 30000) {
					$this->loadDailyMarketData_IntoDBSpec($rItem['typeID'], $rItem['locationID']);
				} else {
					return false;
				}
			}
		}
		return true;
	}
	
	
	private function parseMarketHistoryArray() {
		foreach ( $this->tempMarketData as $regionID => $region ) {
			foreach ( $region as $typeID => $type ) {
				$buyMean = MarketPrices::harmonic_mean ( $value_arr ['buyPriceRaw'] );
				$buyAvg = (array_sum ( $value_arr ['buyPriceRaw'] ) / count ( $value_arr ['buyPriceRaw'] ));
			}
		}
	}
	
	/**
	 *
	 * @param Integer $typeId        	
	 */
	public function addItem($typeId, $location = 10000002) {
		if (empty ( $this->regItems [$itemId] [$location] )) {
			$data = $this->getMarketAverage ( $typeId );
			$sql = "INSERT INTO emb_market_reg_items (typeID, avgPrice, adjustedPrice, last_update, locationID, manually_added)
			VALUES ('$typeId','" . $data ['avgPrice'] . "','" . $data ['averagePrice'] . "','" . time () . "','" . $location . "', 1)";
			
			Database::getInstance ()->sql_query ( $sql );
			$this->regItems [$itemId] [$location] = $itemCache;
		}
	}
	private $marketTempData = array ();
	public function getMarketAverage($typeId) {
		if (empty ( $this->marketTempData )) {
			$mvalue = EvECrest::getInstance ()->getMarketPrices ();
		}
		for($x = 0; $x < $marketPrices->totalCount; $x ++) {
			$this->marketTempData [$mvalue->offsetGet ( 'type' )->__get ( 'id' )] = array (
					'adjustedPrice' => $mvalue->offsetGet ( 'adjustedPrice' ),
					'avgPrice' => $mvalue->offsetGet ( 'averagePrice' ) 
			);
		}
		return $this->marketTempData [$typeId];
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
		$sql = "SELECT emb_market_reg_items.*,invtypes.groupID, invtypes.typeName, invgroups.* FROM emb_market_reg_items
				LEFT JOIN invtypes ON emb_market_reg_items.typeID = invtypes.typeID
				LEFT JOIN invgroups ON invtypes.groupID = invgroups.groupID";
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
	 * Automatically Update Marketprices from a specific location
	 *
	 *
	 * @param Integer $itemId        	
	 * @param Integer $location        	
	 * @param bool $update_fromcrest
	 *        	// Force update from CREST and recalculate
	 * @return Array
	 */
	public function deprecated_getMarketAverages($itemId, $location = 10000002, $update_fromcrest = false) {
		global $cache;
		
		$cache_name = "market_items_history_" . $itemId . "_" . $location . "";
		
		if (! empty ( $this->regItems [$itemId] [$location] ) && ! $update_fromcrest) {
			return $this->regItems [$itemId] [$location];
		} else {
			
			/**
			 * $url = "https://public-crest.eveonline.com/market/$location/types/$itemId/history/";
			 * $url = "https://crest-tq.eveonline.com/market/$location/orders/all/";
			 * //crest-tq.eveonline.com
			 * $marketInformation = Perry::fromUrl($url);
			 * print_r($marketInformation);
			 */
			
			$c = 0; // Count how many elements
			$value_arr = array ();
			foreach ( $marketInformation->items as $key => $element ) {
				$timestamp = strtotime ( $element->date );
				// Only parse the last time / timerange of the data
				if ($timestamp >= time () - $this->timeRange) {
					$value_arr ['avgPrice'] [] = $element->avgPrice;
					$value_arr ['highPrice'] [] = $element->highPrice;
					$value_arr ['lowPrice'] [] = $element->lowPrice;
					$value_arr ['orderCount'] [] = $element->orderCount_st;
				}
			}
			
			if (! empty ( $value_arr )) {
				$itemCache ['avgPrice'] = MarketPrices::harmonic_mean ( $value_arr ['avgPrice'] );
				$itemCache ['highPrice'] = MarketPrices::harmonic_mean ( $value_arr ['highPrice'] );
				$itemCache ['lowPrice'] = MarketPrices::harmonic_mean ( $value_arr ['lowPrice'] );
				$itemCache ['orderCount'] = MarketPrices::harmonic_mean ( $value_arr ['orderCount'] );
			} else {
			}
		}
		
		if (empty ( $this->regItems [$itemId] [$location] )) {
			$sql = "INSERT INTO emb_market_reg_items (typeID, avgPrice, highPrice, lowPrice, last_update, orderCount, locationID, manually_added)
									VALUES ('$itemId','" . $itemCache ['avgPrice'] . "','" . $itemCache ['highPrice'] . "','" . $itemCache ['lowPrice'] . "','" . time () . "','" . $itemCache ['orderCount'] . "','" . $location . "', " . $this->manually_added . ")";
			Database::getInstance ()->sql_query ( $sql );
			
			$this->regItems [$itemId] [$location] = $itemCache;
		} else {
			$sql = "UPDATE emb_market_reg_items SET avgPrice = '" . $itemCache ['avgPrice'] . "',
					highPrice = '" . $itemCache ['highPrice'] . "',
					lowPrice = '" . $itemCache ['lowPrice'] . "',
					last_update = '" . time () . "',
					orderCount = '" . $itemCache ['orderCount'] . "',
					currDailyAvg = '" . $value_arr ['avgPrice'] [count ( $value_arr ['avgPrice'] ) - 1] . "',
					currDailySell = '" . $value_arr ['highPrice'] [count ( $value_arr ['highPrice'] ) - 1] . "'
					WHERE typeID = '" . $itemId . "' AND locationID = '" . $location . "'
					";
			Database::getInstance ()->sql_query ( $sql );
		}
		
		// Reset to default for manually added 'flag'
		$this->setManuallyAdd ( false );
		return $itemCache;
	}
	
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
		global $cache;
		$cache_name = "emb_industry_index";
		$itemCache = $cache->get ( $cache_name );
		if (empty ( $itemCache )) {
			$url = "https://public-crest.eveonline.com/industry/systems/";
			$industryIndex = Perry::fromUrl ( $url );
			$itemCache = array ();
			foreach ( $industryIndex->items as $item ) {
				foreach ( $item ['systemCostIndices'] as $indices ) {
					$array [$indices->activityID] = $indices->costIndex;
				}
				$itemCache [$item ['solarSystem']->id] = $array;
			}
			$cache->put ( $cache_name, $itemCache, $this->cacheTime );
		}
		return $itemCache [$system] [$activity];
	}
	public function getSolarSystemInformation() {
		$url = "https://public-crest.eveonline.com/solarsystems/30011392/";
		$solarSystemInfo = Perry::fromUrl ( $url );
	}
	
	/**
	 * Parse all current market data
	 */
	public function getMarketAvgPrice($typeID) {
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

