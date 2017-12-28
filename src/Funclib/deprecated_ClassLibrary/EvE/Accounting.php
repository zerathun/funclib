<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\AccessibleModule;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\EventOperation;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;

class Accounting extends ItemList implements AccessibleModule, Displayable, Storable {
	public function getOutput() {
		$AccountingTemplate = new TemplateReader ();
		$AccountingTemplate->readFile ( "Templates/Accounting.html" );
		
		$AccountingTemplate->inputVariable ( "INPUT_VARIABLE", "" );
		$AccountingTemplate->finalizeOutput ();
		
		if ($this->hasAccess ()) {
			return $AccountingTemplate->getOutput ();
		} else {
			ErrorHandler::getErrorHandler ()->addStandardError ( 1 );
			return "";
		}
	}
	public function DB_Store() {
	}
	public function DB_Delete() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \system\AccessibleModule::hasAccess()
	 */
	public function hasAccess() {
		// TODO Auto-generated method stub
		return UserManagement::getInstance ()->getCurrentUser ()->getGroup ()->hasAccess ( 'assets_access' );
	}
}

?>