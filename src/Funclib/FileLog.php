<?php 
namespace Funclib;

class FileLog {
	private $file_res;
	
	private function __construct() {
	    if(isset(FILELOG_PATH)) {
	        $this->file_res = fopen(FILELOG_PATH, "w+");
	    } else {
	        throw new Exception("Please define Constant 'FILELOG_PATH'");
	    }
	}
	
	private static $instance;
	
	public static function getInstance() {
		
		if(empty(FileLog::$instance)) {
			FileLog::$instance = new FileLog();
		}

		return FileLog::$instance;
	}
	
	public function appendLog($log) {
		fwrite($this->file_res, date("d.m.y - H:i", time()).": ".$log."\n");
	}
	
}

?>