<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EvECharacter;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIKey;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\GlVars;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\glVar;

class CharacterSelect {
	private static $instance;
	private static $inst_nr;
	private $current_char_id = 0;
	private $current_char = null;
	private $current_api = null;
	private $allowKeyTypes = array('Corporation', 'Character', 'Account');
	
	/**
	 *
	 * @return CharacterSelect
	 */
	public static function getInstance($instance = 0) {
		if (empty ( CharacterSelect::$instance [$instance] )) {
			CharacterSelect::$inst_nr = $instance;
			CharacterSelect::$instance [$instance] = new CharacterSelect ();
		}
		return CharacterSelect::$instance [$instance];
	}
	
	/**
	 * Singleton Constructor
	 */
	private function __construct() {
		$post_api_character = GlVars::emb_request_var('api_character', "_POST", '');
		
		if (!empty ( $post_api_character )) {
			try {
				$this->setCurrentFromCombined ( $post_api_character );
				$this->current_char_id = $post_api_character;
				$var = GlVars::getGlVars ()->getVar ( "assets_list_char" );
				if ($var != null && $var instanceof glVar) {
					
					$val = $var->getValue ();
					$arr = $val;
					if (! isset ( $val [CharacterSelect::$inst_nr] )) {
						$val [CharacterSelect::$inst_nr] = null;
					}
					$val = $val [CharacterSelect::$inst_nr];
				}
				
				$arr [CharacterSelect::$inst_nr] = $post_api_character;
				GlVars::getGlVars ()->setVar ( "assets_list_char", $arr );
				UserManagement::getInstance ()->getCurrentUser ()->setLastEvEChar ( $this->current_char->getCharacterId (), $this->current_api->getAPIKey () );
			} catch ( \Exception $e ) {
				$list = APIManager::getInstance ()->getAPIList ();
				foreach ( $list as $api ) {
					if(array_search($api->getKeyMode(), $this->allowKeyTypes) !== false) {
						$char_list = $api->getCharacters ();
						foreach ( $char_list as $charx ) {
							$value = $charx->getCharacterId () . "_" . $api->getAPIKey ();
							if ($value == $_POST ['api_character'])
								$this->current_char_id = $value;
						}
					}
				}
			}
		} else {
			$var = GlVars::getGlVars ()->getVar ( "assets_list_char" );
			
			if ($var != null) {
				$val = $var->getValue ();
				if (empty ( $val [CharacterSelect::$inst_nr] )) {
					$val [CharacterSelect::$inst_nr] = null;
				}
				$currentChar = $val [CharacterSelect::$inst_nr];
			}
			
			if (! empty ( $currentChar )) {
				$this->current_char_id = $currentChar;
				$this->setCurrentFromCombined ( $currentChar );
			} else {
				$list = APIManager::getInstance ()->getAPIList();
				try {
				foreach ( $list as $api ) {
						// Only list the key / characters if key type allowed
						if(array_search($api->getKeyMode(), $this->allowKeyTypes) !== false) {
							$char_list = $api->getCharacters ();
							foreach ( $char_list as $charx ) {
								if(!empty($charx) && !empty($api)) {
									$this->setCurrent ( $charx, $api );
								}
							}
							if (! empty ( $this->current_api ) && ! empty ( $this->current_char ))
								break;
						}
					}
				} catch(\Exception $e) {
					$this->current_api = $this->current_char = null;
				}
				
				if (! empty ( $this->current_char )) {
					try {
						UserManagement::getInstance ()->getCurrentUser ()->setLastEvEChar ( $this->current_char->getCharacterId (), $this->current_api->getAPIKey () );
					} catch (\Exception $e) {
						$this->current_api = $this->current_char = null;
					}
				}
			}
		}
	}
	
	/**
	 *
	 * @return string
	 */
	public function getSelectMenu($select_name = "api_character", $add_form = 1) {
		$list = !empty($this->apiKeyList) ? $this->apiKeyList : APIManager::getInstance ()->getAPIList ();
		$select = "";
		
		if($add_form)
			$select .= "<form action=\"\" method=\"post\">";
		$select .= "<select name=\"$select_name\">";
		// onchange=\"submit();\"
		
		foreach ( $list as $api ) {
			// Only display the correct Types that are allowed
			if(array_search($api->getKeyMode(), $this->allowKeyTypes) !== false) {
				$char_list = $api->getCharacters ();
				foreach ( $char_list as $char ) {
					$value = $char->getID () . "_" . $api->getAPIKey ();
					$selected = ($this->current_char_id == $value) ? " selected" : "";
					$addendum = "";
					
					foreach ( $this->scopes_array as $scope ) {
						if (isset ( $scope ['scope'] ) && isset ( $scope ['name'] ) && isset ( $scope ['keytype'] )) {
							try {
								if ($api->getAPIAccess ( $scope ['scope'], $scope ['name'], $scope ['keytype'], $api->getAccessMask () )) {
									$addendum = " [OK]";
								} else {
									$addendum = ' [No access]';
								}
							} catch ( \Exception $e ) {
								$addendum = '[No access]';
							}
						}
					}
					
					if ($api->getKeyMode () == 'Corporation') {
						$char_arr = $char->getContents ();
						$display_value = "Corporation: " . $char_arr ['corporationName'] . " " . $addendum;
						//$select .= "<option value=\"" . $value . "\"$selected>" . "Corporation: " . $char_arr ['corporationName'] . " " . $addendum . "</option>";
					} else {
						$display_value = $char->getCharacterName () . " (" . $api->getAPIKey () . ")" . $addendum;
						///$select .= "<option value=\"" . $value . "\"$selected>" . $char->getCharacterName () . " (" . $api->getAPIKey () . ")" . $addendum . "</option>";
					}
					$bool_select = (bool)($this->current_char_id == $value);
					$this->addToSelectList($display_value, $value, $bool_select);
				}
			}
		}
		
		if (count ( $list ) < 1) {
			$select .= "<option>No Character in list</option>";
		} else {
			foreach($this->additional_list_items as $list_item) {
				$selct = ($list_item[2]) ? " selected" : "";
				$select .= "<option value=\"". $list_item[1]."\"$selct>".$list_item[0]."</option>";
			}
		}
		
		$select .= "</select>";
		if($add_form)
			$select .= "</form>";
		return $select;
	}
	
