<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_interface;
use zeradun\api_manager\includes\Ember\ClassLibrary\modules\module_handler;

abstract class module_abstract implements module_interface {
	
	protected $template;
	
	public abstract function getModuleId();
	
	protected function hasWriteAccess() {
		global $user;
		$mod_handler = module_handler::getInstance();
		return $mod_handler->hasWriteAccess($user->data['user_id'], $this->getModuleId());
	}
	
	protected function setTemplate($template) {
		$this->template = $template;
	}
	
	public function hasAccess() {
		global $user;
		return module_handler::getInstance()->hasAccess($user->data['user_id'], $this->getModuleId());
		
	}
	
	public function makeSQLSafe($sql) {
		$sql = str_replace("SELECT", "", $sql);
		$sql = str_replace("GRANT", "", $sql);
		$sql = str_replace("DELETE", "", $sql);
		$sql = str_replace("UPDATE", "", $sql);
		$sql = str_replace("INSERT", "", $sql);
		$sql = htmlspecialchars($sql);
		return $sql;
	}
	
}

?>