<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;

class EvEJobs {
	
	public function __construct() {
		$UserManagement = UserManagement::getInstance ();
	}
	
	private function getApiKeys() {
		$UserManagement = UserManagement::getInstance ();
		$UserManagement->initialize ();
		$api_list = $UserManagement->getCurrentUser()->loadAPI_Basic();
		print_r($api_list);
		return UserManagement::getInstance ()->getCurrentUser()->getAPIList();
	}
	
	public function getJobs() {
		$r = $this->getApiKeys();

		print_r($r);
	}
	
	
}


?>