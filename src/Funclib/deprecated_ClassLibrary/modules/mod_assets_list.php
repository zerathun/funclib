<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\AssetsList;

class mod_assets_list extends module_abstract {
	
	public function __construct($args=array())
	{
		
	}
	
	public function getModuleId() { return 3; }
	
	public function mainOutput($template)
	{
		$this->setTemplate($template);
		$assets_list = new AssetsList();
	
		$this->template->assign_var('ASSETS_LIST', "asdf".$assets_list->getOutput());
		$this->template->assign_var('SELECT_MENU', $assets_list->getSelectMenu());
	}
	
	public function shortOutput()
	{
		
	}

	/**
	 * Inherited function; Return a single array with names to exclude in the Main-Navigation Elements
	 */
	public function urlExcludeArr() {
		return array();
	}
	
}