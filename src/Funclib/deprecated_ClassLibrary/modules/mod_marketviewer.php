<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;
session_start();
use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvEESI;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\GlobalVar;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EveStaticData;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;

class mod_marketviewer extends module_abstract {
	
	public function __construct($args=array())
	{
	 
	}
	
	
	public function getModuleId() { return 1; }
	
	public function mainOutput($template)
	{
		
		global $user, $request;
		GlobalVar::gI();
		$q = EveStaticData::gI();
		
		$regions = EvEStaticData::gI()->getRegions();
		$selected_region = $this->getStandardSelectedValue('emb_region_id', 'emb_sess_region_id', 10000047);
		
		$admin = module_handler::getInstance()->hasWriteAccess($user->data['user_id'], 5);
		if($admin) {
			$sql = "SELECT * FROM emb_update_market_list";
			$res = Database::getInstance()->sql_query($sql);
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$update_region[$row['regionID']] = array('update_region' => $row['update_region'], 'reference_region' => $row['reference_region']);
			}
			
			$refresh_reg_genname = "update_period_".$selected_region;
			if($request->variable($refresh_reg_genname, 0) != 0) {
				$req_var = $request->variable($refresh_reg_genname, 0);
				if(intval($req_var) != 2)
					$update_value = 0;
				else
					$update_value = 1;
			
				if(empty($update_region[$selected_region]))
					$sql = "INSERT INTO emb_update_market_list (RegionID, update_region) VALUES ('$selected_region', '$update_value')";
				else
					$sql = "UPDATE emb_update_market_list SET update_region = '$update_value' WHERE RegionID = $selected_region";
				Database::getInstance()->sql_query($sql);
				$update_region[$selected_region]['update_region'] = $update_value;
				if($request->variable("reference_region_".$selected_region, 0) != 0) {
					$sql = "UPDATE emb_update_market_list SET reference_region = 0; UPDATE emb_update_market_list SET reference_region = 1 WHERE regionID = $selected_region";
					Database::getInstance()->sql_query($sql);
				}
			}

			$template->assign_var('REGION_NAME', $regions[$selected_region]['name']);
			$update_region_bool = !empty($update_region[$selected_region]) && $update_region[$selected_region]['update_region'];
			$template->assign_var('REGION_ID', $selected_region);
			$template->assign_var('VAL_1_SELECTED', $update_region_bool?"CHECKED":"");
			$template->assign_var('VAL_0_SELECTED', !$update_region_bool?"CHECKED":"");
			
			$template->assign_var('VAL_REFREG_SELECTED', $update_region[$selected_region]['reference_region']==1?"CHECKED":"");
			
			$template->assign_var('user_admin', $admin?1:0);
		}
		
		// Ask Region List
		foreach($regions as $region) {
			if($region['regionID'] == $selected_region) {
				$selected = " selected"; 
			} else {
				$selected = "";
			}
			$template->assign_block_vars('select_region', 
					array('REGION_ID' => $region['regionID'],
							'REGION_NAME' => $region['name'],
							'REGION_SELECTED' => $selected,
			));
		}

		$region = EvEStaticData::gI()->getSystems(0, $selected_region);
		$selected_system = $this->getStandardSelectedValue('emb_system_id', 'emb_sess_system_id', 30003751);

		foreach($region[$selected_region] as $system) {
			if($system['systemID'] == $selected_system) {
				$template->assign_var('SYSTEM_NAME', $system['sysName']);
				$selected = " selected";
			} else {
				$selected = "";
			}
			$template->assign_block_vars('select_system',
					array('SYSTEM_ID' => $system['systemID'],
							'SYSTEM_NAME' => $system['sysName'],
							'SYSTEM_SELECTED' => $selected,
							'SYSTEM_SECST' => number_format($system['securityStatus'], 2),
					));
		}
		
		$priceList = MarketPrices::getInstance()->loadSystemWideMarketData($selected_system);

		if(empty($priceList[0])) {
			$template->assign_var('empty_sellorder', 1);
		} else
		{ foreach($priceList[0] as $typeID => $typeItem) {
				$template->assign_block_vars('itemlist_summary',
						array('ITEM_ID' => $typeID,
								'ITEM_NAME' => $typeItem['typeName'],
								'LOW_PRICE' => number_format($typeItem['lowPrice'], 2),
								'HIGH_PRICE' => number_format($typeItem['mPrice'], 2),
								'AVG_PRICE' => number_format($typeItem['avgSellPrice'], 2),
								'NORM_AVG' => number_format($typeItem['normalizedAverage'], 2),
								'VOLUME' => number_format($typeItem['totalVolume'], 0),
								'AGE' => $this->getTimeOrDateFormat($typeItem['updateTime']),
								'ORDERCOUNT' => number_format($typeItem['orderCount'], 0),
								'access_to_mod6' => 1,
								'SYS_ID' => $selected_system
						));
				}
		}
		
		if(empty($priceList[1])) {
			$template->assign_var('empty_buyorder', 1);
		} else {
			foreach($priceList[1] as $typeID => $typeItem) {
					$template->assign_block_vars('itemlist_summary_buy',
							array('ITEM_ID' => $typeID,
									'ITEM_NAME' => $typeItem['typeName'],
									'LOW_PRICE' => number_format($typeItem['lowPrice'], 2),
									'HIGH_PRICE' => number_format($typeItem['mPrice'], 2),
									'AVG_PRICE' => number_format($typeItem['avgSellPrice'], 2),
									'NORM_AVG' => number_format($typeItem['normalizedAverage'], 2),
									'VOLUME' => number_format($typeItem['volume'], 0),
									'AGE' => $this->getTimeOrDateFormat($typeItem['updateTime']),
									'ORDERCOUNT' => number_format($typeItem['orderCount'], 0),
									'access_to_mod6' => 1,
									'SYS_ID' => $selected_system
							));
			}
		}
		
	}
	
	private function getTimeOrDateFormat($timestamp) {
		if((int)date("d", $timestamp) - (int)date("d", time()) == 0
		&& (time() - (int)$timestamp) < 84600)
			{
				if(time() - $timestamp < 60) {
					$result = "Less one hour";
				} else {
					$dateStamp = time() - $timestamp;
					$result = date("H", $dateStamp)."h ".date("i",$dateStamp)."m";
				}
				return $result;
		} else {
			return date("c", $timestamp);
		}
	}
	
	private function getStandardSelectedValue($form_name, $session_name, $standard=0) {
		return GlobalVar::gI()->getStandardSelectedValue($form_name, $session_name, $standard);
	}
	
	public function shortOutput()
	{
	
	}
	
	public function hasAccess()
	{
	
	}
	
	/**
	 * Inherited function; Return a single array with names to exclude in the Main-Navigation Elements
	 */
	public function urlExcludeArr() {
		return array();
	}
	
}