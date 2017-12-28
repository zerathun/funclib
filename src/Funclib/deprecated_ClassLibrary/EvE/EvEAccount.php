<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\StorableEList;

class EvEAccount extends StorableEList implements Displayable {
	
	public function loadCharacters($user_id) {
		
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Displayable::getOutput()
	 */
	public function getOutput() {
		// TODO Auto-generated method stub
	}
	public function checkIfCharacterInList($characterId) {
		foreach ( $this->elist as $apiKey ) {
			if ($apiKey->getCharacter ( $characterId ) != null)
				return true;
		}
		return false;
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Store()
	 */
	public function DB_Store() {
		// TODO Auto-generated method stub
		$sql = "SELECT * FROM emb_api WHERE emb_api.embuserID = '" . UserManagement::getInstance ()->getCurrentUser ()->getId () . "'";
		$resource = Database::getInstance ()->sql_query ( $sql );
		
		$api_array_dbdump = array ();
		do {
			$row = Database::getInstance ()->sql_fetch_object ( $resource );
			if (empty ( $row ))
				break;
			$api_array_dbdump [] = array (
					$row->id,
					$row->vCode 
			);
		} while ( true );
		
		foreach ( $this->elist as $APIKeyEl ) {
			$found = false;
			try {
				$APIKeyEl->DB_Store ();
			} catch ( \Exception $e ) {
				ErrorHandler::getErrorHandler ()->addException ( $e );
			}
		}
		
		foreach ( $this->elist as $APIKeyEl ) {
			$found = false;
			try {
				foreach ( $api_array_dbdump as $dump ) {
					if ($dump [0] == $APIKeyEl->getId ()) {
						$sql = "UPDATE emb_api SET
									vCode = '" . $APIKeyEl->getValue () . "'
									apikeyID = '" . $APIKeyEl->getId () . "' 
								WHERE apikeyID = '" . $APIKeyEl->getId () . "' and emb_api.embuserID = '" . UserManagement::getInstance ()->getCurrentUser ()->getId () . "'";
						Database::getInstance ()->sql_query ( $sql );
						
						$found = true;
					}
				}
				try {
					$sql = "INSERT INTO emb_api (apikeyID,vCode,embuserID) VALUES
							('" . $APIKeyEl->getId () . "','" . $APIKeyEl->getValue () . "','" . UserManagement::getInstance ()->getCurrentUser ()->getId () . "')";
					Database::getInstance ()->sql_query ( $sql );
				} catch ( \Exception $e ) {
					ErrorHandler::getErrorHandler ()->addException ( $e );
				}
				$sql = "INSERT INTO emb_api_user (apikeyID, embuserID) VALUES ('" . $APIKeyEl->getId () . "','" . UserManagement::getInstance ()->getCurrentUser ()->getId () . "')";
				Database::getInstance ()->sql_query ( $sql );
			} catch ( \Exception $e ) {
				ErrorHandler::getErrorHandler ()->addException ( $e );
			}
		}
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Delete()
	 */
	public function DB_Delete() {
		// TODO Auto-generated method stub
	}
}

?>
