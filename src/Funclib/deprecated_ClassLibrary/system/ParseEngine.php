<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\sysObj;

class ParseEngine extends sysObj {
	
	/**
	 * *************************************************************
	 * Copyright notice
	 *
	 * (c) 2007 Sebastian Winterhalder <sw@internetgalerie.ch>
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
	 * *************************************************************
	 */
	protected $data;
	protected $keyDefinition = '(ID_[A-Za-z0-9]{5,12})'; // Regular Expression
	protected $typeDefinition = '(ID_|VAR_)';
	protected $arrayKeyDef = '(\[[A-Za-z0-9_]{1,60}\])';
	protected $OR = '||';
	protected $AND = "&&";
	protected $EQUAL = "==";
	protected $OPERAND = "[A-Za-z0-9_()\']{0,100}";
	protected $variableList = array ();
	public function __construct() {
	}
	
	/**
	 * Load a String which shall be parsed, and parse the data
	 *
	 * @param unknown_type $string        	
	 */
	public function loadData($string) {
		$this->data = $string;
		$this->parse ();
	}
	
	/**
	 * This method will Replace all Variable with the wanted content
	 */
	private function parse() {
		$this->parseBoolean ();
		$this->parseText ();
	}
	
	/**
	 * Returns the Parsed Data.
	 * If it contains a Boolean it will be 0 or 1
	 *
	 * @return Mixed
	 */
	public function getParsed() {
		return $this->data;
	}
	
	/**
	 * function of Parsing Boolean in Strings
	 *
	 * @param unknown_type $data        	
	 */
	private function parseBoolean() {
		$rex = '/\([a-zA-Z0-9_ -&=#|]{0,100}\)/';
		
		while ( preg_match ( $rex, $this->data, $matches ) ) {
			$this->data = str_replace ( $matches [0], $this->operate ( $matches [0] ), $this->data );
		}
	}
	
	/**
	 * Boolean Operator
	 *
	 * @param String $string        	
	 * @return String
	 */
	private function operate($string) {
		$string = $this->removeChar ( "(", $string ); // Remove First bracket
		$string = $this->removeChar ( ")", $string, false ); // Remove last bracket
		
		$splitArr = preg_split ( '/[ ]{0,100}' . $this->EQUAL . '[ ]{0,100}/', $string );
		if (count ( $splitArr ) > 1) {
			if ($this->getBoolValue ( $splitArr [0] ) === $this->getBoolValue ( $splitArr [1] )) {
				return 1;
			} else {
				return 0;
			}
		}
		
		$splitArr = preg_split ( '/[ ]{0,100}' . $this->AND . '[ ]{0,100}/', $string );
		if (count ( $splitArr ) > 1) {
			for($x = 0; $x < sizeof ( $splitArr ); $x ++) {
				if ($this->getBoolValue ( $splitArr [$x] ) == "0") {
					return "0";
				}
			}
			return '1';
		}
		
		$splitArr = preg_split ( '/[ ]{0,100}' . $this->OR . '[ ]{0,100}/', $string );
		if (count ( $splitArr ) > 1) {
			for($x = 0; $x < sizeof ( $splitArr ); $x ++) {
				if ($this->getBoolValue ( $splitArr [$x] ) != "0") {
					return "1";
				}
			}
			return '0';
		}
		
		return $string;
	}
	private function removeChar($char, $string, $first = true) {
		assert ( strlen ( $char ) == 1 );
		if ($first) {
			$res = '';
			$flag = false;
			for($x = 0; $x < strlen ( $string ); $x ++) {
				if ($string [$x] == $char && ! $flag) {
					$flag = true;
				} else {
					$res .= $string [$x];
				}
			}
			return $res;
		} else {
			$res = array ();
			$flag = false;
			for($x = strlen ( $string ) - 1; $x >= 0; $x --) {
				if ($string [$x] == $char && ! $flag) {
					$flag = true;
				} else {
					$res [$x] = $string [$x];
				}
			}
			ksort ( $res );
			return implode ( "", $res );
		}
	}
	private function getBoolValue($string) {
		$variableList = $this->getVariableList ( $string );
		
		foreach ( $variableList as $varObj ) {
			$string = str_replace ( $varObj->getOriginalString (), $varObj->getValue (), $string );
		}
		
		$string = trim ( $string );
		
		if ($string == "0" || $string == "false" || $string == "") {
			return 0;
		} else {
			return $string;
		}
		
		return $string;
	}
	
	/**
	 * Get a List of Variables of a String
	 *
	 * @param String $string        	
	 * @return Array
	 */
	private function getVariableList($string) {
		$treffer = array ();
		$variableList = array ();
		preg_match_all ( '/###[A-Za-z0-9_]{1,100}[\[]{0,1}[A-Za-z0-9_-]{1,50}[\]]{0,1}###/', $string, $treffer );
		
		foreach ( $treffer [0] as $tref ) {
			$obj = new ParseVariable ( $this->getElementID ( $tref ) );
			
			$obj->setType ( $this->getType ( $tref ) );
			$obj->setArrayKey ( $this->getArrayKey ( $tref ) );
			$obj->setOriginalString ( $tref );
			
			$variableList [] = $obj;
		}
		return $variableList;
	}
	
	/**
	 * parse Key
	 *
	 * @param String $line        	
	 * @return Mixed
	 */
	private function parseText() {
		/*
		 * preg_match_all('(###[A-Za-z0-9_]{1,100}[\[]{0,1}[A-Za-z0-9_-]{1,50}[\]]{0,1}###)', $this->data, $treffer);
		 *
		 * foreach($treffer[0] as $tref) {
		 * $obj = new tx_mailform_parseVariable($this->getElementID($tref));
		 * $obj->setType($this->getType($tref));
		 * $obj->setArrayKey($this->getArrayKey($tref));
		 * $obj->setOriginalString($tref);
		 *
		 * $this->variableList[] = $obj;
		 * }
		 */
		$variableList = $this->getVariableList ( $this->data );
		
		foreach ( $variableList as $varObj ) {
			$this->data = str_replace ( $varObj->getOriginalString (), $varObj->getValue (), $this->data );
		}
	}
	public function getElementKeys() {
	}
	public function getElementID($elementString) {
		preg_match ( $this->keyDefinition, $elementString, $treffer );
		
		$result = preg_replace ( $this->typeDefinition, "", $treffer [0] );
		
		return $result;
	}
	public function getType($elementString) {
		preg_match ( $this->typeDefinition, $elementString, $treffer );
		
		return $treffer [0];
	}
	public function getArrayKey($elementString) {
		preg_match ( $this->arrayKeyDef, $elementString, $treffer );
		
		$res = str_replace ( "[", "", $treffer [0] );
		$res = str_replace ( "]", "", $res );
		
		return $res;
	}
}

?>