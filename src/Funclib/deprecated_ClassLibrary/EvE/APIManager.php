<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIKey;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;

class APIManager {
	
	private $apis;
	private $characters;
	private static $instance;
	
	/**
	 * private constructor
	 */
	private function __construct() {
		$this->characters = array ();
		$this->apis = array ();
	}
	
	/**
	 *
	 * @return APIManager
	 */
	public static function getInstance() {
		if (empty ( APIManager::$instance )) {
			APIManager::$instance = new APIManager ();
		}
		return APIManager::$instance;
	}
	
	/**
	 */
	public function getAPIList($force_refresh = false) {
		if (! empty ( $this->apis )) {
			return $this->apis;
		}
		
		$sql = "SELECT * FROM 
					(SELECT emb_api_user.*,emb_api.vCode,
											emb_api.expiration, 
											emb_api.refresh 
							FROM emb_api_user,emb_api
							WHERE emb_api.apikeyID = emb_api_user.apikeyID 
									AND embuserID = " . UserManagement::getInstance ()->getCurrentUser ()->getId () . ")
				 AS userapi";

		$resource = Database::getInstance ()->sql_query ( $sql );
		
		$row = Database::getInstance ()->sql_fetch_array ( $resource );
		
		while ( ! empty ( $row ) ) {
			if(!empty($row['apikeyID']) && !empty($row['vCode'])) {
				try {
					$apikey = new APIKey ( $row['apikeyID'], $row['vCode'] );
					$apikey->setExpiration ( $row->expiration );
				} catch ( \Exception $e ) {
					ErrorHandler::getErrorHandler ()->addException ( $e );
				}
				try {
					$apikey->loadPheal ();
					$this->apis [] = $apikey;
				} catch ( \Exception $e ) {
					ErrorHandler::getErrorHandler ()->addException ( $e );
				}
			}
			$row = Database::getInstance ()->sql_fetch_array ( $resource );
		}
		return $this->apis;
	}
	
	public function getCharacterList() {
		if (! empty ( $this->characters )) {
			return $this->characters;
		} else {
			$this->characters = array ();
		}
		
		$list = $this->getAPIList ();
		foreach ( $list as $api ) {
			$chars = $api->getCharacters ();
			
			if (! empty ( $chars )) {
				foreach ( $chars as $char ) {
					$char_in_list = false;
					foreach ( $this->characters as $ctmp ) {
						if ($ctmp->isSame ( $char )) {
							$char_in_list = true;
						}
					}
					if (! $char_in_list) {
						$this->characters [] = $char;
					}
				}
			} else {
				throw new \Exception ( "Current API " . $api->getAPIKey () . " contains no value" );
			}
		}
		return $this->characters;
	}
	
	/**
	 *
	 * @param unknown $char_id        	
	 * @return Ambigous <multitype:, unknown>|NULL
	 */
	public function getCharacter($char_id) {
		$list = $this->getCharacterList ();
		
		foreach ( $list as $character ) {
			if ($character->getID () == $char_id)
				return $character;
		}
		return null;
	}
	
	/**
	 *
	 * @param Integer $key_id        	
	 * @return Ambigous <multitype:, \ClassLibrary\EvE\APIKey>|NULL
	 */
	public function getAPIKey($key_id) {
		foreach ( $this->apis as $key ) {
			if ($key->getAPIKey () == $key_id) {
				return $key;
			}
		}
		return null;
	}
	
	/**
	 *
	 * @param EvECharacter $character        	
	 * @param APIKey $apikeyID        	
	 * @return boolean|unknown
	 */
	public function getQuery(EvECharacter $character, $apikey = null, $call_function, $arguments = array()) {
		if (! ($character instanceof EvECharacter && $apikey instanceof APIKey)) {
			throw new \Exception ( "Wrong arguments given in APIManager getQuery(arg,arg)" );
		}
		$success = true;
		try {
			$pheal = $apikey->executePheal ( $character, $call_function, $arguments );
			return $pheal;
		} catch ( \Exception $e ) {
			ErrorHandler::getErrorHandler ()->addException ( $e );
			return null;
		}
	}
	
	private $public_corp_apis = array();
	
	public function getAllCorpAPIs($loadWPheal=false,$refresh_from_db=false) {
		if(empty($this->public_corp_apis) || $refresh_from_db) {
			$SQL = "SELECT * FROM emb_api WHERE NOT corp_id = 0 && not_adm_corp_key = 0";
			$res = Database::getInstance()->sql_query($SQL);
			while($row = Database::getInstance()->sql_fetch_array($res)) {
				$api = new APIKey($row['apikeyID'],$row['vCode']);
				$api->loadPheal();
				$this->public_corp_apis[] = $api;
			}
			return $this->public_corp_apis;
		} else {
			return $this->public_corp_apis;
		}
	}
}