<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\EList;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;

abstract class StorableEList extends EList implements Storable {
	function __construct() {
		parent::__construct ();
	}
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Store()
	 */
	public abstract function DB_Store();
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\Ifaces\Storable::DB_Delete()
	 */
	public abstract function DB_Delete();
}

?>