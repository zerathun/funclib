<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EveStaticData;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvECrest;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\GlobalVar;

class mod_marketstockpile extends module_abstract {
	
	public function __construct($args=array())
	{
	
	}
	
	public function getModuleId() { return 1; }
	
	public function mainOutput($template)
	{
		global $request;
		$mp = MarketPrices::getInstance();
	
		$selected_system = GlobalVar::gI()->getStandardSelectedValue('emb_system_id', 'emb_market_edt_item_sel_sys_stockpile', 30003751);
		$selected_region = GlobalVar::gI()->getStandardSelectedValue('emb_region_id', 'emb_market_edt_item_sel_region_stockpile', 10000047);
		$regions = EvEStaticData::gI()->getRegions();
		
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
		
		foreach($region[$selected_region] as $system) {
			if($system['systemID'] == $selected_system) {
				$template->assign_var('SYSTEM_NAME', $system['sysName']);
				$selected = " selected";
			} else {
				$selected = "";
			}
			$template->assign_block_vars('select_system',
					array('SYSTEM_ID' => $system['systemID'],
							'SYSTEM_NAMES' => $system['sysName'],
							'SYSTEM_SELECTED' => $selected,
							'SYSTEM_SECST' => number_format($system['securityStatus'], 2),
					));
		}
		

		$sql = "
SELECT
    T1.typeID,
    T1.showReferencePage,
    T1.registered,
    T2.systemID,
    T2.use_contingent,
    T2.contingent,
    T2.priority,
    T2.margin,
    T3.totalVolume,
    T3.mPrice,
    T3.lowPrice,
    T3.normalizedAverage,
    T3.orderCount,
    T3.avgSellprice,
    T2.regionID,
    T4.typeName,
    T5.groupID,
    T5.groupName,
    T6.mPrice as refHighestPrice,
    T6.lowPrice as refLowestPrice,
    T6.normalizedAverage as refAvg,
	itemAvgs.avgHighPrice AS referencePrice,
    round(((T3.totalVolume/T2.contingent))*100,2) AS contingentReached,
    round(((T3.totalVolume/T2.contingent))*100,2)/priority as weightedContingentReached
FROM
    emb_market_reg_items AS T1
        LEFT JOIN
    emb_market_reg_items_settings AS T2 ON T1.typeID = T2.typeID
        LEFT JOIN
    emb_market_reg_items_averages AS itemAvgs ON T1.typeID = itemAvgs.typeID
        LEFT JOIN
    emb_market_tempmarketdata AS T3 ON T3.typeID = T1.typeID AND T3.buy = 0
        AND T2.systemID = T3.systemID
        LEFT JOIN
    evestatic_invtypes AS T4 ON T1.typeID = T4.typeID
        LEFT JOIN
    evestatic_invgroups AS T5 ON T4.groupID = T5.groupID
		LEFT JOIN
	emb_market_tempmarketdata AS T6 ON T1.typeID = T6.typeID and T6.systemID = 30000142 AND T6.buy = 0
WHERE
    T2.use_contingent = 1
        AND T2.systemID = ".$selected_system."
        AND (T3.totalVolume < T2.contingent
        OR T3.totalVolume IS NULL
        OR T3.totalVolume = 0
        OR T3.lowPrice > (1+T2.margin)*T6.normalizedAverage)
        AND T2.contingent > 0
ORDER BY weightedContingentReached ASC, T2.priority DESC;
				";
		
		$res = Database::getInstance()->sql_query($sql);
		
		while($row = Database::getInstance()->sql_fetch_array($res)) {
			$priority = $row['priority'];
			if($priority > 3) $priority = 3; elseif($priority < 1) $priority = 1; else $priority = intval($priority);
			if($priority == 1) {
				$pr = "Low";
			} elseif ($priority == 2) {
				$pr = "Medium";
			} elseif($priority == 3){
				$pr = "High";
			} else {
				$pr = "Low";
			}
			
			$currTx_margin = $row['lowPrice']>0?number_format(((1-$row['referencePrice']/$row['lowPrice'])*100),2)."%":"n.a.";
			$curr_margin = $row['lowPrice']>0?(1-$row['referencePrice']/$row['lowPrice'])*100:100;
			if($curr_margin+$row['margin'] < $row['margin']) {
				$margin_color = 'rlygreenfat';
			}
			elseif($curr_margin < $row['margin']) {
				$margin_color = 'redfat';
			} elseif($curr_margin > $row['margin']) {
				$margin_color = 'greenfat';
			} else {
				$margin_color = '';
			}
			
			$price_proposal = (1+($row['margin']/100))*$row['referencePrice'];
			
			$template->assign_block_vars('stockpile',
					array('ITEM_NAME' => $row['typeName'],
							'TYPE_ID' => $row['typeID'],
							'SYS_ID' => $row['systemID'],
							'CONTINGENT' => "<b>".number_format($row['contingent']-$row['totalVolume'],2)."</b> (".number_format($row['contingent'],0).")",
							'TOTAL_VOLUME' => number_format($row['totalVolume'],0),
							'LOW_PRICE' => number_format($row['lowPrice'],2),
							'REF_LOW_PRICE' => number_format($row['referencePrice'],2),
							'PRICE_PROPOSAL' => number_format($price_proposal,2),
							'CURRENT_MARGIN' => $currTx_margin,
							'PRIORITY' => $pr,
							'MARGIN_CLASS' => $margin_color,
					));
		}
		

		
		return "";
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