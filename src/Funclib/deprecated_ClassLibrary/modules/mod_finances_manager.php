<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EvEESI;

class mod_finances_manager extends module_abstract {
	
	public function __construct($args=array())
	{
	
	}
	
	public function getModuleId() { return 1; }
	
	public function mainOutput($template)
	{
		
		$eveesi = EvEESI::getInstance();
		/**
		 * Deprecated API Fetch
		
		$apis = APIManager::getInstance()->getAllCorpAPIs();
		
		foreach($apis as $api) {
			if($api->getAPIAccess('WalletTransactions', 'account', 'corporation')) {
				print_r($api);
				print "<br><br>";
			}
		}
		 */
		
		
		return "Finances Manager";
	}
	
	public function shortOutput()
	{
	
	}
	
	public function hasAccess()
	{
	
	}
	
	/**
	 * Inherited function; Return a single array with names to exclude in the Main-Navigation Elements
	 */
	public function urlExcludeArr() {
		return array();
	}
	
}