<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_abstract;

class mod_pos_manager extends module_abstract {
	
	public function __construct($args=array())
	{
		
	}
	
	public function getModuleId() { return 0; }
	
	
	public function mainOutput($template)
	{
		return "POS Manager";
	}
	
	public function shortOutput()
	{
		
	}
	
	public function hasAccess()	{

	}
	
	/**
	 * Inherited function; Return a single array with names to exclude in the Main-Navigation Elements
	 */
	public function urlExcludeArr() {
		return array();
	}
}