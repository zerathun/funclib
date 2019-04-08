<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;

class mod_marketprices extends module_abstract {
	
	public function __construct($args=array())
	{
	
	}
	
	public function getModuleId() { return 1; }
	
	public function mainOutput($template)
	{
		global $user, $template;
		$mp = MarketPrices::getInstance();		
		// Get Reference system ID
		$systemID = 30000142;
		$template->assign_var('REF_REGIONNAME', 'Jita');
		
		$items = $mp->loadSystemWideMarketData($systemID);

		$new_arr = array ();
		// Sort by group
		if(!empty($items[0])) {
			foreach ( $items[0] as $id => $item ) {
				$grp_id = $item ['groupID'];
				if($item['registered']==1 && $item['showReferencePage']) {
					$new_arr [$grp_id] [$id] = $item;
				}
			}
		}
		
		$regList = $mp->getRegisteredList();

		ksort ( $new_arr );
		foreach ( $new_arr as $group_id => $grouped ) {
			$keys = array_keys($grouped);
			$group_name = $grouped[$keys[0]]['groupName'];
			$template->assign_block_vars('grouplist', array('GROUP_NAME' => $group_name));
			
			foreach($grouped as $typeID => $item) {
				$highestbuyprice = !empty($items[1][$typeID]['mPrice']) ? number_format($items[1][$typeID]['mPrice'], 2) : 0;
				$avgbuyprice = !empty($items[1][$typeID]['avgSellPrice']) ? number_format($items[1][$typeID]['avgSellPrice'], 2) : 0;
				
				$percentAvgLow = (1-($regList[$typeID]['avgLowPrice']/$items[1][$typeID]['mPrice']))*100;
				$percentAvgHigh = (1-($regList[$typeID]['avgHighPrice']/$item['lowPrice']))*100;
				$PAHColor = $percentAvgHigh >= 0 ? "119911" : "FF0000";
				$PALColor = $percentAvgHigh >= 0 ? "119911" : "FF0000";

				$template->assign_block_vars('grouplist.minerallist', array('REGION_ID' => $item['id'], 
						'REGION_NAME' => $item['name'],
						'ITEM_NAME' => $item['typeName'],
						'ITEM_ID' => $item['typeID'],
						'SELL_PRICE' => number_format($item['lowPrice'], 2),
						'AVG_PRICE' => number_format($item['avgSellPrice'], 2),
						'HIGHESTBUY_PRICE' => $highestbuyprice	,
						'AVGBUY_PRICE' => $avgbuyprice,
						'LOWPRICE_PERCENT' => number_format($percentAvgLow, 2),
						'HIGHPRICE_PERCENT' => number_format($percentAvgHigh, 2),
						'THREEMONTH_SELL' => number_format($regList[$typeID]['avgHighPrice'], 2),
						'THREEMONTH_BUY' => number_format($regList[$typeID]['avgLowPrice'], 2),
						'LOWPRICE_CHANGE_COLOR' => $PALColor,
						'HIGHPRICE_CHANGE_COLOR' => $PAHColor,
						'access_to_mod6' => module_handler::getInstance()->hasAccess($user->id, 6),
						'SYS_ID' => $systemID
				));
			}
		}
		return "";
	}
	
	/**
	<tr>
		<td><img class="title-img" src="https://image.eveonline.com/Type/{grouplist.minerallist.ITEM_ID}_32.png">{grouplist.minerallist.ITEM_NAME}</td>
		<td>{grouplist.minerallist.ADJUSTED_AVG}</td>
		<td><b>{grouplist.minerallist.AVG_PRICE}</b></td>
		<td style="text-align: right"><!-- IF grouplist.minerallist.SHOW_DELETE --><a href="{grouplist.minerallist.HREF}" title="Delete" class="button icon-button delete-icon"></a><!-- ENDIF --></td>
	</tr>
	
	 [typeID] => 34 [avgPrice] => 5.75 [highPrice] => 0 [lowPrice] => 0 [last_update] => 1468239560 [orderCount] => 0 [locationID] => 10000002 [manually_added] => 1 [currDailyAvg] => 0 [currDailySell] => 0 [adjustedPrice] => 4.82 [groupID] => 18 [categoryID] => 4 [groupName] => Mineral [description] => [iconID] => 22 [useBasePrice] => 1 [allowManufacture] => 1 [allowRecycler] => 1 [anchored] => 0 [anchorable] => 0 [fittableNonSingleton] => 0 [published] => 1 )
	
	**/
	
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