<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;

class FileSystem {
	private $file_path;
	private $file_res;
	public function __construct($path = "", $mode = "r") {
		new \Exception ();
		try {
			$this->openFile ( $path, $mode );
		} catch ( \Exception $e ) {
			ErrorHandler::getErrorHandler ()->logError ( $e );
		}
	}
	public function openFile($path, $mode) {
		if (file_exists ( $path ) || $mode = "w+") {
			$this->file_res = fopen ( $path, $mode );
			$this->file_path = $path;
		} else {
			throw new \Exception ( "Could not open file: $path" );
		}
	}
	public function writeFile($content) {
		fwrite ( $this->file_res, $content );
	}
	public function __destruct() {
		fclose ( $this->file_res );
		$this->file_path = null;
		$this->file_res = null;
	}
}

?>