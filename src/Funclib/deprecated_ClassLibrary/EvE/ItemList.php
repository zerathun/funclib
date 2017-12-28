<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\EList;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;

abstract class ItemList extends EList implements Storable {
	public function __construct() {
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \ClassLibrary\system\EList::getList()
	 * @return \ClassLibrary\EvE\Asset
	 */
	public function getList() {
		return parent::getList ();
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