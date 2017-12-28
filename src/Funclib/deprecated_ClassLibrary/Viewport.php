<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;

class Viewport {
	private $output = "";
	private $template_reader;
	/*
	 * Registered variable
	 */
	private $registeredVariables;
	public function __construct() {
		// Define css class names
		// Use YAML
		$this->template_reader = new TemplateReader ();
	}
	public function setTemplate($path) {
		$this->template_reader->readFile ( $path );
	}
	public function registerVariable($variable, $content) {
		$this->template_reader->inputVariable ( $variable, $content );
	}
	public function parseOutput() {
		$this->template_reader->finalizeOutput ();
		$this->output = $this->template_reader->getOutput ();
	}
	public function getOutput() {
		$this->parseOutput ();
		return $this->output;
	}
}

?>