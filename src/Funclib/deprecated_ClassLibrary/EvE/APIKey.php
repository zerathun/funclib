<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\Pheal\Pheal;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\Pheal\Core\Config;
use zeradun\api_manager\includes\Ember\Pheal\Cache\FileStorage;
use zeradun\api_manager\includes\Ember\Pheal\Access\StaticCheck;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;
use zeradun\api_manager\includes\Ember\Pheal\Exceptions\APIException;
use zeradun\api_manager\includes\Ember\Pheal\Exceptions\ConnectionException;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EvECorporation;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APICacheInfo;
use zeradun\api_manager\includes\Ember\Pheal\Cache\PdoStorage;

class APIKey implements Storable {
	private $APIKey;
	private $vCODE;
	private $APICache;
	private $timestamp;
	private $phealArray;
	private $lastPhealResult;
	private $PhealObj;
	private $keyMode;
	private $keyWrong;

	public function __construct($APIKey, $vCODE) {
		$this->setAPI ( $APIKey, $vCODE );
	}
	public function setAPI($APIKey, $vCODE) {
		$this->APIKey = $APIKey;
		$this->vCODE = $vCODE;
	}
	
	public function setExpiration($timestamp) {
		$this->timestamp = $timestamp;
	}
	
	public function getExpiration() {
		return $this->timestamp;
	}
	
	public function getAPIKey() {
		return $this->APIKey;
	}
	
	public function isAPIKeyWrong() {
		return $this->keyWrong;
	}
	
	public function setKeyStatus($bool) {
		$this->keyWrong = (bool) $bool;
	}
	
	public function getVCode() {
		return $this->vCODE;
	}
	
	public function getAccessMask() {
		return $this->phealArray ['result'] ['key'] ['accessMask'];
	}
	
	public function getKeyMode() {
		return $this->keyMode;
	}
	
	public function getPhealObj() {
		return $this->PhealObj;
	}
	
	public function loadPheal() {
		try {
			if($this->isAPIKeyWrong()) {
				return false;
			}
			//Config::getInstance ()->cache = new FileStorage ( 'Cache/' );
			$dns = "mysql:dbname=pheal_cache;host=localhost";
			//__construct($dsn, $username, $password, $table = false, array $dbOptions = array())
			Config::getInstance()->cache = new \zeradun\api_manager\includes\Ember\Pheal\Cache\PdoStorage($dns, "pheal_cache", "1234Asdf");
			//Config::getInstance()->cache = new FileStorage("../cache/pheal/");
			Config::getInstance()->access = new StaticCheck ();
			Config::getInstance()-> archive = new \zeradun\api_manager\includes\Ember\Pheal\Archive\FileStorage("/var/www/log/pheal/archive");
			Config::getInstance()-> log = new \zeradun\api_manager\includes\Ember\Pheal\Log\FileStorage("/var/www/log/phealqueries");

			$this->PhealObj = new Pheal ( $this->getAPIKey (), $this->getVCode () );

			$this->PhealObj->setAccess ( "Character" );

			$getAccessMask = $this->PhealObj->detectAccess ();

			if (! empty ( $getAccessMask )) {

				$this->phealArray = $getAccessMask->toArray ();
				$this->keyMode = $this->phealArray ['result'] ['key'] ['type'];
			}
			
			$this->lastPhealResult = $this->PhealObj->APIKeyInfo ();

			if($this->isAPIKeyWrong()) {
				$sql = "UPDATE emb_api SET api_wrong = 0,last_time_checked = '0'  WHERE apikeyID = '".$this->getAPIKey()."'";
				Database::getInstance()->sql_query($sql);
				$this->setKeyStatus(false);
			}
			
		} catch ( ConnectionException $e ) {
			throw $e;
		} catch (APIException $e ) {
			$sql = "UPDATE emb_api SET api_wrong = 1,last_time_checked = '".time()."'  WHERE apikeyID = '".$this->getAPIKey()."'";
			Database::getInstance()->sql_query($sql);
			return false;
		} catch (\Exception $e) {
			print $e->getMessage()." APIKey.php<br>";
		}
		
		return $this->lastPhealResult;
	}
	
	public function getAPIAccess($call_name, $scope, $keytype) {
		try {
			Config::getInstance ()->access->check ( $scope, $call_name, $keytype, $this->getAccessMask () );
			return true;
		} catch ( APIException $e ) {
			return false;
		} catch ( \Exception $e) {
			throw new Exception("Exception in API-Key is thrown - should not happen.");
		}
	}
	
	private $last_time_check;
	
	public function setLastTimeChecked($timestamp) {
		$this->last_time_check = $timestamp;
	}
	
	public function checkKeyAgain() {
		return ($this->last_time_check < time()-86400);
	}
	
	private $PublicEvEInformation = array (
			"AllianceList",
			"CertificateTree",
			"CharacterAffiliation",
			"CharacterID",
			"CharacterInfo",
			"ConquerableStationList",
			"ErrorList",
			"FacWarStats",
			"FacWarTopStats",
			"RefTypes",
			"SkillTree" 
	);
	
