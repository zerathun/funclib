<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\form;

use zeradun\api_manager\includes\Ember\ClassLibrary\iface\table\htmlib_parent;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_layout;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_action;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_accept;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_enctype;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_acceptcharset;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_method;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_name;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_onreset;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_onsubmit;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_target;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_content;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_name;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_value;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
class htmlib_htmlform extends htmlib_parent implements htmlib_I_layout, htmlib_Iattr_action, htmlib_Iattr_accept, htmlib_Iattr_enctype, htmlib_Iattr_acceptcharset, htmlib_Iattr_method, htmlib_Iattr_name, htmlib_Iattr_onreset, htmlib_Iattr_onsubmit, htmlib_Iattr_target, htmlib_I_content {
	public function __construct() {
		parent::__construct ();
	}
	
	/**
	 * Gibt die generierte Tabelle zur�ck
	 *
	 * @return String
	 */
	public function getElementRendered() {
		$this->resetString ();
		$this->appendString ( "<form" );
		$this->appendString ( $this->attributes->getAttributes () );
		$this->appendString ( ">\n" );
		$this->appendString ( $this->getContent () );
		$this->appendString ( '</form>' );
		return $this->getResult ();
	}
	public function getStartElement() {
		$this->resetString ();
		$this->appendString ( "<form" );
		$this->appendString ( $this->attributes->getAttributes () );
		$this->appendString ( ">" );
		return $this->getResult ();
	}
	public function getEndElement() {
		$this->resetString ();
		$this->appendString ( '</form>' );
		return $this->getResult ();
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
	public function setName($name) {
		$this->addAttribute ( new htmlib_attr_name ( $name ) );
	}
	public function getName() {
		return $this->getAttribute ( 'htmlib_attr_name' );
	}
	public function setAction($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getAction() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setAccept($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getAccept() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setEnctype($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getEnctype() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setAcceptcharset($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getAcceptcharset() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setMethod($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getMethod() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setOnreset($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getOnreset() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setOnsubmit($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getOnsubmit() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
	public function setTarget($value) {
		$this->addAttribute ( new htmlib_attr_value ( $value ) );
	}
	public function getTarget() {
		return $this->getAttribute ( 'htmlib_attr_value' );
	}
}

?>