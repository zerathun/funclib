<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes;

use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_rowspan;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class rowspan
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
 * Class rowspan
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
class htmlib_attr_rowspan extends htmlib_attribute implements htmlib_Iattr_rowspan {
	
	/**
	 * Constructor
	 *
	 * @param Integer $rowspan        	
	 */
	public function __construct($rowspan = -1) {
		$this->setRowspan ( $rowspan );
	}
	
	/**
	 * Gibt das Rowspan Attribut zur�ck
	 *
	 * @return unknown
	 */
	public function getAttribute() {
		if ($this->getRowspan () != - 1)
			return ' rowspan="' . $this->attribute . '"';
		else
			return '';
	}
	
	/**
	 * Setze Rowspan (Integer)
	 * Keine Ausgabe -> $rowspan = -1
	 *
	 * @param Integer $rowspan        	
	 */
	public function setRowspan($rowspan) {
		if (gettype ( $rowspan ) != "integer")
			throw new \Exception ( 'Wrong argument type passed. Argument type must be an Integer' );
		if ($rowspan <= 0 && $rowspan != - 1)
			throw new \Exception ( 'Wrong argument passed. Argument must be greater zero' );
		$this->attribute = $rowspan;
	}
	
	/**
	 * Gibt Rowspan zur�ck
	 * Falls kein Rowspan angegeben ist die Ausgabe -1
	 *
	 * @return Integer
	 */
	public function getRowspan() {
		return $this->attribute;
	}
}

?>