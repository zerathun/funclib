<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes;

use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_cssclass;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class Cssclass
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
 * Class Cssclass
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
class htmlib_attr_cssclass extends htmlib_attribute implements htmlib_Iattr_cssclass {
	
	/**
	 * Constructor
	 *
	 * @param String $class        	
	 */
	public function __construct($class = -1) {
		$this->addCssClass ( $class );
	}
	
	/**
	 * Gibt das Cellspacing Attribut zur�ck
	 *
	 * @return unknown
	 */
	public function getAttribute() {
		$classes = $this->getCssClasses ();
		if (count ( $classes ) > 0) {
			$res = ' class="';
			for($x = 0; $x < count ( $classes ); $x ++) {
				if ($x > 0)
					$res .= " ";
				$res .= $classes [$x];
			}
			return $res . '"';
		} else
			return '';
	}
	
	/**
	 * Setze Klassen (String)
	 * Keine Ausgabe -> $spacing = -1
	 *
	 * @param String $class        	
	 */
	public function addCssClass($class) {
		if (gettype ( $class ) != "string" && $class != - 1)
			throw new \Exception ( 'Wrong argument type passed. Argument type must be String' );
		if (strlen ( $class <= 0 )) {
		}
		
		if ($class == - 1)
			$this->removeClasses ();
		elseif ($this->attribute == - 1)
			if (strlen ( $class ) > 0)
				$this->attribute = array (
						$class 
				);
			else {
				if (strlen ( $class ) > 0)
					$this->attribute [] = $class;
			}
	}
	
	/**
	 * Deletes all Defined Classes in this Object
	 */
	public function removeClasses() {
		$this->attribute = array ();
	}
	
	/**
	 * Gibt Alle CSS Klassen zur�ck
	 * Falls keine Klassen angegeben ist die Ausgabe ein leeres Array
	 *
	 * @return Array
	 */
	public function getCssClasses() {
		return $this->attribute;
	}
}
?>