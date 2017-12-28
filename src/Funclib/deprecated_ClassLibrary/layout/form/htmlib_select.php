<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\form;

use zeradun\api_manager\includes\Ember\ClassLibrary\iface\table\htmlib_parent;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_layout;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_multipleContent;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_disabled;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_multiple;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_name;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_onblur;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_onchange;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_onfocus;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_size;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_tableindex;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_disabled;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_multiple;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_name;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_onblur;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_onchange;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_onfocus;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_size;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_tableindex;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Interface Alt
 *
 *
 * PHP versions 5
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
class htmlib_select extends htmlib_parent implements htmlib_I_layout, htmlib_I_multipleContent, htmlib_Iattr_disabled, htmlib_Iattr_multiple, htmlib_Iattr_name, htmlib_Iattr_onblur, htmlib_Iattr_onchange, htmlib_Iattr_onfocus, htmlib_Iattr_size, htmlib_Iattr_tableindex {
	protected $option_elements = array ();
	public function __construct() {
		parent::__construct ();
	}
	
	/**
	 * Gibt die generierte Tabelle zurück
	 *
	 * @return String
	 */
	public function getElementRendered() {
		$this->resetString ();
		$this->appendString ( "<select" );
		$this->appendString ( $this->attributes->getAttributes () );
		$this->appendString ( ">\n" );
		foreach ( $this->option_elements as $key => $element ) {
			$this->appendString ( $element->getElementRendered () );
		}
		$this->appendString ( "</select>\n" );
		return $this->getResult ();
	}
	public function addContent($arg) {
		if (! $arg instanceof htmlib_option)
			throw new \Exception ( 'The given Argument does not match the type. htmlib_option expected' );
		$this->option_elements [] = $arg;
	}
	public function getElements() {
		return $this->option_elements;
	}
	public function getContent() {
		return implode ( "\n", $this->option_elements );
	}
	
	/* Interface Implementation */
	public function setDisabled($boolean) {
		$this->addAttribute ( new htmlib_attr_disabled ( $boolean ) );
	}
	public function getDisabled() {
		return $this->getAttribute ( 'htmlib_attr_disabled' );
	}
	public function setMultiple($boolean) {
		$this->addAttribute ( new htmlib_attr_multiple ( $boolean ) );
	}
	public function getMultiple() {
		return $this->getAttribute ( 'htmlib_attr_multiple' );
	}
	public function setName($name) {
		$this->addAttribute ( new htmlib_attr_name ( $name ) );
	}
	public function getName() {
		return $this->getAttribute ( 'htmlib_attr_name' );
	}
	public function setOnblur($onblur) {
		$this->addAttribute ( new htmlib_attr_onblur ( $onblur ) );
	}
	public function getOnblur() {
		return $this->getAttribute ( 'htmlib_attr_onblur' );
	}
	public function setOnchange($onchange) {
		$this->addAttribute ( new htmlib_attr_onchange ( $onchange ) );
	}
	public function getOnchange() {
		return $this->getAttribute ( 'htmlib_attr_onchange' );
	}
	public function setOnfocus($onfocus) {
		$this->addAttribute ( new htmlib_attr_onfocus ( $onfocus ) );
	}
	public function getOnfocus() {
		return $this->getAttribute ( 'htmlib_attr_onfocus' );
	}
	public function setSize($size) {
		$this->addAttribute ( new htmlib_attr_size ( $size ) );
	}
	public function getSize() {
		return $this->getAttribute ( 'htmlib_attr_size' );
	}
	public function setTableindex($tableindex) {
		$this->addAttribute ( new htmlib_attr_tableindex ( $tableindex ) );
	}
	public function getTableindex() {
		return $this->getAttribute ( 'htmlib_attr_tableindex' );
	}
}
?>