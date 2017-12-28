<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\AccessibleModule;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\EventOperation;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\POS;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TableCreator;
use zeradun\api_manager\includes\Ember\Perry\Representation\Eve\v1\Character;

class POSManager extends ItemList implements AccessibleModule, Displayable, Storable {
	
	public function getOutput() {
		$POSMNGTemplate = new TemplateReader ();
		$POSMNGTemplate->readFile ( "Templates/POSManager.html" );
		
		$POSMNGTemplate->inputVariable ( "SELECT_MENU", $this->getSelectMenu () );
		
		if ($this->hasAccess ()) {
			
			if (APICacheInfo::getInstance ( 1 )->isExpired ( "StarbaseList" ) || true) {
				$this->DB_Store ();
			}
			
			$content = $this->displayPOSList ();
			$POSMNGTemplate->inputVariable ( "INPUT_VARIABLE", $content );
			$POSMNGTemplate->finalizeOutput ();
			
			return $POSMNGTemplate->getOutput ();
		} else {
			ErrorHandler::getErrorHandler ()->addStandardError ( 1 );
			return "";
		}
	}
	
	private function getSelectMenu() {
		try {
			$arr [] = array (
					'name' => 'char',
					'scope' => 'starbaselist',
					'keytype' => "Character",
					'list_wo_access' => 0 
			);
			CharacterSelect::getInstance ( 1 )->setAccessScopes ( $arr );
			return CharacterSelect::getInstance ( 1 )->getSelectMenu ();
		} catch ( \Exception $e ) {
			print "Exception in POSManager";
		}
	}
	
	private function displayPOSList() {
		$sql = "SELECT mainTable.*, towerName.*, fuelName.typeName as fuelTypeName FROM emb_user_starbases AS mainTable
				LEFT JOIN invTypes as towerName ON mainTable.typeID = towerName.typeID
				LEFT JOIN invTypes as fuelName ON mainTable.fuelTypeID = fuelName.typeID
				 WHERE mainTable.apiKey = '" . CharacterSelect::getInstance ( 1 )->getAPIKey ()->getAPIKey () . "'";
		$resource = Database::getInstance ()->sql_query ( $sql );
		print mysql_error ();
		
		$TableCreator = new TableCreator ( 4 );
		$TableCreator->loadTemplateFiles ( array (
				'outer_box' => "Templates/Tables/POSList_Table.html",
				'inner_box' => "Templates/Tables/POSList_TableElement.html" 
		) );
		
		$class_arr = array (
				"ym-grid-table-titleline",
				"ym-grid-table-titleline",
				"ym-grid-table-titleline",
				"ym-grid-table-titleline" 
		);
		
		$TableCreator->addContent ( array (
				"Tower",
				"typeID",
				"Access-Mask",
				"&nbsp;" 
		), $class_arr );
		
		while ( $row = Database::getInstance ()->sql_fetch_array ( $resource ) ) {
			$POS = new POS ();
			$POS->loadWithArray ( $row );
			$list_output = $POS->getOutputArr ();
			$TableCreator->addContent ( $list_output, array () );
		}
		
		return $TableCreator->getOutput ();
	}
	
	public function DB_Store() {
		$api = CharacterSelect::getInstance ( 1 )->getAPIKey ();
		$char = CharacterSelect::getInstance ( 1 )->getCharacter ();
		
		$SB_List = APIManager::getInstance ()->getQuery ( $char, $api, "StarbaseList" );
		if (! empty ( $SB_List )) {
			$x = $SB_List->__get ( 'starbases' );
			
			$sql = "DELETE FROM emb_user_starbases WHERE apiKey = '" . $api->getAPIKey () . "'";
			Database::getInstance ()->sql_query ( $sql );
			$c = 0;
			$query = "INSERT INTO emb_user_starbases
					 (apiKey, itemID, typeID, locationID, moonID, stateTimestamp, onlineTimestamp, state, standingOwnerID, usageFlags,
						deployFlags, allowCorporationMembers, allowAllianceMembers, fuelTypeID, fuelQuantity, reinforceTypeID, reinforceTypeQuantity)
					VALUES ";
			foreach ( $x as $tower ) {
				$array = $tower->toArray ();
				// print_r($tower->toArray());
				if (isset ( $array ['itemID'] )) {
					$SB_Detail = APIManager::getInstance ()->getQuery ( $char, $api, "StarbaseDetail", array (
							'itemID' => $array ['itemID'] 
					) );
					$sb_det_Res = $SB_Detail->toArray ();
					
					print_r ( $SB_Detail );
					
					if (! empty ( $sb_det_Res ['result'] )) {
						$array = array_merge ( $array, $sb_det_Res ['result'] );
						if ($c > 0)
							$query .= ",\n";
						$empty = array (
								'typeID' => 0,
								'quantity' => 0 
						);
						if (! isset ( $array ['fuel'] )) {
							$array ['fuel'] [0] = $empty;
							$array ['fuel'] [1] = $empty;
						}
						if (! isset ( $array ['fuel'] [0] ))
							$array ['fuel'] [0] = $empty;
						if (! isset ( $array ['fuel'] [1] ))
							$array ['fuel'] [1] = $empty;
						
						$array ['stateTimestamp'] = $this->mktimeFromEvETime ( $array ['stateTimestamp'] );
						$array ['onlineTimestamp'] = $this->mktimeFromEvETime ( $array ['onlineTimestamp'] );
						
						$query .= "('" . $api->getAPIKey () . "', '" . $array ['itemID'] . "', '" . $array ['typeID'] . "', '" . $array ['locationID'] . "', '" . $array ['moonID'] . "', '" . $array ['stateTimestamp'] . "',
					 		'" . $array ['onlineTimestamp'] . "', '" . $array ['state'] . "', '" . $array ['standingOwnerID'] . "',
					 				'" . $array ['generalSettings'] ['usageFlags'] . "',
					 				'" . $array ['generalSettings'] ['deployFlags'] . "',
					 				'" . $array ['generalSettings'] ['allowCorporationMembers'] . "',
					 				'" . $array ['generalSettings'] ['allowAllianceMembers'] . "',
					 				'" . $array ['fuel'] [0] ['typeID'] . "', '" . $array ['fuel'] [0] ['quantity'] . "', '" . $array ['fuel'] [1] ['typeID'] . "', '" . $array ['fuel'] [1] ['quantity'] . "')";
					}
					
					print_r ( $query );
					print_r ( "<br><br>" );
					
					$c ++;
				}
			}
			$query .= ";";
			Database::getInstance ()->sql_query ( $query );
		} else {
		}
	}
	
	/**
	 *
	 * @param String $string        	
	 */
	private function mktimeFromEvETime($string) {
		$d1 = preg_split ( "^[ ]{1,1}^", $string );
		$date = preg_split ( "^[-]{1,1}^", $d1 [0] );
		$time = preg_split ( "^[:]{1,1}^", $d1 [1] );
		return mktime ( $time [0], $time [1], $time [2], $date [1], $date [2], $date [0] );
	}
	public function DB_Delete() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \system\AccessibleModule::hasAccess()
	 */
	public function hasAccess() {
		// TODO Auto-generated method stub
		return UserManagement::getInstance ()->getCurrentUser ()->getGroup ()->hasAccess ( 'pos_manager' );
	}
}

?>