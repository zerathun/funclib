<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\table;

use zeradun\api_manager\includes\Ember\ClassLibrary\iface\table\htmlib_parent;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_layout;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_height;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_width;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_cellpadding;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_cellspacing;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_summary;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_border;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_summary;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_height;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_width;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_border;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_cellpadding;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_cellspacing;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Attribute Factory Class.
 * Every Entity does manage his attributes with that factory
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

/**
 * htmlib_table
 *
 * Supports creating a table
 *
 * @version 1.0
 * @author Sebastian Winterhalder (sebi@concastic.ch)
 *        
 */
class htmlib_table extends htmlib_parent implements htmlib_I_layout, htmlib_Iattr_height, htmlib_Iattr_width, htmlib_Iattr_cellpadding, htmlib_Iattr_cellspacing, htmlib_Iattr_summary, htmlib_Iattr_border {
	private $table_rows = array ();
	
	// cell content
	// @deprecated
	private $cellContent;
	public function __construct() {
		parent::__construct ();
		$this->addAttribute ( new htmlib_attr_summary ( "none" ) );
	}
	public function setCell($row, $col, $content) {
		$this->cellContent [$row] [$col] ['content'] = $content;
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
	 * Setze Border
	 *
	 * @param Boolean $border        	
	 */
	public function setBorder($border) {
		$this->attributes->addAttribute ( new htmlib_attr_border ( $border ) );
	}
	
	/**
	 * Get Border
	 *
	 * @return Object
	 */
	public function getBorder() {
		return $this->attributes->getAttribute ( 'htmlib_attr_border' );
	}
	
	/**
	 * Setze Summary
	 *
	 * @param String $summary        	
	 */
	public function setSummary($summary) {
		$this->attributes->addAttribute ( new htmlib_attr_summary ( $summary ) );
	}
	
	/**
	 * Get Summary
	 *
	 * @return Object
	 */
	public function getSummary() {
		return $this->getAttribute ( 'htmlib_attr_summary' );
	}
	
	/**
	 * Setze Cellpadding
	 *
	 * @param int $cellpadding        	
	 */
	public function setCellpadding($cellpadding) {
		$this->addAttribute ( new htmlib_attr_cellpadding ( $cellpadding ) );
	}
	
	/**
	 * Gibt das Objekt von Cellpadding zur�ck
	 *
	 * @return Object
	 */
	public function getCellpadding() {
		return $this->getAttribute ( 'htmlib_attr_cellpadding' );
	}
	
	/**
	 * Setze Cellspacing (Integer)
	 *
	 * @param int $cellspacing        	
	 */
	public function setCellspacing($cellspacing) {
		$this->addAttribute ( new htmlib_attr_cellspacing ( $cellspacing ) );
	}
	
	/**
	 * Gibt das Objekt von Cellspacing zur�ck
	 *
	 * @return Object
	 */
	public function getCellspacing() {
		return $this->getAttribute ( 'htmlib_attr_cellspacing' );
	}
	
	/**
	 * Ads a row of type htmlib_tr to the Row List of the Table
	 * Returns True
	 *
	 * @param htmlib_tr $row        	
	 * @return Boolean
	 */
	public function addRow($row) {
		if ($row == false || empty ( $row )) {
			return false;
		}
		if ($row instanceof htmlib_tr) {
			$this->table_rows [] = $row;
			return true;
		} else {
			throw new \Exception ( 'Given Argument is Invalid. Row Object htmlib_tr expected.' );
		}
	}
	public function getRow($index) {
		return $this->table_rows [$index];
	}
	
	/**
	 * Gibt die generierte Tabelle zur�ck
	 *
	 * @return String
	 */
	public function getElementRendered() {
		$this->setTableHeader ();
		
		foreach ( $this->table_rows as $row ) {
			$this->appendString ( $row->getElementRendered () );
		}
		
		$this->setTableFooter ();
		return $this->getResult ();
	}
	
	/**
	 * Create <table [attributes]>
	 * Set the Attributes before creating the table
	 */
	private function setTableHeader() {
		$this->appendString ( "\n<!-- Layout Generator Table -->\n<table" );
		$this->appendString ( $this->attributes->getAttributes () );
		$this->appendString ( ">\n" );
	}
	private function setTableFooter() {
		$this->appendString ( "</table>\n<!-- Layout Generator Table End -->\n" );
	}
}

?>
