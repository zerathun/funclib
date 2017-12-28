<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\Viewport;

use zeradun\api_manager\includes\Ember\Iface\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EList;
use zeradun\api_manager\includes\Ember\ClassLibrary\Viewport;

class MenuList extends EList implements Displayable {
	private $viewport;
	function __construct(Viewport $viewport) {
		if (! ($viewport instanceof Viewport))
			throw new \Exception ();
		$this->viewport = $viewport;
	}
	private $menuItemList;
	public function getOutput() {
		$outer_div = new \htmlib_div ();
		$outer_div->addCssClass ( "" );
		
		return "";
	}
	
	/**
	 * Make method private
	 *
	 * @param unknown $listItem        	
	 */
	private function addItem($listItem) {
		parent::addItem ( $listItem );
	}
	public function addMenuItem(MenuItem $item) {
		if (! ($item instanceof MenuItem)) {
			throw new \Exception ();
		}
		if ($this->isUniqueListItem ( $item )) {
			$this->addItem ( $item );
		}
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \Iface\Displayable::getViewport()
	 */
	public function getViewport() {
		// TODO Auto-generated method stub
		if (! empty ( $this->viewport ) && $this->viewport instanceof Viewport) {
			return $this->viewport;
		} else {
			throw new \Exception ( "Viewport is not correctly set" );
		}
	}
}

?>