<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes;

use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_style;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class Style
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
 * Class Style
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
class htmlib_attr_style extends htmlib_attribute implements htmlib_Iattr_style {
	
	/**
	 * Constructor
	 *
	 * @param String $style        	
	 */
	public function __construct($style = -1) {
		$this->addStyle ( $style );
	}
	
	/**
	 * Gibt das Cellspacing Attribut zur�ck
	 *
	 * @return unknown
	 */
	public function getAttribute() {
		$styles = $this->getStyles ();
		if (count ( $styles ) > 0) {
			$res = ' style="';
			for($x = 0; $x < count ( $styles ); $x ++) {
				if ($x > 0)
					$res .= " ";
				$res .= $styles [$x];
			}
			return $res . '"';
		} else
			return '';
	}
	
	/**
	 * F�ge Style hinzu (String)
	 * Keine Ausgabe -> $spacing = -1
	 *
	 * @param String $style        	
	 */
	public function addStyle($style) {
		if (gettype ( $style ) != "string" && $style != - 1)
			throw new \Exception ( 'Wrong argument type passed. Argument type must be String' );
		if (strlen ( $style ) <= 0)
			throw new \Exception ( 'Invalid Parameter. class.addStyle requires a String length greater than zero' );
		if ($style == - 1)
			$this->removeStyles ();
		elseif ($this->attribute == - 1)
			$this->attribute = array (
					$style 
			);
		else
			$this->attribute [] = $style;
	}
	
	/**
	 * Deletes all Defined Styles in this Object
	 */
	public function removeStyles() {
		$this->attribute = array ();
	}
	
	/**
	 * Gibt alle Styles zur�ck
	 * Falls keine Styles angegeben ist die Ausgabe ein leeres Array
	 *
	 * @return Array
	 */
	public function getStyles() {
		return $this->attribute;
	}
}
?>