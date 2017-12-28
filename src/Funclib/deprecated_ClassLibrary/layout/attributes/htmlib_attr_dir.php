<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes;

use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_dir;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class Dir
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
 * Class Dir
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
class htmlib_attr_dir extends htmlib_attribute implements htmlib_Iattr_dir {
	public function __construct($dir = -1) {
		$this->setDir ( $dir );
	}
	
	/**
	 * Gibt das Dir Attribut zur�ck
	 *
	 * @return unknown
	 */
	public function getAttribute() {
		if ($this->getDir () != - 1)
			return ' dir="' . $this->attribute . '"';
		else
			return '';
	}
	
	/**
	 * Setze Dir
	 * Keine Ausgabe -> $dir = -1
	 *
	 * @param String $dir        	
	 */
	public function setDir($dir) {
		if (strlen ( $dir ) <= 0 && $dir != - 1)
			throw new \Exception ( 'Wrong argument passed. Stringlength must be greater than zero or -1' );
		$this->attribute = $dir;
	}
	
	/**
	 * Gibt Dir zur�ck
	 * Falls Dir angegeben ist die Ausgabe -1
	 *
	 * @return Mixed
	 */
	public function getDir() {
		return $this->attribute;
	}
}
?>