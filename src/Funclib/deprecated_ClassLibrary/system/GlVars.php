<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

session_start ();
class GlVars {
	
	protected static $GlVarsObj;
	private $varList;
	
	private function __construct() {
		if (empty ( $_SESSION ["glVars"] )) {
			$_SESSION ['glVars'] = array ();
		}
		$this->varList = array ();
		$this->parseVars ();
	}
	
	public function parseVars() {		
		foreach ( $_SESSION ["glVars"] as $key => $var ) {
			$varObj = new GlVar ( $key );
			try {
				$varObj->setValue ( $var );
			} catch ( \Exception $e ) {
				unset ( $_SESSION ["glVars"] [$key] );
			}
			$this->varList [$varObj->getKey ()] = $varObj;
		}
	}
	
	public function getVar($arg) {
		if (! empty ( $this->varList [$arg] ) && ($this->varList [$arg] instanceof glVar))
			return $this->varList [$arg];
		else {
			if (! empty ( $_SESSION [$arg] )) {
				$this->parseVars ();
				if ($this->varList [$arg] instanceof glVar)
					return $this->varList [$arg];
				else {
					return new glVar ();
				}
			} else {
				return null;
			}
		}
	}
	
	public static function getGlVars() {
		if (empty ( GlVars::$GlVarsObj )) {
			GlVars::$GlVarsObj = new GlVars ();
		}
		return GlVars::$GlVarsObj;
	}
	
	public function unsetVar($arg) {
		unset ( $_SESSION ['glVars'] [$arg] );
		unset ( $this->varList [$arg] );
		$this->parseVars ();
	}
	
	public function setVar($key, $value, $refresh = true) {
		$_SESSION ['glVars'] [$key] = $value;
		if ($refresh) {
			$this->parseVars ();
		}
	}
	
	/**
	 * use this function to manage GET/POST etc. Variables
	 */
	public static function emb_request_var($var_name, $variable_type, $default_value = '') {
		if(defined("PROGRAM_MODE")) {
			if(PROGRAM_MODE == "PHPBB" || (defined('IN_PHPBB') && IN_PHPBB))
				return request_var($var_name, $default_value);
			else
				return $$variable_type[$var_name];
		}	else 
			throw new \Exception("PROGRAM MODE Constant is not defined");
	}

}

?>