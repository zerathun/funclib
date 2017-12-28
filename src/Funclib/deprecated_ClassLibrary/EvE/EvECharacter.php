<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\FuncLib;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Comparable;

class EvECharacter implements Displayable, Comparable {
	
	protected $CharacterSetting;
	protected $apiKey;
	private $cacheInfoXML;
	public function __construct() {
		$this->apiKey = array ();
	}
	
	/**
	 *
	 * @param EvECharacter $character        	
	 */
	public function isEqual(Comparable $character) {
		return ($character instanceof EvECharacter && $character->getCharacterId () == $this->getCharacterId ());
	}
	
	/**
	 *
	 * @param Generic $object        	
	 * @return boolean
	 */
	public function isSame($object) {
		return (spl_object_hash ( $object ) == spl_object_hash ( $this ));
	}
	
	public function getCharacterName() {
		return $this->CharacterSetting ['characterName'];
	}
	
	public function getCharacterId() {
		return $this->CharacterSetting ['characterID'];
	}
	
	/**
	 * gets the ID of the Character, and if its a Corporation the CorpID
	 *
	 * @return int
	 */
	public function getID() {
		return $this->getCharacterId ();
		if ($this instanceof \ClassLibrary\EvE\EvECorporation && get_class ( $this ) == 'ClassLibrary\EvE\EvECorporation') {
			return $this->getCorpID ();
		} else {
		}
	}
	
	public function loadCharacter() {
		
	}
	
	public function setContents($array) {
		$this->CharacterSetting = $array;
	}
	
	public function getContents() {
		return $this->CharacterSetting;
	}
	
	/**
	 *
	 * @param \ClassLibrary\EvE\APIKey $api        	
	 * @throws \Exception
	 */
	public function setAPI($api) {
		if ($api instanceof APIKey) {
			$this->apiKey [] = $api;
		} else {
			throw new \Exception ( "Not a Object APIKey as argument given" );
		}
	}
	
	/**
	 *
	 * @param number $id        	
	 * @return \ClassLibrary\EvE\APIKey
	 */
	public function getAPI($id = 0) {
		if (isset ( $this->apiKey [$id] )) {
			return $this->apiKey [$id];
		} elseif (isset ( $this->apiKey [0] )) {
			return $this->apiKey [0];
		} else
			return null;
	}
	
	/**
	 *
	 * @return Ambigous <multitype:, \ClassLibrary\EvE\APIKey>
	 */
	public function getAPIs() {
		return $this->apiKey;
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Store()
	 *
	 * public function DB_Store() {
	 * // TODO Auto-generated method stub
	 * $sql = "";
	 * foreach($this->apiKey as $apiKey) {
	 * $checksql = "SELECT count(characterID) as amount FROM emb_characters
	 * WHERE characterID='".$this->getCharacterId()."' AND apikeyID='".$apiKey->getAPIKey()."'";
	 * $resource = Database::getInstance()->sql_query($checksql);
	 * $res = Database::getInstance()->sql_fetch_object($resource);
	 * if($res->amount <= 0) {
	 * $sql .= "INSERT INTO emb_characters
	 * (characterID,apikeyID,cache_expiration,characterName,
	 * corporationID,corporationName,allianceID,allianceName,factionID,factionName)
	 * VALUES ('".$this->getCharacterId()."',
	 * '".$apiKey->getId()."',
	 * '".APIKey::getCacheExpiration("basic")."',
	 * '".$this->getCharacterName()."',
	 * '".$this->CharacterSetting['corporationID']."',
	 * '".$this->CharacterSetting['corporationName']."',
	 * '".$this->CharacterSetting['allianceID']."',
	 * '".$this->CharacterSetting['allianceName']."',
	 * '".$this->CharacterSetting['factionID']."',
	 * '".$this->CharacterSetting['factionName']."');\n";
	 * } else {
	 * $sql .= "UPDATE emb_characters SET
	 * cache_expiration = ".APIKey::getCacheExpiration("basic").",
	 * characterName = '".$this->getCharacterName()."',
	 * corporationID = '".$this->CharacterSetting['corporationID']."',
	 * corporationName = '".$this->CharacterSetting['corporationName']."',
	 * allianceID = '".$this->CharacterSetting['allianceID']."',
	 * allianceName = '".$this->CharacterSetting['allianceName']."',
	 * factionID = '".$this->CharacterSetting['factionID']."',
	 * factionName = '".$this->CharacterSetting['factionName']."',
	 * lastUpdated = ".time()."
	 * WHERE characterID = ".$this->getCharacterId()." AND apikeyID = ".$apiKey->getId().";";
	 * }
	 * }
	 * if(empty($sql)) {
	 * throw new \Exception("SQL Query is empty trying to save EvECharacter into Database");
	 * }
	 * Database::getInstance()->sql_query($sql);
	 * }
	 */
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Delete()
	 *
	 * public function DB_Delete() {
	 * // TODO Auto-generated method stub
	 * $sql = "DELETE FROM emb_api_user WHERE key_id = ".$this->getAPI()->getId()." AND user_id = ".UserManagement::getInstance()->getCurrentUser()->getId();
	 *
	 * Database::getInstance()->sql_query($sql);
	 * $sql = "DELETE FROM emb_character WHERE";
	 * $c = 0;
	 * foreach($this->apiKey as $apiKey) {
	 * if($c > 0)
	 * $sql .= " OR";
	 * $sql = " (characterID = ".$this->getCharacterId()." AND apikeyID = ".$apiKey->getId().")";
	 * $c++;
	 * }
	 * $sql = ";";
	 *
	 * Database::getInstance()->sql_query($sql);
	 * }
	 */
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Displayable::getOutput()
	 */
	public function getOutput() {
		$template = new TemplateReader ();
		$template->readFile ( TMPL_PATH . "CharacterList.html" );
		$template->inputVariable ( "CHAR_NAME", $this->getCharacterName () );
		$template->inputVariable ( "CORP_ID", $this->CharacterSetting ['corporationID'] );
		$template->inputVariable ( "CHAR_ID", $this->getCharacterId () );
		$template->inputVariable ( "ALLIANCE_ID", $this->CharacterSetting ['allianceID'] );
		$template->inputVariable ( "CHAR_ALLY", $this->CharacterSetting ['allianceName'] );
		$template->inputVariable ( "CHAR_CORP", $this->CharacterSetting ['corporationName'] );
		
		$add_content = "";
		foreach ( $this->apiKey as $keys ) {
			// $add_content .= '<div><a href="?id=2&act=del_api&api_id='.$keys->getAPIKey().'" class="ym-button" style="width: 20px; font-size: 8px; margin-top: 2px;"/>Delete</a>';
			$add_content .= '
				<div><div style="font-size: 10px; width: 160px;" class="ym-gr">Key ID: ' . $keys->getAPIKey () . "<br>
				vCode: " . FuncLib::shortenText ( $keys->getVCode (), 20 ) . '<br></div></div>';
		}
		$template->inputVariable ( "ADDITIONAL_CONTENT", $add_content );
		
		$template->finalizeOutput ();
		return $template->getOutput ();
	}
}

?>