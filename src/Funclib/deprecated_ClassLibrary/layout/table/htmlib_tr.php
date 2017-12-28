<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\table;

use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_layout;
use zeradun\api_manager\includes\Ember\ClassLibrary\iface\table\htmlib_parent;

/**
 * Attribute Factory Class.
 * Every Entity does manage his attributes with that factory
 *
 *
 * PHP versions 4 and 5
 *
 * Copyright notice
 *
 * (c) 2007 Sebastian Winterhalder <sebi@concastic.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * htmlib_tr
 *
 * Supports creating a table
 *
 * @version 1.0
 * @author Sebastian Winterhalder (sebi@concastic.ch)
 *        
 */
class htmlib_tr extends htmlib_parent implements htmlib_I_layout {
	private $cells;
	
	/**
	 * F�gt Zeilen Inhalte hinzu
	 *
	 * @param unknown_type $tdObj        	
	 */
	public function addTd($tdObj) {
		if (! ($tdObj instanceof htmlib_td))
			throw new \Exception ( 'Wrong Argument. Type htmlib_td required.' );
		$this->cells [] = $tdObj;
	}
	public function getCell($index) {
		return $this->cells [$index];
	}
	public function countCells() {
		return count ( $this->cells );
	}
	
	/**
	 * Gibt das Generierte HTML Element aus
	 */
	public function getElementRendered() {
		if (! empty ( $this->comment ))
			$this->appendString ( "\n<!-- Class TR: " . $this->comment . " -->\n" );
		$this->appendString ( "<tr" );
		$this->appendString ( $this->attributes->getAttributes () );
		$this->appendString ( ">\n" );
		
		if (empty ( $this->cells ))
			$this->cells = array ();
		
		foreach ( $this->cells as $key => $cell ) {
			$this->appendString ( $cell->getElementRendered () );
		}
		$this->appendString ( "</tr>\n" );
		if (! empty ( $this->comment ))
			$this->appendString ( "\n<!-- END Class TR: " . $this->comment . " -->\n" );
		return $this->getResult ();
	}
}
?>