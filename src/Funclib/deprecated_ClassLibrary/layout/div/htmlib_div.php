<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\div;

use zeradun\api_manager\includes\Ember\ClassLibrary\iface\table\htmlib_parent;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_layout;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_height;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_width;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_content;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_height;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_width;

/**
 * Interface Alt
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
 * Interface alt
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
class htmlib_div extends htmlib_parent implements htmlib_I_layout, htmlib_Iattr_height, htmlib_Iattr_width, htmlib_I_content {
	public function __construct() {
		parent::__construct ();
	}
	
	/**
	 * Setze Tabellen Breite.
	 * Zahl oder 0%-100% (String)
	 *
	 * @param unknown_type $height        	
	 */
	public function setHeight($height) {
		$this->attributes->addAttribute ( new htmlib_attr_height ( $height ) );
	}
	
	/**
	 * Get Height
	 *
	 * @return Object
	 */
	public function getHeight() {
		return $this->attributes->getAttribute ( 'htmlib_attr_height' );
	}
	
	/**
	 * Setze Tabellen Breite.
	 * Zahl oder 0%-100% (String)
	 *
	 * @param Mixed $width        	
	 */
	public function setWidth($width) {
		$this->attributes->addAttribute ( new htmlib_attr_width ( $width ) );
	}
	
	/**
	 * Get Width
	 *
	 * @return Object
	 */
	public function getWidth() {
		return $this->attributes->getAttribute ( 'htmlib_attr_width' );
	}
	
	/**
	 * Content
	 *
	 * @param Mixed $content        	
	 */
	public function setContent($content) {
		$this->content = $content;
	}
	public function getContent() {
		return $this->content;
	}
	
	/**
	 * Gibt die generierte Tabelle zurück
	 *
	 * @return String
	 */
	public function getElementRendered() {
		$this->appendString ( "<div" );
		$this->appendString ( $this->attributes->getAttributes () );
		$this->appendString ( ">" );
		$this->appendString ( $this->getContent () );
		$this->appendString ( "</div>\n" );
		return $this->getResult ();
	}
}
?>