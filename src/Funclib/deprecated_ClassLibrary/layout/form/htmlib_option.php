<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\form;

use zeradun\api_manager\includes\Ember\ClassLibrary\iface\table\htmlib_parent;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_layout;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_value;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_label;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_selected;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_disabled;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_content;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_value;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_label;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_selected;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_disabled;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class Option
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
 * Class Option
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
class htmlib_option extends htmlib_parent implements htmlib_I_layout, htmlib_Iattr_value, htmlib_Iattr_label, htmlib_Iattr_selected, htmlib_Iattr_disabled, htmlib_I_content {
	private $content;
	public function __construct() {
		parent::__construct ();
	}
	
	/**
	 * Gibt das Option Element zurück
	 *
	 * @return String
	 */
	public function getElementRendered() {
		$this->resetString ();
		$this->appendString ( "<option" );
		$this->appendString ( $this->attributes->getAttributes () );
		$this->appendString ( ">" );
		$this->appendString ( $this->getContent () );
		$this->appendString ( "</option>\n" );
		return $this->getResult ();
	}
	public function setContent($content) {
		$this->content = $content;
	}
	public function getContent() {
		return $this->content;
	}
	public function setValue($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getValue() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setLabel($label) {
		$this->addAttribute ( new htmlib_attr_label ( $label ) );
	}
	public function getLabel() {
		return $this->getAttribute ( 'htmlib_attr_label' );
	}
	public function setSelected($boolean) {
		$this->addAttribute ( new htmlib_attr_selected ( $boolean ) );
	}
	public function getSelected() {
		return $this->getAttribute ( 'htmlib_attr_selected' );
	}
	public function setDisabled($boolean) {
		$this->addAttribute ( new htmlib_attr_disabled ( $boolean ) );
	}
	public function getDisabled() {
		return $this->getAttribute ( 'htmlib_attr_disabled' );
	}
}
?>