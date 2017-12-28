<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

class GlVar {
	private $timestamp;
	private $key;
	private $value;
	function __construct($key) {
		$this->setKey ( $key );
	}
	private function setKey($key) {
		if (! is_string ( $key ) && strlen ( $key ) > 3)
			throw new \Exception ( "No string given or Key with less than 3 letters" );
		$this->key = $key;
	}
	public function getKey() {
		return $this->key;
	}
	public function setValue($value) {
		if (is_string ( $value ) || is_array ( $value ) || is_int ( $value )) {
			$this->value = $value;
			return $value;
		} else {
			throw new \Exception ( "Unallowed Value: " . get_class ( $value ) . ", " . $this->key );
		}
	}
	public function setTs($ts) {
		$this->timestamp = intval ( $ts );
	}
	public function getValue() {
		return $this->value;
	}
}
?>