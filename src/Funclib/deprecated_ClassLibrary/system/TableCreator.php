<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;

class TableCreator extends TemplateReader implements Displayable {
	private $column_count;
	private $inner_template;
	private $table_content;
	private $basic_array;
	
	public function __construct($columnCount) {
		$this->setColumnCount ( $columnCount );
	}
	
	/**
	 *
	 * @param array $path_array        	
	 */
	public function loadTemplateFiles(Array $path_array) {
		if (@file_exists ( $path_array ['outer_box'] )) {
			$this->readFile ( $path_array ['outer_box'] );
		}
		
		if (file_exists ( $path_array ['inner_box'] )) {
			$this->inner_template = new TemplateReader ();
			$this->inner_template->readFile ( $path_array ['inner_box'] );
		} else {
			print "File ".$path_array['inner_box']." not found";
			throw new \Exception();
		}
	}
	public function addContent($array, $class_arr = array()) {
		$this->basic_array [] = $array;
		$this->inner_template->clearTemplateContentVars();
		foreach ( $array as $key => $input ) {
			if (count ( $class_arr ) > 0) {
				$this->inner_template->inputVariable ( "CLASS_KEY_$key", $class_arr [$key] );
			} else {
				$this->inner_template->inputVariable ( "CLASS_KEY_$key", "" );
			}
			$this->inner_template->inputVariable ( "KEY_$key", $input );
		}
		$this->inner_template->finalizeOutput ();
		$this->table_content .= $this->inner_template->getOutput ();
	}
	public function finalizeOutput() {
		$this->inputVariable ( "TABLE_CONTENT", $this->table_content );
		parent::finalizeOutput ();
	}
	public function getOutput() {
		$this->finalizeOutput ();
		return parent::getOutput ();
	}
	
	/**
	 *
	 * @param
	 *        	$int
	 */
	public function setColumnCount($int) {
		$column = intval ( $int );
		if ($int < 1) {
			throw new \Exception ( "Integer value of TableCreator setColumnCount(int) is below 1" );
		}
		$this->column_count = $int;
	}
}

?>