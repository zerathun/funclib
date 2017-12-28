<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes;

/**
 * Attribute Factory Class.
 * Every Entity does manage his attributes with that factory
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
 * Attribute Factory Class.
 * Every Entity does manage his attributes with that factory
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
class htmlib_attributeFactory {
	private $attributes = array ();
	
	/**
	 * Gibt true zurï¿½ck, wenn ein Objekt gleichen Typs bereits hinzugefï¿½gt wurde
	 * D.h.
	 * das bestehende Objekt wurde durch das neue ersetzt
	 *
	 * @param Object $attribute        	
	 * @return Boolean
	 */
	public function addAttribute($attribute) {
		if (! ($attribute instanceof htmlib_attribute))
			throw new \Exception ( 'The Argument requires an instance or child of attribute. Wrong Object Type passed.' );
		$classname = get_class ( $attribute );
		foreach ( $this->attributes as $key => $listElement ) {
			if ($listElement instanceof $classname) {
				$this->attributes [$key] = $attribute;
				return true;
			}
		}
		$this->attributes [] = $attribute;
		return false;
	}
	
	/**
	 * Create attribute String
	 *
	 * @return String
	 */
	public function getAttributes() {
		$result = '';
		foreach ( $this->attributes as $attribute ) {
			$result .= $attribute->getAttribute ();
		}
		return $result;
	}
	
	/**
	 * Gibt das gewünschte Objekt zurück, falls vorhanden.
	 * Wenn nicht vorhanden
	 *
	 * @param unknown_type $attributeClass        	
	 * @return unknown
	 */
	public function getAttribute($attributeClass) {
		foreach ( $this->attributes as $key => $listElement ) {
			if ($listElement instanceof $attributeClass)
				return $this->attributes [$key];
		}
		return false;
	}
}
?>