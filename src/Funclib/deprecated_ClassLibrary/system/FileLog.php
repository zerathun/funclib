<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

class FileLog {
	private $file_res;
	private function __construct() {
		$this->file_res = fopen("/var/www/log/emb_logg.log", "w+");
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