	public function executePheal($character, $phealquery, $arguments = array()) {
		try {
			if (array_search ( $phealquery, $this->PublicEvEInformation )) {
				$Pheal = new Pheal ( $this->getAPIKey (), $this->getVCode (), 'eve' );
			} elseif ($this->getKeyMode () == "Corporation") {
				
				$Pheal = new Pheal ( $this->getAPIKey (), $this->getVCode (), 'corp' );
			} elseif ($this->getKeyMode () == "Account" || $this->getKeyMode () == "Character") {
				$Pheal = new Pheal ( $this->getAPIKey (), $this->getVCode (), 'char' );
			} else {
				print "PHEAL NOT ACTIVE";
			}
		
			if ($this->getAPIAccess ( "char", "assetlist", "Character" )) {

				$result = $Pheal->$phealquery ( array_merge ( array (
						"characterID" => $character->getCharacterId () 
				), $arguments ) );
				
				/*APICacheInfo::getInstance ()->setCache ( $phealquery, $result->cached_until_unixtime );
				$cache_setting = array (
						"cached_until_unixtime" => $result->cached_until_unixtime,
						"request_time_unixtime" => $result->request_time_unixtime 
				);*/
				
				// $character->setCacheExpiration($phealquery, $cache_setting);
				return $result;
			} else {
				return null;
			}
		} catch ( APIException $e ) {
			throw $e;
		} catch ( \Exception $e ) {
			ErrorHandler::getErrorHandler ()->addException ( $e );
		}
	}
	
	private $characters;
	
	/**
	 *
	 * @return \ClassLibrary\EvE\EvECharacter
	 */
	public function getCharacters() {
		if (! isset ( $this->lastPhealResult )) {
			$this->loadPheal ();
		} else {
			if (isset ( $this->characters ))
				return $this->characters;
		}
		if(is_object($this->lastPhealResult)) {
			$arr = $this->lastPhealResult->toArray ();
			if (! empty ( $arr ['result'] ['key'] ['characters'] )) {
				foreach ( $arr ['result'] ['key'] ['characters'] as $character ) {
					if ($arr ['result'] ['key'] ['type'] == "Corporation") {
						$Character = new EvECorporation ();
						$Character->setCorpId ( $arr ['result'] ['key'] ['characters'] [0] ['corporationID'] );
					} else {
						$Character = new EvECharacter ();
					}
					$Character->setContents ( $character );
					$Character->setAPI ( $this );
					$this->characters [] = $Character;
				}
			}
			
			return $this->characters;
		} else {
			return array();
		}
	}
	
	/**
	 * 
	 */
	public function updateNotAdmKey() {
		$SQL = "UPDATE emb_api SET not_adm_corp_key = 1
					WHERE emb_api.apikeyID = '" . $this->getAPIKey () . "'";
		$resource = Database::getInstance ()->sql_query ( $SQL );
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Store()
	 */
	public function DB_Store() {
		$SQL = "SELECT count(emb_api.apikeyID) as numbr, emb_api.*
					FROM emb_api
					WHERE apikeyID = '" . $this->getAPIKey () . "'";
		$resource = Database::getInstance ()->sql_query ( $SQL );
		
		$row = Database::getInstance ()->sql_fetch_object ( $resource );

		if($this->getKeyMode() == "Corporation") {
			$chars = $this->getCharacters();
			foreach($chars as $char) {
				$corp_id = $char->getCorpID();
			}
		}
		
		if(!isset($corp_id) || empty($corp_id)) {
			$corp_id = 0;
		}
		
		if (! empty ( $row ) && $row->numbr > 0) {
			$SQL = "UPDATE emb_api SET emb_api.expiration = " . ( string ) (time () + (15 * 60)) . " , corp_id = '".$corp_id."', emb_api.not_adm_corp_key = 0
					WHERE emb_api.apikeyID = '" . $this->getAPIKey () . "'";
		} else {
			$SQL = "INSERT INTO emb_api (apikeyID, vCode, expiration, corp_id)
						VALUES ('" . $this->getAPIKey () . "','" . $this->getVCode () . "','" . ( string ) (time () + (15 * 60)) . "', '".$corp_id."')";
		}
		
		print $SQL;
		
		Database::getInstance ()->sql_query ( $SQL );
		
		// Make the connection db entry
		$SQL1 = "SELECT count(emb_api_user.embuserID) as numbr FROM emb_api_user
						WHERE emb_api_user.embuserID = '" . UserManagement::getInstance ()->getCurrentUser ()->getId () . "'" . " AND apikeyID = '" . $this->getAPIKey () . "'";
		
		$row = Database::getInstance ()->sql_fetch_object ( Database::getInstance ()->sql_query ( $SQL1 ) );
		
		if ($row->numbr < 1) {
			$SQL = "INSERT INTO emb_api_user (embuserID,apikeyID) VALUES ('" . UserManagement::getInstance ()->getCurrentUser ()->getId () . "','" . $this->getAPIKey () . "')";
			Database::getInstance ()->sql_query ( $SQL );
		}
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Delete()
	 */
	public function DB_Delete() {
		$SQL = "DELETE FROM emb_api_user WHERE emb_api_user.apikeyID = " . $this->getAPIKey () . " AND emb_api_user.embuserID = " . UserManagement::getInstance ()->getCurrentUser ()->getId ();
		Database::getInstance ()->sql_query ( $SQL );
		$SQ1 = "SELECT count(apikeyID) as nrm FROM emb_api_user WHERE emb_api_user.apikeyID = " . $this->getAPIKey () . "";
		$res = Database::getInstance()->sql_fetch_array(Database::getInstance ()->sql_query ( $SQ1 ) );
		if($res['nrm'] < 1) {
			$SQ2 = "DELETE FROM emb_api WHERE apikeyID = " . $this->getAPIKey () . "";
			Database::getInstance ()->sql_query ( $SQ2 );
		}
	}
}

?>