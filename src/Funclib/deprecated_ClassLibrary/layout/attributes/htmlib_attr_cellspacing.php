<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes;

use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_cellspacing;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class Cellspacing
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

/*
 * Place includes, constant defines and $_GLOBAL settings here.
 */

/**
 * Class Cellspacing
 *
 *
 *
 * @category HTML Library
 * @package interface
 * @author Sebastian Winterhalder <sebi@concastic.ch>
 * @copyright 2007 Concastic
 * @license http://www.gnu.org/copyleft/gpl.html.
 * @version Release: @package_version@
 * @since Class available since Release 1.0.0
 *       
 */
class htmlib_attr_cellspacing extends htmlib_attribute implements htmlib_Iattr_cellspacing {
	
	/**
	 * Constructor
	 *
	 * @param Integer $spacing        	
	 */
	public function __construct($spacing = -1) {
		$this->setCellspacing ( $spacing );
	}
	
	/**
	 * Gibt das Cellspacing Attribut zur�ck
	 *
	 * @return unknown
	 */
	public function getAttribute() {
		if ($this->getCellspacing () != - 1)
			return ' cellspacing="' . $this->attribute . '"';
		else
			return '';
	}
	
	/**
	 * Setze Cellspacing (Integer)
	 * Keine Ausgabe -> $spacing = -1
	 *
	 * @param Integer $spacing        	
	 */
	public function setCellspacing($spacing) {
		if (gettype ( $spacing ) != "integer")
			throw new \Exception ( 'Wrong argument type passed. Argument type must be String' );
		if ($spacing < 0 && $spacing != - 1)
			throw new \Exception ( 'Wrong argument passed. Argument must be greater zero' );
		$this->attribute = $spacing;
	}
	
	/**
	 * Gibt Cellspacing zur�ck
	 * Falls kein Cellspacing angegeben ist die Ausgabe -1
	 *
	 * @return Integer
	 */
	public function getCellspacing() {
		return $this->attribute;
	}
}
?>