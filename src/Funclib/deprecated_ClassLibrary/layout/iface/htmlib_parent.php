<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\iface\table;

use ClassLibrary\layout\iface\htmlib_I_layout;
use ClassLibrary\layout\iface\htmlib_Iattr_style;
use ClassLibrary\layout\iface\htmlib_Iattr_cssclass;
use ClassLibrary\layout\iface\htmlib_Iattr_id;
use ClassLibrary\layout\iface\htmlib_Iattr_lang;
use ClassLibrary\layout\iface\htmlib_Iattr_title;
use ClassLibrary\layout\iface\htmlib_Iattr_dir;
use ClassLibrary\layout\attributes\htmlib_attributeFactory;
use ClassLibrary\layout\attributes\htmlib_attr_cssclass;
use ClassLibrary\layout\attributes\htmlib_attr_style;
use ClassLibrary\layout\attributes\htmlib_attr_dir;
use ClassLibrary\layout\attributes\htmlib_attr_id;
use ClassLibrary\layout\attributes\htmlib_attr_lang;
use ClassLibrary\layout\attributes\htmlib_attr_title;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class Parent
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
 * Class Parent
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
abstract class htmlib_parent implements htmlib_I_layout, htmlib_Iattr_style, htmlib_Iattr_cssclass, htmlib_Iattr_id, htmlib_Iattr_lang, htmlib_Iattr_title, htmlib_Iattr_dir 

{
	protected $attributes;
	private $result = "";
	protected $comment;
	public function __construct() {
		$this->attributes = new htmlib_attributeFactory ();
	}
	
	/**
	 * Wrapper
	 *
	 * @param Object $attribute        	
	 */
	protected function addAttribute($attribute) {
		$this->attributes->addAttribute ( $attribute );
	}
	
	/**
	 * Get Attribute
	 *
	 * @param String $name        	
	 * @return Object
	 */
	protected function getAttribute($name) {
		return $this->attributes->getAttribute ( $name );
	}
	
	/**
	 * append a string to the cache
	 *
	 * @param unknown_type $string        	
	 */
	protected function appendString($string) {
		$this->result .= $string;
	}
	
	/**
	 * get the result
	 *
	 * @return unknown
	 */
	protected function getResult() {
		return $this->result;
	}
	public function setComment($comment) {
		$this->comment = $comment;
	}
	public function resetString() {
		$this->result = '';
	}
	
	/**
	 * Interface
	 */
	
	/**
	 * F�gt eine CSS Klasse hinzu
	 *
	 * @param String $class        	
	 */
	public function addCssClass($class) {
		if (! ($attr = $this->getAttribute ( 'htmlib_attr_cssclass' )))
			$this->attributes->addAttribute ( new htmlib_attr_cssclass ( $class ) );
		else {
			$attr->addCssClass ( $class );
			$this->attributes->addAttribute ( $attr );
		}
	}
	
	/**
	 * Get all classes in an array
	 *
	 * @return Array
	 */
	public function getCssClasses() {
		return $this->getAttribute ( 'htmlib_attr_cssclass' );
	}
	
	/**
	 * F�gt ein Style Element hinzu
	 *
	 * @param String $style        	
	 */
	public function addStyle($style) {
		if (! ($attr = $this->getAttribute ( 'htmlib_attr_style' )))
			$this->attributes->addAttribute ( new htmlib_attr_style ( $style ) );
		else {
			$attr->addStyle ( $style );
			$this->attributes->addAttribute ( $attr );
		}
	}
	
	/**
	 * Gibt alle Styles zur�ck
	 *
	 * @return Mixed
	 */
	public function getStyles() {
		return $this->getAttribute ( 'htmlib_attr_style' );
	}
	public function setDir($dir) {
		$this->addAttribute ( new htmlib_attr_dir ( $dir ) );
	}
	public function getDir() {
		return $this->getAttribute ( 'htmlib_attr_dir' );
	}
	public function setId($id) {
		$this->addAttribute ( new htmlib_attr_id ( $id ) );
	}
	public function getId() {
		return $this->getAttribute ( 'htmlib_attr_id' );
	}
	public function setLang($lang) {
		$this->addAttribute ( new htmlib_attr_lang ( $lang ) );
	}
	public function getLang() {
		return $this->getAttribute ( 'htmlib_attr_lang' );
	}
	public function setTitle($title) {
		$this->addAttribute ( new htmlib_attr_title ( $title ) );
	}
	public function getTitle() {
		$this->getAttribute ( 'htmlib_attr_title' );
	}
}
?>