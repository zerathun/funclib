<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\AccessibleModule;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ListItem;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\Asset;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\FileSystem;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\CharacterSelect;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\EventOperation;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EventListener;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\PublicAPIInformation;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EList;
use zeradun\api_manager\includes\Ember\Pheal\Core\RowSetRow;
use zeradun\api_manager\includes\Ember\Pheal\Core\RowSet;

class AssetsList extends ItemList implements AccessibleModule, Displayable, Storable, EventOperation {
	private $current_asset_group = 0;
	private $current_asset_category = 0;
	private $cache_db_storage = 600; // Delay Timer for DB-Storage Queries (5 min)
	private $current_char_id = 0;
	private $db_cache_assets; // array
	private $db_loaded_items; // Count of loaded items
	private $temp_filefolder = "Cache/DB_Loading/";
	
	public function __construct() {
		$this->elist = array ();
		parent::__construct ();
		$EventListener = EventListener::getInstance ();
		$EventListener->registerEventOperation ( $this );
		PublicAPIInformation::getInstance ();
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \system\AccessibleModule::hasAccess()
	 */
	public function hasAccess() {
		// TODO Auto-generated method stub
		return UserManagement::getInstance ()->getCurrentUser ()->getGroup ()->hasAccess ( 'assets_access' );
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Displayable::getOutput()
	 */
	public function getOutput() {
		$AssetsListFrame = new TemplateReader ();
		$AssetsListFrame->readFile ( "ext/zeradun/api_manager/includes/Ember/Templates/AssetsListFrame.html" );
		$AssetsListFrame->inputVariable ( "SELECT_MENU", $this->getSelectMenu () );
		
		$AssetListItem = new TemplateReader ();
		$AssetListItem->readFile ( "ext/zeradun/api_manager/includes/Ember/Templates/AssetList.html" );
		$list_content = "";
		
		$AccessMng = new AccessMng ();
		if ($AccessMng->CurrentCharAccess ( "assetlist" )) {
			$this->compileAssetsList ();
			CharacterSelect::getInstance ()->getCharacter ()->getCharacterId ();
			$iterator = $this->getIterator ();
			while ( $iterator->current () ) {
				if ($iterator->current () instanceof Asset) {
					$output = $iterator->current ()->getListLine ();
					$list_content .= $output;
					$iterator->next ();
				} else {
					throw new \Exception ( " Wrong datatype given, must be of 'Asset'" );
				}
			}
		} else {
			$list_content = "";
		}
		
		$AssetsListFrame->inputVariable ("ASSET_LIST", $list_content );
		$AssetsListFrame->finalizeOutput ();
		
		if ($this->hasAccess ()) {
			return $AssetsListFrame->getOutput ();
		} else {
			ErrorHandler::getErrorHandler ()->addStandardError ( 1 );
			return "";
		}
	}
	
	public function getSelectMenu() {
		
		try {
			$arr [] = array (
					'name' => 'char',
					'scope' => 'assetlist',
					'keytype' => "Character",
					'list_wo_access' => 1 
			);
			CharacterSelect::getInstance ()->setAccessScopes ( $arr );
			return CharacterSelect::getInstance ()->getSelectMenu ('char_select', 0);
		} catch ( \Exception $e ) {
			print "Exception in AssetsList";
		}
	}
	
	// LEFT JOIN ember.stastations ON emb_user_assets.locationID = stastations.stationID
	public function loadAssets() {
		$char = CharacterSelect::getInstance ()->getCharacter ();
		
		$own_id = UserManagement::getInstance ()->getCurrentUser ()->getId ();
		
		$sql = "
				SELECT Counting.totalCount,locCounting.locationCount, locCounting.locationID FROM
					(SELECT count(assets.itemId) as totalCount FROM emb_user_assets as assets WHERE assets.ownerID = '" . $own_id . "' AND characterID = " . $char->getID () . ") as Counting
					,(SELECT count(assets.itemId) as locationCount,assets.locationID as locationID FROM emb_user_assets as assets WHERE assets.ownerID = '" . $own_id . "' AND characterID = " . $char->getID () . " GROUP BY  assets.locationID) AS LocCounting
			   ";
		$res = Database::getInstance ()->sql_query ( $sql );
		
		while ( $row = Database::getInstance ()->sql_fetch_array ( $res ) ) {
			// print_r($row);
		}
		
		$sql = "SELECT assets.*,
					t1.groupID,
					t1.typeName,
					t1.mass,
					t1.volume,
					t1.capacity,
					t1.portionSize,
					t1.raceID,
					t1.basePrice,
					t1.marketGroupID,
					invFlags.*
					FROM (SELECT * FROM emb_user_assets AS assets, 
				(SELECT case
				  when locationID BETWEEN 66000000 AND 66014933 then
					(SELECT s.stationName FROM ember.staStations AS s
					  WHERE s.stationID=a.locationID-6000001)
				  when a.locationID BETWEEN 66014934 AND 67999999 then
					(SELECT c.stationName FROM ember.conquerablestations AS c
					  WHERE c.stationID=a.locationID-6000000)
				  when a.locationID BETWEEN 60014861 AND 60014928 then
					(SELECT c.stationName FROM ember.conquerablestations AS c
					  WHERE c.stationID=a.locationID)
				  when a.locationID BETWEEN 60000000 AND 61000000 then
					(SELECT s.stationName FROM ember.staStations AS s
					  WHERE s.stationID=a.locationID)
				  when a.locationID>=61000000 then
					(SELECT c.stationName FROM ember.conquerablestations AS c
					  WHERE c.stationID=a.locationID)
				  else (SELECT m.itemName FROM ember.mapdenormalize AS m
					WHERE m.itemID=a.locationID) end
				AS LocationName, a.locationID AS LID FROM ember.emb_user_assets AS a
			    WHERE characterID = " . $char->getID () . "
				GROUP BY a.locationID) AS locs
			    WHERE assets.ownerID = '" . $own_id . "' AND characterID = " . $char->getID () . " AND locs.LID = assets.locationID AND assets.parentItemId = 0) as assets
			    LEFT JOIN ember.invFlags as invFlags ON assets.flag = invFlags.flagID
			    LEFT JOIN invtypes AS t1 ON assets.typeID = t1.typeID
			    LIMIT 0,50
			    ;";
		try {
			$resource = Database::getInstance ()->sql_query ( $sql );
			print mysql_error ();
		} catch ( \Exception $e ) {
			print_r ( $e );
		}
		$itemIds = array ();
		$row = Database::getInstance ()->sql_fetch_array ( $resource );
		$this->db_cache_assets = new EList ();
		$asset_array = array ();
		$this->purgeList ();
		while ( ! empty ( $row ) ) {
			$a1 = new Asset ();
			$asset ['CharacterID'] = $row ['characterID']; // $char->getID();
			
			$a1->loadItemDB ( $row );
			if ($a1->getParent () != 0) {
				if (empty ( $asset_array [$a1->getParent ()] )) {
					$tmp1 = $asset_array [$a1->getParent ()] = new Asset ();
					$asset_array [$a1->getParent ()]->loadPlaceholder ( $a1->getParent () );
				}
				$asset_array [$a1->getParent ()]->append ( $a1 );
			} else {
				if (! empty ( $asset_array [$a1->getItemId ()] )) {
					$asset_array [$a1->getItemId ()]->loadItemDB ( $row );
				} else {
					$asset_array [$a1->getItemId ()] = $a1;
					$asset_array [$a1->getItemId ()]->setPlaceholder ( false );
				}
			}
			$itemIds [] = $a1->getItemId ();
			
			$row = Database::getInstance ()->sql_fetch_array ( $resource );
		}
		
		$condition = "";
		foreach ( $itemIds as $ids ) {
			if (strlen ( $condition ) > 0) {
				$condition .= " OR ";
			}
			$condition .= "assets.parentItemId = $ids";
		}
		if (strlen ( $condition ) <= 0) {
			$condition = "false";
		}
		
		$sql = "SELECT assets.*,
					t1.groupID,
					t1.typeName,
					t1.mass,
					t1.volume,
					t1.capacity,
					t1.portionSize,
					t1.raceID,
					t1.basePrice,
					t1.marketGroupID,
					invFlags.*
					FROM (SELECT * FROM emb_user_assets AS assets,
				(SELECT case
				  when locationID BETWEEN 66000000 AND 66014933 then
					(SELECT s.stationName FROM ember.staStations AS s
					  WHERE s.stationID=a.locationID-6000001)
				  when a.locationID BETWEEN 66014934 AND 67999999 then
					(SELECT c.stationName FROM ember.conquerablestations AS c
					  WHERE c.stationID=a.locationID-6000000)
				  when a.locationID BETWEEN 60014861 AND 60014928 then
					(SELECT c.stationName FROM ember.conquerablestations AS c
					  WHERE c.stationID=a.locationID)
				  when a.locationID BETWEEN 60000000 AND 61000000 then
					(SELECT s.stationName FROM ember.staStations AS s
					  WHERE s.stationID=a.locationID)
				  when a.locationID>=61000000 then
					(SELECT c.stationName FROM ember.conquerablestations AS c
					  WHERE c.stationID=a.locationID)
				  else (SELECT m.itemName FROM ember.mapdenormalize AS m
					WHERE m.itemID=a.locationID) end
				AS LocationName, a.locationID AS LID FROM ember.emb_user_assets AS a
			    WHERE characterID = " . $char->getID () . "
				GROUP BY a.locationID) AS locs
			    WHERE assets.ownerID = '" . $own_id . "' AND characterID = " . $char->getID () . " AND locs.LID = assets.locationID AND ($condition)) as assets
			    LEFT JOIN ember.invFlags as invFlags ON assets.flag = invFlags.flagID
			    LEFT JOIN invtypes AS t1 ON assets.typeID = t1.typeID
			    ;";
		
		$x1 = Database::getInstance ()->sql_query ( $sql );
		
		while ( $row = Database::getInstance ()->sql_fetch_array ( $x1 ) ) {
			$a1 = new Asset ();
			$asset ['CharacterID'] = $row ['characterID']; // $char->getID();
			
			$a1->loadItemDB ( $row );
			if ($a1->getParent () != 0) {
				if (empty ( $asset_array [$a1->getParent ()] )) {
					$tmp1 = $asset_array [$a1->getParent ()] = new Asset ();
					$asset_array [$a1->getParent ()]->loadPlaceholder ( $a1->getParent () );
				}
				$asset_array [$a1->getParent ()]->append ( $a1 );
			} else {
				if (! empty ( $asset_array [$a1->getItemId ()] )) {
					$asset_array [$a1->getItemId ()]->loadItemDB ( $row );
				} else {
					$asset_array [$a1->getItemId ()] = $a1;
					$asset_array [$a1->getItemId ()]->setPlaceholder ( false );
				}
			}
		}
		
		$c = 0;
		foreach ( $asset_array as $asKey => $asset ) {
			if ($asset->isPlaceholder ()) {
				$c ++;
			}
			
			$this->addItem ( $asset );
		}
		if ($c > 0) {
			throw new Exception ( "Items are not sorted / indexed well. Count of unallocated Items: $c" );
		}
	}
	
	private function recursiveRowset($rowset, $parent = null, $stufe = 0, $options = array()) {
		if (! ($rowset instanceof RowSet)) {
			throw new \Exception ( "Rowset must be an RowSet object" );
		}
		$it = $rowset->getIterator ();
		
		while ( $it->current () ) {
			$value = $it->current ();
			$asset = new Asset ();
			$asset->setParent ( $parent );
			$array = $value->toArray ();
			$array ['OwnerID'] = UserManagement::getInstance ()->getCurrentUser ()->getId ();
			$array ['CharacterID'] = $options ['char']->getID ();
			$asset->loadItemDB ( $array );
			
			if ($parent == null && empty ( $parent )) {
				$this->addAsset ( $asset );
			} else {
				$parent->append ( $asset );
			}
			
			if ($value->offsetExists ( "contents" )) {
				if ($value->offsetGet ( "contents" ) instanceof RowSet) {
					$this->recursiveRowset ( $value->offsetGet ( "contents" ), $asset, $stufe + 1, $options );
				} else {
					throw new \Exception ( "Value in the list that is not currently handleable" );
				}
			} else {
			}
			$it->next ();
		}
	}
	
	public function compileAssetsList() {
		$api = CharacterSelect::getInstance ()->getAPIKey ();
		$char = CharacterSelect::getInstance ()->getCharacter ();
		if ((APICacheInfo::getInstance ()->isExpired ( "AssetList" ) || (! APICacheInfo::getInstance ()->isExpired ( "assets_db_storage" )) && ! APICacheInfo::getInstance ()->getQueryComplete ( "assets_db_storage" ))) {
			
			try {
				if ($api->getKeyMode () == "Corporation") {
					$call_func = "";
				}
				
				$result = APIManager::getInstance ()->getQuery ( $char, $api, "AssetList" );
				
				if (! empty ( $result )) {
					$x = $result->__get ( 'assets' );
					$assets = $x->toArray ();
					
					foreach ( $assets as $asset ) {
						$a1 = new Asset ();
						$asset ['CharacterID'] = $char->getID ();
						
						$a1->loadItemDB ( $asset );
						$this->addAsset ( $a1 );
					}
				} else {
					ErrorHandler::getErrorHandler ()->addError ( "EvE API could not load the selected Assets" );
				}
				
				if (APICacheInfo::getInstance ()->isExpired ( "AssetList" ) || APICacheInfo::getInstance ()->isExpired ( "assets_db_storage" )) {
					$this->DB_Store ();
				}
				
				$this->purgeList ();
				$this->loadAssets ();
			} catch ( \Exception $e ) {
				ErrorHandler::getErrorHandler ()->addException ( $e );
			}
		} else {
			$this->loadAssets ();
		}
	}
	
	/**
	 * Wrapper function to add a list item
	 *
	 * @param ListItem $listItem        	
	 */
	public function addAsset(Asset $listItem) {
		parent::addItem ( $listItem );
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Store()
	 */
	public function DB_Store() {
		if (APICacheInfo::getInstance ()->isExpired ( "assets_db_storage" )) {
			$curr_char = CharacterSelect::getInstance ()->getCharacter ()->getID ();
			$userID = UserManagement::getInstance ()->getCurrentUser ()->getId ();
			
			$assetList = $this->getList ();
			$sql_list = new \ArrayObject ();
			$sql = "DELETE FROM emb_user_assets WHERE OwnerID = '" . $userID . "' AND characterID = '" . $curr_char . "';\n";
			
			$query_count = 0;
			
			foreach ( $assetList as $asset ) {
				$asset->appendDataImportFile ( $sql_list );
			}
			
			$query_count = $sql_list->count ();
			$flag = $_SESSION ['mysql_updates'] [UserManagement::getInstance ()->getCurrentUser ()->getId ()] ['ident_1'];
			$finalized_sql = "";
			if (! empty ( $flag ) && ($flag !== true || $flag != 1)) {
				try {
					APICacheInfo::getInstance ()->setCache ( "assets_db_storage", time () + $this->cache_db_storage, time (), $query_count );
					APICacheInfo::getInstance ()->setQueryCount ( "assets_db_storage", $query_count );
					APICacheInfo::getInstance ()->setQueryComplete ( "assets_db_storage", false );
					$sql_iterator = $sql_list->getIterator ();
					
					while ( $sql_iterator->current () ) {
						$finalized_sql .= $sql_iterator->current ();
						$sql_iterator->next ();
					}
					
					$filename = $this->temp_filefolder . $curr_char . "_$userID.txt";
					$File = new FileSystem ( $filename, "w+" );
					$File->writeFile ( $finalized_sql );
					
					$sql = "DELETE FROM emb_user_assets WHERE OwnerID = '" . $userID . "' AND characterID = '" . $curr_char . "';\n";
					Database::getInstance ()->sql_query ( $sql );
					$update_db = "
							LOAD DATA INFILE '" . $_SERVER ["DOCUMENT_ROOT"] . "/Ember/$filename' INTO TABLE emb_user_assets
							FIELDS ENCLOSED BY '\"' TERMINATED BY ','
							(`OwnerID`,
												`characterID`,
												`typeID`,
												`itemID`,
												`locationID`,
												`quantity`,
												`flag`,
												`parentItemID`,
												`singleton`)
							";
					
					Database::getInstance ()->sql_query ( $update_db );
					APICacheInfo::getInstance ()->setQueryComplete ( "assets_db_storage", 1 );
				} catch ( \Exception $e ) {
					ErrorHandler::getErrorHandler ()->logError ( $e );
					throw new \Exception ( "Query Error in AssetsList DB_Store()" );
				}
			} else {
				throw new \Exception ( "Database not ready yet" );
			}
		} else {
			print "Store is disabled";
		}
	}
	public function DB_Delete() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\EventOperation::CallEventOperation()
	 */
	public function CallEventOperation() {
		// TODO Auto-generated method stub
		// $this->DB_Store();
	}
}

?>