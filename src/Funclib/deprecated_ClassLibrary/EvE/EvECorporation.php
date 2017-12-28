<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EvECharacter;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Comparable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\FuncLib;

class EvECorporation extends EvECharacter {
	private $corp_id;
	public function __construct() {
		parent::__construct ();
	}
	public function getOutput() {
		$template = new TemplateReader ();
		$template->readFile ( TMPL_PATH . "CorpList.html" );
		$template->inputVariable ( "CHAR_NAME", $this->CharacterSetting ['corporationName'] );
		$template->inputVariable ( "CORP_ID", $this->CharacterSetting ['corporationID'] );
		// $template->inputVariable("CHAR_ID", $this->getCharacterId());
		$template->inputVariable ( "ALLIANCE_ID", $this->CharacterSetting ['allianceID'] );
		$template->inputVariable ( "CHAR_ALLY", $this->CharacterSetting ['allianceName'] );
		$template->inputVariable ( "CHAR_CORP", $this->CharacterSetting ['corporationName'] );
		
		$add_content = "";
		if (! empty ( $this->apiKey )) {
			foreach ( $this->apiKey as $keys ) {
				// $add_content .= '<div><a href="?id=2&act=del_api&api_id='.$keys->getAPIKey().'" class="ym-button" style="width: 20px; font-size: 8px; margin-top: 2px;"/>Delete</a>';
				$add_content .= '
					<div><div style="font-size: 10px; width: 160px;" class="ym-gr">Key ID: ' . $keys->getAPIKey () . "<br>
					vCode: " . FuncLib::shortenText ( $keys->getVCode (), 20 ) . '<br></div></div>';
			}
		}
		$template->inputVariable ( "ADDITIONAL_CONTENT", $add_content );
		
		$template->finalizeOutput ();
		return $template->getOutput ();
	}
	
	public function setCorpID($id) {
		$this->corp_id = $id;
	}
	public function getCorpID() {
		return $this->corp_id;
	}
	public function getID() {
		return $this->getCorpID ();
	}
	public function getCorpName() {
		return $this->CharacterSetting['corporationName'];
	}
	public function isEqual(Comparable $Corp) {
		return ($Corp instanceof EvECorporation && $Corp->getCorpID () == $this->getCorpID ());
	}
}

?>