<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

/**
 * Module Interface for all Modules
 * @author Sebastian
 *
 */
interface module_interface {
	
	public function __construct($args=array());
	
	public function mainOutput($template);
	
	public function shortOutput();
	
	public function hasAccess();
	
	public function urlExcludeArr();
	
}

?>