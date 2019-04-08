<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ESI\src\ApiClient;

class EvEESI {
	
	protected static $instance;
	
	private function __construct() {
		//require_once('/var/www/phpBB3/ext/zeradun/api_manager/includes/Ember/ESI/autoload.php');
		//$cli = new ApiClient();
	}

	public static function getInstance() {
		if(empty(EvEESI::$instance)) {
			EvEESI::$instance = new EvEESI();
		}
		return EvEESI::$instance;
	}
	
	/**
	 * WrapperFunction for shorter FName
	 */
	public static function gI() {
		return EvEESI::getInstance();
	}
	
	/**
	 * returns information about industry/systems
	 */
	public function getSysIndustryInfo() {
		return $this->getUrl("industry/systems/");
	}
	
	/**
	 * get general market prices
	 */
	public function getMarketPrices() {
		return $this->getUrl("market/prices/");
	}

	/**
	 * get Market Information from region
	 * @param unknown $region_id
	 */
	public function getMarketData($region_id) {
		return $this->getUrl("market/$region_id/orders/all/");
	}
	
	public function getTypeId($type_id) {
		return $this->getUrl("inventory/types/$type_id/");
	}

	public function getTypeList(){
		return $this->getUrl("inventory/types");
	}
	
	public function getMarketGroups(){
		return $this->getUrl("market/groups/");
	}
	
	public function getMarketGroup($groupID) {
		return $this->getUrl("market/groups/$groupID/");
	}
	
	public function getInventoryGroups($page) {
		$page = intval($page);
		return $this->getUrl("inventory/groups/?page=$page");
	}
	
	public function getInventoryGroup($groupID, $page) {
		$page = intval($page);
		return $this->getUrl("inventory/groups/".$groupID."/?page=$page");
	}
	
	public function getGroupId($group_id) {
		
	}
	
	public function getRegions() {
		return $this->getUrl("regions/");
	}
	
	public function getRegionInfo($region_id) {
		return $this->getUrl("regions/$region_id/");
	}
	
	public function getConstellation($constellation_id) {
		return $this->getUrl("constellations/$constellation_id/");
	}
	
	public function getSolarsystem($solarsystem_id) {
		return $this->getUrl("solarsystems/$solarsystem_id/");
	}
	
	public function getMarketDetailData($region_id, $page=1) {
		$page = intval($page);
		return $this->getUrl("/market/$region_id/orders/all/?page=".$page);
	}
	
	public function getMarketType($typeID) {
		$page = intval($typeID);
		return $this->getUrl("/market/types/$typeID/");
	}
	
	public function getSovereigntyStructures() {
		return $this->getUrl("/sovereignty/structures/");
	}
	
	public function getHref($crest_href) {
		return $this->getUrl($crest_href, false);
	}
	
	public function getLocationDetail($locationID) {
		return $this->getUrl("/universe/locations/$locationID/");
	}
	
	public function getSellOrders($regionID, $typeID) {
		$queryPart = "/market/$regionID/orders/sell/?type=https://crest-tq.eveonline.com/inventory/types/$typeID/";
		return $this->getUrl($queryPart);
	}

	public function getIndustryIndexes() {
		$query = "/industry/systems/";
		//$url = "https://public-crest.eveonline.com/solarsystems/30011392/";
		$sysInfo = $this->getUrl($query);
		return $sysInfo;
	}
	
	
	/**
	 * get a list of items currently seo
	 * @param Integer $typeId
	 * @param Integer $region_id
	 */
	public function getMarketAveragesHistoryOfType_and_Region($typeId, $region_id) {
		$ur = "market/$region_id/history/?type=".$this->crest_url."inventory/types/$typeId/";
		return $this->getUrl($ur);
	}
	
	public function getMarketHistoryData($page=0, $region_id=10000002) {
		if($page > 0) {
			$page_url = "?page=$page";
		} else $page_url = "";
		
		return $this->getUrl("market/$region_id/orders/all/$page_url");
	}
	
	private $cache_name = "request_count_cache";
	
	private function getUrl($relative_crest_path, $relative=true) {
		global $cache;
		$url = $relative?$this->crest_url.$relative_crest_path:$relative_crest_path;

		$loop_counter = 0;
		do {
			if($this->canRequest()) {
				$count = $cache->get($this->cache_name);
				if(empty($count))
					$count = 0;
				$count++;
				$cache->put($this->cache_name, $count, 5);
				$result = Perry::fromUrl($url);
				$request_pending = false;
			} else {
				// Request konnte nicht durchgeführt werden, deshalb warte eine halbe Sekunde
				usleep(200000);
				$request_pending = true;
				$loop_counter++;
			}
			if($loop_counter > $this->loop_counter) {
				throw new \Exception("EvECrest: $url exceeded 60 tries (30sec)");
			}
		} while($request_pending);
		
		return $result;
	}
	
	public function setLoopCounter($counter) {
		$this->loop_counter = $counter;
	}
	
	private $loop_counter = 149;
	
	private function canRequest() {
		global $cache;
		
		if($cache->get($this->cache_name) > 120) {
			return false;
		} else {
			return true;
		}
	}
	
	
}


?>