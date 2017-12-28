<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\sysObj;

class ParseVariable extends sysObj {
	private $ID;
	private $type;
	private $arrayKey;
	private $origString;
	private $formElement;
	private $uidExists = false;
	public function __construct($ID) {
		$this->setID ( $ID );
	}
	public function setID($ID) {
		$this->ID = $ID;
	}
	public function getID() {
		return $this->ID;
	}
	public function setType($type) {
		$this->type = $type;
	}
	public function getType() {
		return $this->type;
	}
	public function setArrayKey($arrayKey) {
		$this->arrayKey = $arrayKey;
	}
	public function getArrayKey() {
		return $this->arrayKey;
	}
	public function setOriginalString($string) {
		$this->origString = $string;
	}
	public function getOriginalString() {
		return $this->origString;
	}
	public function getValue() {
		return $this->getOriginalString ();
	}
}

?>