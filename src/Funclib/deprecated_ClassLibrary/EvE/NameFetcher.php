<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

class NameFetcher {
	
	public static function getItemdetails($typeID, $argument='typeName') {
		global $cache, $PDDB;
		if(intval($typeID) > 0) {
		$cache_name = "emb_items_name_".$typeID;
		$itemdetails = $cache->get($cache_name);
			if(empty($itemdetails)) {
				$sql = "SELECT * FROM invTypes WHERE typeID = ".intval($typeID);
				$res = $PDDB->sql_query($sql);
				$itemdetails = $PDDB->sql_fetch_row($res);
				$cache->set($cache_name, $itemdetails, 2592000);
			}
		
			return $itemdetails[$argument];
		}
		return null;
	}
	
}
