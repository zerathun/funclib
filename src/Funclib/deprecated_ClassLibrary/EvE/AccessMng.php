<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\Pheal\Core\Config;
use zeradun\api_manager\includes\Ember\Pheal\Exceptions\APIException;

class AccessMng {
	private $charSelect;
	function __construct() {
		$this->charSelect = CharacterSelect::getInstance ();
	}
	public function CurrentCharAccess($call_name = 'assetlist') {
		if ($this->charSelect->getCharacter () instanceof EvECorporation && get_class ( $this->charSelect->getCharacter () ) == 'ClassLibrary\EvE\EvECorporation') {
			$type = "Character";
		} else
			$type = "Character";
		try {
			$accessMask = $this->charSelect->getAPIKey ()->getAccessMask ();
			Config::getInstance ()->access->check ( "char", $call_name, $type, $accessMask );
			return true;
		} catch ( APIException $e ) {
			return false;
		} catch ( \Exception $x ) {
			return false;
		}
	}
}

?>