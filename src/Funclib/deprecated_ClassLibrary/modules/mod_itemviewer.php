<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\MarketPrices;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EveStaticData;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvECrest;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\GlobalVar;

class mod_itemviewer extends module_abstract {
	
	public function __construct($args=array())
	{
	
	}
	
	public function getModuleId() { return 1; }
	
	public function mainOutput($template)
	{
		global $request, $user;
		$mp = MarketPrices::getInstance();

		EveStaticData::getInstance()->updateInvTypes();
		
		$selected_system = GlobalVar::gI()->getStandardSelectedValue('emb_system_id', 'emb_market_edt_item_sel_sys', 30003751);
		
		$selected_region = GlobalVar::gI()->getStandardSelectedValue('emb_region_id', 'emb_market_edt_item_sel_region', 10000047);

		//$selectedItem = $request->variable('item', 0);
		
		
	 /*
		$items = $mp->loadSystemWideMarketData($location_id);
		$new_arr = array ();
		// Sort by group
		if(!empty($items[0])) {
			foreach ( $items[0] as $id => $item ) {
				$grp_id = $item ['groupID'];
				$new_arr [$grp_id] [$id] = $item;
			}
		}
		
		$regList = $mp->getRegisteredList();
		//Array ( [34] => Array ( [typeID] => 34 [avgHighPrice] => 6.05689 [dailyHighPrice] => 6.26 [dailyLowPrice] => 6 [last_update] => 1476084183 [orderCount] => 1826 [regionID] => 10000002 [manually_added] => 1 [avgLowPrice] => 5.59978 [last_historyUpdate] => 1477769162 ) 
		
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
				));
			}
		}
		
		*/
		$admin = module_handler::getInstance()->hasWriteAccess($user->data['user_id'], 6);
		if($admin) {
			$template->assign_var('admin_mode', 1);
			// Prepare the standard list for the 'stockings' {
			
			$sysID_GET = $request->variable('sys_id',0);
			if($sysID_GET != 0 && $request->variable('emb_system_id',0) == 0 && $request->variable('emb_region_id',0) == 0) {
				$sql = "SELECT * FROM evestatic_systems WHERE systemID = ".intval($sysID_GET);
				$res = Database::getInstance()->sql_query($sql);
				$rD = Database::getInstance()->sql_fetch_array($res);
				$selected_region = $rD['parentRegionID'];
				$selected_system = intval($sysID_GET);
			}
			
			$regions = EvEStaticData::gI()->getRegions();
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
			
			// Prepare system dropdown
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
								'SYSTEM_NAME' => $system['sysName'],
								'SYSTEM_SELECTED' => $selected,
								'SYSTEM_SECST' => number_format($system['securityStatus'], 2),
						));
			}
			
			/** Show Market Groups of Items that can be added */		
			$groups = EvECrest::gI()->getMarketGroups();
			$override_post_session = false;
			$selArr = array();
			// Preselect if someone has used GET but not Updated the current
			if($request->variable("slist_0",0)==0 && $request->variable('item',0) != 0) {
				$sql = "";
				$currentTypeID = $request->variable('item',0);
				$select_arr = array();
				$mrktType = EvECrest::gI()->getMarketType($currentTypeID);
				$x =0;
				$searchGrpId = $mrktType->marketGroup->id;
				do {
					$pointer=null;
					foreach($groups->items as $grp) {
						if($grp->id == $searchGrpId) {
							$select_arr[$x] = $grp->id;
							$searchGrpId = $grp->parentGroup->id;
							$pointer=$grp;
							$x++;
						}
					}
				} while(!empty($pointer->parentGroup) && $x<8);
				$selArr = array_reverse($select_arr);
				$selArr[] = $currentTypeID;
				$override_post_session = true;
			}

			$c = 0;
			do {
				$template->assign_block_vars('slist', array('INPUTNAME' => $c));
				if(!$override_post_session) {
					$selArr[$c] = GlobalVar::gI()->getStandardSelectedValue("slist_$c", "slist_$c",0);
				}
				
				if($displayItems && !empty($this->selItem[$c-1]->types->href)) {
					$crest = EvECrest::gI()->getHref($this->selItem[$c-1]->types->href);
					$checkSubgroupFlag = $displayItems = false;
					$checkSubgroupFlag = false;
					
					foreach($crest->items as $typeItems) {
						if($selArr[$c]==$typeItems->type->id) {
							///$selectedItemId = $item['id'];
							$selected = " SELECTED";
							$selectedTypeId = $selArr[$c];
							$selected_Type = $typeItems;
						} else {
							$selected = "";
						}
						$selected = $selArr[$c]==$typeItems->type->id?" SELECTED":"";
						
						$template->assign_block_vars('slist.option',
								array(
										'NAME' => $typeItems->type->name,
										'SELECTED' =>$selected,
										'ID' => $typeItems->type->id,
								));
					}
				} else {
					$checkSubgroupFlag = true;
					$foreachGroup = $groups->items;	
					$none_selected = false;
					foreach($foreachGroup as $item) {
						if((empty($item->parentGroup) && $c==0) || $this->selItem[$c-1]->id == $item->parentGroup->id) {
							if($selArr[$c]==$item['id']){
								$this->selItem[$c] = $tmp1 = $item;
								$none_selected = true;
								$selected = " SELECTED";
							} else {
								$selected = "";
							}
							$template->assign_block_vars('slist.option',
									array(
											'NAME' => $item['name'],
											'SELECTED' =>$selected,
											'ID' => $item['id'],
									));
						}
					}
				}
				
				$modifier = false;
				if($checkSubgroupFlag) {
					foreach($groups->items as $checkChild) {
						if(!empty($checkChild->parentGroup->id)) {
							
							if($checkChild->parentGroup->id == $this->selItem[$c]->id) {
								$modifier = true;
								$countFlag = $c;
							}
						}
					}
				}
				if($modifier == false && $checkSubgroupFlag) {
					$displayItems = true;
				} else {
					$displayItems = false;
				}
				$c++;
			} while (($modifier&&$checkSubgroupFlag || $displayItems&&$none_selected) && $c < 7);


			/** Display the Form and the Market List */
			if(!empty($selected_Type->type)) {
				$regList = array();
				$res = Database::getInstance ()->sql_query ( "SELECT * FROM emb_market_reg_items" );
				
				while ( $row = Database::getInstance ()->sql_fetch_array ( $res ) ) {
					$regList [$row ['typeID']] = $row;
				}
				
				$sql = "SELECT * FROM emb_market_reg_items_settings WHERE typeID = '".$selected_Type->type->id."' AND systemID = $selected_system";
				$res = Database::getInstance()->sql_query($sql);
				while($row = Database::getInstance()->sql_fetch_array($res)) {
					$detailSetting[$row['typeID']][$row['regionID']][$row['systemID']] = $row;
				}
				$tId = $selected_Type->type->id;
				$rId = $selected_region;
				$sId = $selected_system;
				$template->assign_var('REF_TYPE_ID', $selected_Type->type->id);
				$template->assign_var('REF_TYPE_NAME', $selected_Type->type->name);
				$template->assign_var('REGION_ID', $rId);
				$template->assign_var('SYSTEM_ID', $sId);
				$formNameModifier = $tId."_".$rId."_".$sId;
				$template->assign_var('FORMNAME_MODIFIER', $formNameModifier);
				$template->assign_var('FORMNAME_MODIFIER_REFREG', $selected_Type->type->id);
				
				if($request->variable("check_if_form_sent_".$selected_Type->type->id,0) != 0) {
					$formName = "show_reference_".$selected_Type->type->id;
					$chk_ShowRef = $request->variable( $formName,0);//$selected_Type->type->id;
					if($chk_ShowRef != 1) {
						$chk_ShowRef = 0;
					}
					$regList[$selected_Type->type->id]['showReferencePage'] = $showReferencePage = $chk_ShowRef;
					
					$registered = $request->variable("type_is_reg_item_".$selected_Type->type->id, 0);
					if($registered != 1)
						$registered = 0;
					$regList[$selected_Type->type->id]['registered'] = $registered;
				
					if(empty($regList[$selected_Type->type->id]['typeID'])){
						$sql = "INSERT INTO emb_market_reg_items (typeID, last_update, showReferencePage, registered)
								 VALUES (".$selected_Type->type->id.", 0, $showReferencePage, $registered)";
					} else {
						$sql = "UPDATE emb_market_reg_items SET showReferencePage = $showReferencePage, registered = $registered
							WHERE typeID = ".$selected_Type->type->id."";
					}
					Database::getInstance()->sql_query($sql);
					
					$setVarCorrectly = $request->variable('use_insys_type_'.$formNameModifier,-1)==1?1:0;
					if($setVarCorrectly != -1 && $request->variable("check_rId",0) == $selected_region
					&& $request->variable("check_sId",0) == $selected_system) {
						$detailSetting[$tId][$rId][$sId]['use_contingent'] = $uContingent = $setVarCorrectly;
					}
					
					$setVarCorrectly = $request->variable('set_contingent_'.$formNameModifier,-1);
					if($setVarCorrectly != -1) {
						$detailSetting[$tId][$rId][$sId]['contingent'] = $contingent = $setVarCorrectly;
					}
					$setVarCorrectly = intval($request->variable('set_margin_'.$formNameModifier,10));
					if($setVarCorrectly > 200)
						$setVarCorrectly = 200;
					elseif($setVarCorrectly < -100 && $setVarCorrectly != 1200) {
						$setVarCorrectly = -100;
					}
					if($setVarCorrectly != -1200) {
						$detailSetting[$tId][$rId][$sId]['margin'] = $margin = $setVarCorrectly;
					}
					
					$setVarCorrectly = $request->variable('contingent_priority_'.$formNameModifier, -1);
					if($setVarCorrectly != -1) {
						$detailSetting[$tId][$rId][$sId]['priority'] = $priority = $setVarCorrectly;
					}
					
					// Only save system specific settings if also the data from the system has been sent
						if($request->variable("check_rId",0) == $selected_region 
							&& $request->variable("check_sId",0) == $selected_system
							&& $request->variable("use_insys_type_$formNameModifier",0)!= 0) {
						if($uContingent >= 1) {
							$uContingent = 1;
						} else $uContingent = 0;
						if($contingent <0) $contingent = 0; else $contingent = intval($contingent);
						if($priority > 3) $priority = 3; elseif($priority < 1) $priority = 1; else $priority = intval($priority);
						
						
						if(empty($detailSetting[$tId][$rId][$sId]['typeID'])) {
							$sql = "INSERT INTO emb_market_reg_items_settings (typeID, regionID, systemID, use_contingent, contingent, priority, margin)
									 VALUES ($tId, $rId, $sId, $uContingent, $contingent, $priority, ".$detailSetting[$tId][$rId][$sId]['margin'].")";
						} else {
							$sql = "UPDATE emb_market_reg_items_settings SET use_contingent = $uContingent, contingent = $contingent, priority = $priority, margin = ".$detailSetting[$tId][$rId][$sId]['margin']."
							 WHERE typeID = $tId AND regionID = $rId AND systemID = $sId
							";
						}
						Database::getInstance()->sql_query($sql);
					}
				}
				
				$registered = $regList[$selected_Type->type->id]['registered']?true:false;
				$template->assign_var('TYPE_REGISTERED',$registered?" CHECKED":""); // True if the Item is in SYSTEM and Registered
				$template->assign_var('TYPE_UNREGISTERED',!$registered?" CHECKED":"");

				
				if($regList[$selected_Type->type->id]['showReferencePage']) {
					$template->assign_var('REF_TYPE_SELECTED', " CHECKED");
				}

				if(!empty($detailSetting[$tId][$rId][$sId])){
					$template->assign_var("PRIO_".intval($detailSetting[$tId][$rId][$sId]['priority']), " SELECTED");
				}
				$template->assign_var('CONTINGENT_VALUE', !empty($detailSetting[$tId][$rId][$sId]['contingent'])?$detailSetting[$tId][$rId][$sId]['contingent']:"0");
				$template->assign_var('MARGIN_VALUE', !empty($detailSetting[$tId][$rId][$sId]['margin'])?$detailSetting[$tId][$rId][$sId]['margin']:"10");
				
				if($detailSetting[$tId][$rId][$sId]['use_contingent']) {
					$template->assign_var("USE_INSYS_1", " CHECKED");
					$template->assign_var("USE_INSYS_0", "");
				} else {
					$template->assign_var("USE_INSYS_0", " CHECKED");
					$template->assign_var("USE_INSYS_1", "");
				}
			}
				
			
			if(!empty($selectedTypeId)) {
				
			}
			if(!empty($currentTypeObj)) {
				
			}
			$template->assign_var('ADM_FORM_URL', "?mod=6&item=$selectedTypeId");
		}
		

		
		
		if($selected_Type->type->id == 0 && $admin==false) {
			$selectedItem = $request->variable('item',0);
			// Define minimum template with data from Database not to stress with Crest
			if($selectedItem != 0) {
			$sql = "SELECT * FROM evestatic_invtypes WHERE typeID = $selectedItem";
			$row = Database::getInstance()->sql_fetch_array(Database::getInstance()->sql_query($sql));
				$template->assign_var('REF_TYPE_ID', $row['typeID']);
				$template->assign_var('REF_TYPE_NAME', $row['typeName']);
			} else {
				$template->assign_var('REF_TYPE_ID', 37845);
				$template->assign_var('REF_TYPE_NAME', "No item selected");
			}
			$template->assign_var('REGION_ID', $selected_region);
			$template->assign_var('SYSTEM_ID', $selected_system);
		} else {
			$selectedItem = $selected_Type->type->id;
			if($selectedItem == 0) {
				$template->assign_var('REF_TYPE_ID', 37845);
				$template->assign_var('REF_TYPE_NAME', "No item selected");
			}
		}
		
		
		if($selectedItem!=0) {
			$sql = "SELECT t4.avgHighPrice, t4.avgLowPrice, t4.dailyHighPrice, t4.dailyLowPrice,
				t1.*, esS.sysName,
				esS.sov_id, esS.sovereignty_name,
				esS.securityStatus,
				t2.registered,
				t2.showReferencePage
				FROM embin.emb_market_reg_items_settings as t1
				
				LEFT JOIN emb_market_reg_items as t2 ON t1.typeID = t2.typeID
				LEFT JOIN emb_market_reg_items_averages as t4 ON t2.typeID = t4.typeID
				LEFT JOIN evestatic_systems AS esS ON t1.systemID = esS.systemID
				LEFT JOIN emb_market_tempmarketdata AS t10 ON t1.systemID = t10.systemID AND t1.typeID = t10.typeID AND buy=0
				
				WHERE t1.use_contingent = 1 AND t1.typeID = $selectedItem;";

			
			$sql = "	
				SELECT 
				    t1.typeID,
				    t1.showReferencePage,
				    t1.registered,
				    t2.regionID, t2.systemID, t2.use_contingent, t2.contingent, t2.priority, t2.margin,
				    t4.avgHighPrice AS reference_avgHighPrice, t4.avgLowPrice, t4.dailyLowPrice, t4.dailyLowPrice, t4.orderCount,
				    esS.*,
				    t10.*
				FROM
				    emb_market_reg_items AS t1
				        LEFT JOIN
				    emb_market_reg_items_settings AS t2 ON t1.typeID = t2.typeID
				        LEFT JOIN
				    emb_market_reg_items_averages AS t4 ON t2.typeID = t4.typeID
				        LEFT JOIN
				    evestatic_systems AS esS ON t2.systemID = esS.systemID
				        LEFT JOIN
				    emb_market_tempmarketdata AS t10 ON esS.systemID = t10.systemID
				        AND t1.typeID = t10.typeID AND t10.buy = 0
				WHERE
				    t1.typeID = $selectedItem AND t2.use_contingent = 1
					";
			$sql = "
					
					SELECT 
	t1.*,
	t1.typeID as tId,
    t2.*,
    t4.*,
    esS.*,
    esS.systemID as sysId,
    t10.*
FROM
    emb_market_reg_items AS t1
        LEFT JOIN
    emb_market_reg_items_settings AS t2 ON t1.typeID = t2.typeID
        LEFT JOIN
    emb_market_reg_items_averages AS t4 ON t2.typeID = t4.typeID
        LEFT JOIN
    evestatic_systems AS esS ON t2.systemID = esS.systemID
        LEFT JOIN
    emb_market_tempmarketdata AS t10 ON esS.systemID = t10.systemID
        AND t1.typeID = t10.typeID
        AND t10.buy = 0
WHERE
    t1.typeID = $selectedItem
        AND t2.use_contingent = 1
					
					
					";
			
			$res = Database::getInstance()->sql_query($sql);
			$systemsDisplayed = $systemIds = $data = array();
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$systemIds[$row['sysId']] = $row['sysId'];
				$data[] = $row;
			}
						
			foreach($data as $rowItem) {
				$t = $rowItem;
				
				$rcImport = $t['contingent'] - $t['totalVolume'];
				if($rcImport < 0)
					$rcImport = 0;
				$priceProposal = (1+$t['margin']/100)*$t['avgHighPrice'];
				if($t['avgSellPrice'] == 0)
					$avgMarginNr = 100;
				else
					$avgMarginNr = (1-($priceProposal/$t['avgSellPrice']))*100;
				
				$avgMarginColor = (float)$avgMarginNr>0?"fontgreen":"fontred";
				if($avgMarginNr+($avgMarginNr*0.1) > $t['margin'] && $avgMarginNr != 100) {
					$avgMarginColor = "fontred";
					$avgMargin = "<b>".number_format($avgMarginNr,2)."% (!)</b>";
				} elseif(intval($avgMarginNr) == 100) {
					$avgMargin = "n.a.";
				} else {
					$avgMargin = number_format($avgMarginNr,2)."%";
				}
				
				if($t['priority'] == 1) {
					$pr = "Low";
				} elseif ($t['priority'] == 2) {
					$pr = "Medium";
				} elseif($t['priority'] == 3){
					$pr = "High";
				} else {
					$pr = "Low";
				}
				
				if($avgMarginNr < 0) {
					$resellPrice = $t['totalVolume']*$t['avgHighPrice']-$t['totalVolume']*$t['avgSellPrice'];
					$resellPrice = number_format($resellPrice, 2);
				} else {
					$resellPrice = "n.a.";
				}
				if($t['totalVolume'] == 0)
					$relStock = 0;
				else
					$relStock = (($t['totalVolume']/$t['contingent']))*100;
				
				$template->assign_block_vars('mkdt', array(
						'SYSTEM_NAME' => $t['sysName'],
						'SELL_PRICE' => number_format($t['lowPrice'],2),
						'AVG_SELL' => number_format($t['avgSellPrice'],2),
						'TREND_SELL' => $t['t'],
						'IN_STOCK' => number_format($t['totalVolume'],0),
						'TRADE_VOLUME' => 0,
						'CONTINGENT' => number_format($t['contingent'],0),
						'RC_IMPORT' => number_format($rcImport,0),
						'RESELL_PROFIT' => $resellPrice,
						'REF_PRICE' => number_format($t['avgHighPrice'],2),
						'PRICE_PROPOSAL' => number_format($priceProposal,2)." ISK",
						'AVG_MARGIN' => $avgMargin,
						'AVG_MARGIN_COLOR' => $avgMarginColor,
						'PRIORITY' => $pr,
						'RELATIVE_STOCK' => number_format($relStock,2)." %",
						'TYPE_ID' => $t['typeID'],
						'SYS_ID' => $t['systemID']
				));
			}
			
			
			
			/** Display marketdata reference **/
			$cx = 0; $ad_query = "";
			foreach($systemIds as $sysId) {
				if($cx>0)
					$ad_query .= " OR ";
				$ad_query .= "xT.solarSystemID = ".intval($sysId);
				$cx++;
			}
			
			if(count($systemIds) > 0) {
				/*
			$sqlgetMarketDetail = "
				SELECT * FROM emb_market_detaildata AS dd
					INNER JOIN emb_locations AS xT ON dd.stationID = xT.locationID AND ($ad_query)
				WHERE dd.typeID = $selectedItem and BUY = 0 ORDER BY solarSystemID , dd.price
				LIMIT 0 , 50";

			print $sqlgetMarketDetail;
			
			$res = Database::getInstance()->sql_query($sqlgetMarketDetail);
			
			$cOrders = 0;
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				print_r($row);
				$template->assign_block_vars('mkdt_reference', array(
						'STATION_NAME' => htmlspecialchars($row['name']),
						'PRICE' => number_format($row['price'],2),
						'VOLUME' => number_format($row['volume'],0),
						'START_VOLUME' => number_format($row['volumeEntered'])
				));
				$cOrders++;
			}
			
			if($cOrders > 0) {
				$template->assign_var('display_sell_column',1);
			}

			$sqlGEtBuy = "
			SELECT * FROM emb_market_detaildata AS dd
			INNER JOIN emb_locations AS xT ON dd.stationID = xT.locationID AND ($ad_query)
			WHERE dd.typeID = $selectedItem and BUY = 1 ORDER BY solarSystemID , dd.price
			LIMIT 0 , 50";

			$res = Database::getInstance()->sql_query($sqlGEtBuy);
			$cbOrders = 0;
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				if($cbOrders == 0) {
					$template->assign_var('display_buy_column', 1);
				}
				$template->assign_block_vars('mkdt_buy_reference', array(
						'STATION_NAME' => htmlspecialchars($row['name']),
						'PRICE' => number_format($row['price'],2),
						'VOLUME' => number_format($row['volume'],0),
						'START_VOLUME' => number_format($row['volumeEntered'])
				));
				$cbOrders++;
			}
			*/
			if($cOrders == 0 && $cbOrders == 0) {
				$template->assign_var('set_no_items_mkdtref', 1);
			}
			} else {
				$template->assign_var('show_orders', 1);
			}
			
			/*
			print_r(array_keys($systemsDisplayed));
			
			$sysData = $mp->loadSystemWideTempMarketData(array_keys($systemsDisplayed));

			//print_r($sysData);
			
			//print "<br><br>";
			// Prepare data, in case the contingent is set, but no items on the market
			foreach($systemsDisplayed as $sId => $tmpSysData) {
				if(empty($sysData[$sId])) {
					$rr = array(); //$mp->loadSystemWideMarketData($sId);
					if(!empty($rr)) {
						$sysData = array_merge($sysData,$rr);
					} else 	 {				
						$sysData[$sId][0][$selectedItem] = $tmpSysData;
					}
				}
			}
			
			foreach($sysData as $dataTmp) {
				$t = $dataTmp[0][$selectedItem];
				if(!empty($t)) {
					$rcImport = $t['contingent'] - $t['totalVolume'];
					if($rcImport < 0)
						$rcImport = 0;
						
						$priceProposal = (1+$t['margin']/100)*$t['avgHighPrice'];
					
						if($t['avgSellPrice'] == 0)
							$avgMarginNr = 100;
						else
							$avgMarginNr = (1-($priceProposal/$t['avgSellPrice']))*100;
						
						$avgMarginColor = (float)$avgMarginNr>0?"fontgreen":"fontred";

						if($avgMarginNr+($avgMarginNr*0.1) > $t['margin'] && $avgMarginNr != 100) {
							$avgMarginColor = "fontred";
							$avgMargin = "<b>".number_format($avgMarginNr,2)."% (!)</b>";
						} elseif(intval($avgMarginNr) == 100) {
							$avgMargin = "n.a.";
						} else {
							$avgMargin = number_format($avgMarginNr,2)."%";
						}
						
						if($t['priority'] == 1) {
							$pr = "Low";
						} elseif ($t['priority'] == 2) {
							$pr = "Medium";
						} elseif($t['priority'] == 3){
							$pr = "High";
						} else {
							$pr = "Low";
						}
						
						if($avgMarginNr < 0) {
							$resellPrice = $t['totalVolume']*$t['avgHighPrice']-$t['totalVolume']*$t['avgSellPrice'];
							$resellPrice = number_format($resellPrice, 2);
						} else {
							$resellPrice = "n.a.";
						}
						
						
						if($t['totalVolume'] == 0)
							$relStock = 0;
						else
							$relStock = (($t['totalVolume']/$t['contingent']))*100;
							
						$template->assign_block_vars('mkdt', array(
								'SYSTEM_NAME' => $t['sysName'],
								'SELL_PRICE' => number_format($t['lowPrice'],2),
								'AVG_SELL' => number_format($t['avgSellPrice'],2),
								'TREND_SELL' => $t['t'],
								'IN_STOCK' => number_format($t['totalVolume'],0),
								'TRADE_VOLUME' => 0,
								'CONTINGENT' => number_format($t['contingent'],0),
								'RC_IMPORT' => number_format($rcImport,0),
								'RESELL_PROFIT' => $resellPrice,
								'REF_PRICE' => number_format($t['avgHighPrice'],2),
								'PRICE_PROPOSAL' => number_format($priceProposal,2)." ISK",
								'AVG_MARGIN' => $avgMargin,
								'AVG_MARGIN_COLOR' => $avgMarginColor,
								'PRIORITY' => $pr,
								'RELATIVE_STOCK' => number_format($relStock,2)." %",
								'TYPE_ID' => $t['typeID'],
								'SYS_ID' => $t['systemID']
						));
						
						
				} else {
					//print_r($dataTmp[0]);
				}
				
			}*/
		} else {
			print "Not selected item";
		}

		if(sizeof($data) <= 0) {
			$template->assign_var('set_no_items', 1);
		}
		
		return "";
	}
	
	private $selItem;
	private $selItemGrpId;

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