<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateElement;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\EList;

class TemplateReader extends EList implements Displayable {
	private $start_delimiter = "###";
	private $end_delimiter = "###";
	private $template = "";
	private $original_template = "";
	function __construct($file = "") {
		if (strlen ( $file ) > 0) {
			$this->readFile ( $file );
		}
	}
	public function readFile($filename) {
		if (! @file_exists ( $filename )) {
			throw new \Exception ( "$filename does not exist" );
		}
		$handle = fopen ( $filename, 'r' );
		
		$c = 0;
		$currentField = false; // Flags
		$subTemplate = false; // Flags
		$file_tmp_d = "";
		while ( ! feof ( $handle ) ) {
			$buffer = fgets ( $handle, 4096 );
			
			$keys = $this->parseKey ( $buffer );
			$file_tmp_d .= $buffer;
			
			$error_handler = new ErrorHandler ();
			if ($keys) {
				foreach ( $keys as $keys1 ) {
					for($x = 0; $x < sizeof ( $keys1 ); $x ++) {
						$TmplElement = $this->getNewTemplateElement ( $keys1 [$x] );
						
						if ($this->isUniqueListItem ( $TmplElement )) {
							$this->addItem ( $TmplElement );
						} else {
							$error_handler->addError ( "The key: " . $keys1 [$x] . " is double in the Template" );
						}
					}
				}
			}
			$file_tmp_d = $error_handler->getListRendered () . $file_tmp_d;
		}
		$this->original_template = $file_tmp_d;
		$this->template = $file_tmp_d;
		fclose ( $handle );
	}
	
	private function getNewTemplateElement($key) {
		$TmplElement = new TemplateElement ( $key );
		return $TmplElement;
	}
	
	public function inputVariable($key, $content) {
		$list = $this->getList ();
		$comparision = $this->getNewTemplateElement ( $key );
		$this->resetListIndex ();
		$Variable = null;
		
		$list = $this->getList ();
		foreach ( $list as $listitem ) {
			if ($listitem->getKey () == $comparision->getKey ()) {
				$Variable = $listitem;
				break;
			}
		}
		if ($Variable != NULL) {
			$Variable->setContent ( $content );
		} else {
			throw new \Exception ( "Content Variable does not Exist: $key" );
		}
	}
	public function getElement($key) {
		$iterator = $this->getIterator ();
		while ( $iterator->current () ) {
			if ($iterator->current ()->getKey () == $key)
				return $iterator->current ();
			$iterator->next ();
		}
		throw new \Exception ( "Item with $key could not be found in list" );
	}
	public function finalizeOutput() {
		$list = $this->getList ();
		foreach ( $list as $listitem ) {
			$search_string = '(' . $this->start_delimiter . $listitem->getKey () . $this->end_delimiter . ')';
			$this->template = preg_replace ( $search_string, $listitem->getContent (), $this->template );
		}
	}
	public function getOutput() {
		return $this->template;
	}
	private function addTemplateObject(TemplateElement $tmpl_element) {
		if (! ($tmpl_element instanceof TemplateElement)) {
			throw new \Exception ("Wrong type added: ".getClass($tmpl_element)." should be TemplateElement");
		}
	}
	
	/**
	 * parse Key
	 *
	 * @param String $line        	
	 * @return Mixed
	 */
	private function parseKey($line) {
		// Parse Standard Keys
		$matches = $result = array ();
		if (preg_match_all ( '(' . $this->start_delimiter . '[A-Za-z0-9_]{1,}' . $this->end_delimiter . ')', $line, $matches ) > 0) {
			for($x = 0; $x < sizeof ( $matches ); $x ++) {
				$result [$x] = str_replace ( "###", "", $matches [$x] );
			}
			return $result;
		}
		return false;
	}
	
	/**
	 * isDelimiter
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isDelimiter($key) {
		return ($this->isLayout ( $key ) || $this->isSpecialTemplate ( $key ) || $this->isForm ( $key ) || $this->isSubTemplate ( $key ) || $this->isNavi ( $key ));
	}
	
	/**
	 * isStart ($key)
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isStart($key) {
		$res = ($this->isDelimiter ( $key ) && ! $this->isOutput ( $key ) && ! ( bool ) preg_match ( "(END_)", $key ));
		return $res;
	}
	
	/**
	 * isEnd ($key)
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isEnd($key) {
		return ($this->isDelimiter ( $key ) && ! $this->isOutput ( $key ) && ( bool ) preg_match ( "(END_)", $key ));
	}
	
	/**
	 * Checks if the given key is a layout key
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isLayout($key) {
		if (preg_match ( '(LAYOUT_)', $key ))
			return true;
		else
			return false;
	}
	
	/**
	 * Checks if the given key is a Form Key
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isForm($key) {
		if (preg_match ( '(FORM_)', $key ))
			return true;
		else
			return false;
	}
	
	/**
	 * Checks if the given key is a Navi Key
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isNavi($key) {
		if (preg_match ( '(NAVI_)', $key ))
			return true;
		else
			return false;
	}
	private function isSpecialTemplate($key) {
		if (preg_match ( '(\[[A-Za-z0-1_-]{1,}\])', $key, $treffer )) {
			return $treffer;
		} else
			return false;
	}
	
	/**
	 * isSubTemplate $key
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isSubTemplate($key) {
		if (preg_match ( '(SUBEL_)', $key )) {
			return true;
		} else
			return false;
	}
	
	/**
	 * is Output ($key)
	 *
	 * @param String $key        	
	 * @return Boolean
	 */
	private function isOutput($key) {
		if (preg_match ( '(OUTPUT_)', $key ))
			return true;
		else
			return false;
	}
	
	/**
	 * get Form Type with Key
	 *
	 * @param String $key        	
	 * @return String
	 */
	private function getFormType($key) {
		if ($this->isLayout ( $key ))
			$result = str_replace ( 'LAYOUT_', '', $key );
		if ($this->isForm ( $key ))
			$result = str_replace ( 'FORM_', '', $key );
		if ($this->isNavi ( $key ))
			$result = str_replace ( 'NAVI_', '', $key );
		if ($this->isSubTemplate ( $key ))
			$result = str_replace ( 'SUBEL_', '', $key );
		if ($this->isOutput ( $key ))
			$result = str_replace ( 'OUTPUT_', '', $key );
		return $result;
	}
	
	/**
	 * getElement Type
	 *
	 * @param String $key        	
	 * @return String
	 */
	private function getElementType($key) {
		if ($this->isLayout ( $key ))
			return 'layout';
		if ($this->isForm ( $key ))
			return 'form';
		if ($this->isNavi ( $key ))
			return 'navi';
		if ($this->isSubTemplate ( $key ))
			return 'subel';
		if ($this->isOutput ( $key ))
			return 'output';
		return 'x';
	}
	private function getSpecialType($key) {
		if ($this->isSpecialTemplate ( $key )) {
			preg_match ( '(\[[A-Za-z0-1_-]{1,}\])', $key, $match );
			$str = str_replace ( "[", "", $match [0] );
			$str = str_replace ( "]", "", $str );
			return $str;
		} else {
			return false;
		}
	}
	
	/**
	 * Reset to the initial template with empty variables
	 */
	public function clearTemplateContentVars() {
		// $this->purgeList();
		$this->template = ( string ) $this->original_template;
	}
}

?>