	private $scopes_array;
	/**
	 * [] = array('scope'=>'char','name'=>'assetlist','keytype'=>'Character','list_wo_access'=1);
	 *
	 * @param Array $array        	
	 */
	public function setAccessScopes(&$array) {
		$this->scopes_array = $array;
	}
	
	/**
	 * 
	 * @param unknown $array = array('List Display', 'list_value', 'position integer (-1 = undefined / automated) ')
	 */
	public function addToSelectList($display_value, $element_value, $selected=0, $position=-1) {
		$additional = array(array($display_value, $element_value, $selected, $position));
		$this->additional_list_items = array_merge($this->additional_list_items, $additional);
	}
	
	/*
	 * additional list information
	 */
	private $additional_list_items = array();
	
	/**
	 * restrict or allow key types to be listed
	 * @param Array $array
	 */
	public function allowKeyTypes($array = array('Corporation', 'Character', 'Account')) {
		foreach($array as $ele) {
			if($ele != 'Corporation' && $ele != 'Character' && $ele != 'Account')
				throw new \Exception("AllowKeyTypes must be 'Character, Account or Corporation'");
		}
		
		$this->allowKeyTypes = $array;
	}
	
	/**
	 * set current character with key id (Combined string)
	 * xxx_yyy
	 * 
	 * @param unknown $combined_string
	 */
	public function setCurrentChar($combined_string) {
		$this->current_char_id = $combined_string;
		$this->setCurrentFromCombined ( $combined_string );
	}

	
	/**
	 *
	 * @throws \Exception
	 * @return \ClassLibrary\EvE\APIKey
	 */
	public function getAPIKey() {
		if (! empty ( $this->current_api )) {
			return $this->current_api;
		} else {
			throw new \Exception ( "Function 'getAPIKey()' would throw a NULL Value 'CharacterSelect.php'" );
		}
	}
	
	
	/**
	 * In special cases we want to create a indepent API list, hence you can add a API to the list, but then the USERs API will be ignored
	 * @param APIKey $apiKey
	 */
	public function addAPIKey($apiKey) {
		if($apiKey instanceof APIKey) {
			$this->apiKeyList[] = $apiKey;
 		}
	}
	
	/**
	 * Special API Key list, if this item is filled with values of APIKey, then the list wont sho the current USERS api key
	 * @var Array
	 */
	private $apiKeyList = array();
	
	/**
	 *
	 * @throws \Exception
	 * @return \ClassLibrary\EvE\EvECharacter
	 */
	public function getCharacter() {
		if (! empty ( $this->current_char )) {
			return $this->current_char;
		} else {
			throw new \Exception ( "Function 'getAPIKey()' would throw a NULL Value 'CharacterSelect.php'" );
		}
	}
	
	/**
	 *
	 * @param String $combined_string        	
	 */
	private function setCurrentFromCombined($combined_string) {
		if ($combined_string == 0) {
			// throw new \Exception("No Character/API Key Set");
		} else {
			$split = $this->getSplitofCombined ( $combined_string );
			$character = APIManager::getInstance ()->getCharacter ( $split [0] );
			$apikey = APIManager::getInstance ()->getAPIKey ( $split [1] );
			
			try {
				if(!empty($character) && !empty($apikey))
					$this->setCurrent ( $character, $apikey );
			} catch ( \Exception $e ) {
				FileLog::getInstance()->appendLog("Error Occurred in Character Select: ".$e->getMessage."\n".$e->getTrace());
			}
		}
	}
	
	public function getSplitofCombined($combined_string) {
		return split ( "_", $combined_string );
	}
	
	/**
	 *
	 * @param EvECharacter $character        	
	 * @param APIKey $api        	
	 * @throws \Exception
	 */
	private function setCurrent(EvECharacter $character, APIKey $api) {
		if (($character instanceof EvECharacter || $character instanceof EvECorporation) && $api instanceof APIKey) {
			$this->current_char = $character;
			$this->current_api = $api;
		} else {
			throw new \Exception ( "Character or API Key not of a valid type" );
		}
	}
}