<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
namespace zeradun\api_manager\includes\Ember\ClassLibrary\layout\table;

use zeradun\api_manager\includes\Ember\ClassLibrary\iface\table\htmlib_parent;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_I_layout;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_height;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_width;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_colspan;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_rowspan;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_align;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\iface\htmlib_Iattr_valign;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_height;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_width;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_align;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_valign;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_cssclass;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_style;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_rowspan;
use zeradun\api_manager\includes\Ember\ClassLibrary\layout\attributes\htmlib_attr_colspan;

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
 * Unterst�tzt das vollst�ndige erstellen einer Tabelle in OOP
 *
 * @version 1.0
 * @author Sebastian Winterhalder (sebi@concastic.ch)
 *        
 */
class htmlib_td extends htmlib_parent implements htmlib_I_layout, htmlib_Iattr_height, htmlib_Iattr_width, htmlib_Iattr_colspan, htmlib_Iattr_rowspan, htmlib_Iattr_align, htmlib_Iattr_valign {
	
	/**
	 * Setze die H�he Integer oder Prozent (String)
	 *
	 * @param Mixed $height        	
	 */
	public function setHeight($height) {
		$this->addAttribute ( new htmlib_attr_height ( $height ) );
	}
	
	/**
	 * Gibt die Höhe zurück
	 *
	 * @param Mixed $height        	
	 * @return int
	 */
	public function getHeight() {
		return $this->getAttribute ( 'htmlib_attr_height' );
	}
	
	/**
	 * Setze die Breite Integer oder Prozent (String)
	 *
	 * @param Mixed $width        	
	 */
	public function setWidth($width) {
		$this->addAttribute ( new htmlib_attr_width ( $width ) );
	}
	
	/**
	 * Gibt die aktuelle Breite zur�ck
	 *
	 * @return Mixed
	 */
	public function getWidth() {
		return $this->getAttribute ( 'htmlib_attr_width' );
	}
	
	/**
	 * Setze Align
	 *
	 * @param String $align        	
	 */
	public function setAlign($align) {
		$this->addAttribute ( new htmlib_attr_align ( $align ) );
	}
	
	/**
	 * Gibt align zurück
	 *
	 * @return Object
	 */
	public function getAlign() {
		return $this->getAttribute ( 'htmlib_attr_align' );
	}
	
	/**
	 * Setze Align
	 *
	 * @param String $align        	
	 */
	public function setValign($align) {
		$this->addAttribute ( new htmlib_attr_valign ( $align ) );
	}
	
	/**
	 * Gibt align zurück
	 *
	 * @return Object
	 */
	public function getValign() {
		return $this->getAttribute ( 'htmlib_attr_valign' );
	}
	
	/**
	 * Fügt eine CSS Klasse hinzu
	 *
	 * @param String $class        	
	 */
	public function addCssClass($class) {
		if (! ($attr = $this->attributes->getAttribute ( 'htmlib_attr_cssclass' )))
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
		return $this->attributes->getAttribute ( 'htmlib_attr_cssclass' );
	}
	
	/**
	 * F�gt ein Style Element hinzu
	 *
	 * @param String $style        	
	 */
	public function addStyle($style) {
		if (! ($attr = $this->attributes->getAttribute ( 'htmlib_attr_style' )))
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
		return $this->attributes->getAttribute ( 'htmlib_attr_style' );
	}
	
	/**
	 * Setze rowspan
	 *
	 * @param unknown_type $rowspan        	
	 */
	public function setRowspan($rowspan) {
		if (! $rowspan > 0)
			$rowspan = 1;
		
		$this->addAttribute ( new htmlib_attr_rowspan ( $rowspan ) );
	}
	public function getRowspan() {
		$this->getAttribute ( 'htmlib_attr_rowspan' );
	}
	
	/**
	 * Setze colspan
	 *
	 * @param int $colspan        	
	 */
	public function setColspan($colspan) {
		if (! $colspan > 0)
			$colspan = 1;
		
		$this->addAttribute ( new htmlib_attr_colspan ( $colspan ) );
	}
	public function getColspan() {
		$this->getAttribute ( 'htmlib_attr_colspan' );
	}
	
	/**
	 * Gibt das Generierte HTML Element aus
	 */
	public function getElementRendered() {
		if (! empty ( $this->comment ))
			$res = "<!-- Class TD: " . $this->comment . " -->\n";
		$res .= "<td";
		$res .= $this->attributes->getAttributes ();
		$res .= ">" . $this->content . "</td>\n";
		if (! empty ( $this->comment ))
			$res .= "\n<!-- END Class TD: " . $this->comment . " -->\n";
		return $res;
	}
	public function setContent($content) {
		$this->content = $content;
	}
}
?>
