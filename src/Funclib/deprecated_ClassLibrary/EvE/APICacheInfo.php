<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;

class APICacheInfo implements Storable {
	private static $instance;
	private $cache_info;
	private $flag = false;
	private $apiKey;
	private $characterID;
	private $CharSelect_Instance = 0;
	
	/**
	 *
	 * @return \ClassLibrary\EvE\APICacheInfo
	 */
	public static function getInstance($CharSelection_instance = 0) {
		if (empty ( APICacheInfo::$instance )) {
			APICacheInfo::$instance = new APICacheInfo ( $CharSelection_instance );
		}
		return APICacheInfo::$instance;
	}
	
	private function __construct($CharSelection_instance) {
		$this->setCharSelect_Instance ( $CharSelection_instance );
		$this->cache_info = array ();
		$this->setChosenCharacter ();
	}
	
	private function setChosenCharacter() {
		$char = CharacterSelect::getInstance ( $this->CharSelect_Instance )->getCharacter ();
		$currApi = CharacterSelect::getInstance ( $this->CharSelect_Instance )->getAPIKey ();
		$this->apiKey = $currApi->getAPIKey ();
		$this->characterID = $char->getID ();
		$this->loadFromDB ( $this->apiKey, $this->characterID );
	}
	
	private function loadFromDB($apiKey, $charId) {
		$sql = "SELECT * FROM emb_cache_info WHERE apikeyID = '" . $apiKey . "' AND characterID = " . $charId;
		$res = Database::getInstance ()->sql_query ( $sql );
		
		while ( $row = Database::getInstance ()->sql_fetch_array ( $res ) ) {
			$pheal_callname = $row ['request_mode'];
			$this->setCache ( $row ['request_mode'], $row ['cached_until_unixtime'], $row ['request_time_unixtime'], $row ['query_count'] );
			$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_count'] = $row ['query_count'];
			$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_complete'] = $row ['query_complete'];
			$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['db_set'] = true;
			$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_complete'] = ( bool ) $row ['query_complete'];
		}
	}
	public function setCharSelect_Instance($instance) {
		$this->CharSelect_Instance = $instance;
	}
	private function existsInDB($pheal_callname) {
		if (! empty ( $this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['db_set'] )) {
			return ( bool ) ($this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['db_set']);
		}
	}
	
	/**
	 *
	 * @param String $pheal_callname        	
	 * @return boolean
	 */
	public function isExpired($pheal_callname) {
		$exp_d = isset ( $this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['cached_until'] ) ? $this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['cached_until'] : null;
		if (empty ( $exp_d )) {
			return true;
		} else {
			return ($exp_d < time ());
		}
	}
	public function setCache($pheal_callname, $cachingTime, $request_time = 0) {
		$this->flag = true;
		$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['characterID'] = $this->characterID;
		$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['apiKey'] = $this->apiKey;
		$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['cached_until'] = $cachingTime;
		$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['request_time'] = ($request_time > time () - 60) ? $request_time : time ();
	}
	
	/**
	 *
	 * @param String $pheal_callname        	
	 * @param number $query_complete
	 *        	// == 1 or 0
	 */
	public function setQueryComplete($pheal_callname, $query_complete = 0) {
		$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_complete'] = $query_complete ? 1 : 0;
	}
	
	/**
	 *
	 * @param String $pheal_callname
	 *        	@result Boolean
	 */
	public function getQueryComplete($pheal_callname) {
		if (isset ( $this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_complete'] )) {
			return ( bool ) $this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_complete'];
		}
	}
	
	/**
	 *
	 * @param String $pheal_callname
	 *        	@result Boolean
	 */
	public function getQueryCount($pheal_callname) {
		if (isset ( $this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_count'] )) {
			return intval ( $this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_count'] );
		}
	}
	public function setQueryCount($pheal_callname, $count) {
		$this->cache_info [$this->apiKey] [$this->characterID] [$pheal_callname] ['query_count'] = $count;
	}
	public function DB_Delete() {
		return null;
	}
	public function DB_Store() {
		Database::getInstance ()->startDatabaseConnection ();
		foreach ( $this->cache_info as $apikey => $character ) {
			foreach ( $character as $charid => $callname ) {
				foreach ( $callname as $key => $time ) {
					if (empty ( $time ['query_complete'] ))
						$time ['query_complete'] = 0;
					if (empty ( $time ['query_count'] )) {
						$time ['query_count'] = 0;
					}
					if ($this->existsInDB ( $key )) {
						
						$q1 = "
							UPDATE emb_cache_info SET cached_until_unixtime = '" . $time ['cached_until'] . "', request_time_unixtime = '" . $time ['request_time'] . "', query_count = '" . $time ['query_count'] . "', query_complete = '" . $time ['query_complete'] . "'
							WHERE characterID = '" . $time ['characterID'] . "' AND apikeyID = '" . $time ['apiKey'] . "' AND request_mode = '" . $key . "';\n
							";
						Database::getInstance ()->sql_query ( $q1 );
					} else {
						$q2 = "
						INSERT INTO emb_cache_info (characterID,
													apikeyID,
													request_time_unixtime,
													cached_until_unixtime,
													request_mode,
													query_count,
													query_complete
													)
						VALUES ('" . $time ['characterID'] . "','" . $time ['apiKey'] . "', '" . $time ['request_time'] . "', '" . $time ['cached_until'] . "', '" . $key . "', '" . $time ['query_count'] . "', '" . $time ['query_complete'] . "');\n";
						Database::getInstance ()->sql_query ( $q2 );
					}
				}
			}
		}
		
		$q3 = "
				DELETE FROM emb_cache_info 
					WHERE apikeyID = '" . $this->apiKey . "' AND emb_cache_info.characterID = '" . $this->characterID . "' AND cached_until_unixtime < " . time () . ";";
		Database::getInstance ()->sql_query ( $q3 );
	}
	public function __destruct() {
		// Something has changed, so store it
		if ($this->flag || true) {
			$this->DB_Store ();
		}
	}
}

?>