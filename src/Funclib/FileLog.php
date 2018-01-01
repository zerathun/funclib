<?php
namespace Funclib;

class FileLog {
    
    private $file_res;
    private $path;
    
    private function __construct() {
        // Initialize Later
    }
    
    private static $instance;
    
    public static function getInstance() {
        if(empty(FileLog::$instance)) {
            FileLog::$instance = new FileLog();
        }
        return FileLog::$instance;
    }
    
    public function definePath($path) {
        $this->path = $path;
    }
    
    public function appendLog($log) {
        if(empty($this->file_res)) {
            if(empty($this->path)) {
                throw new Exception("Please define 'FILELOG_PATH' for FileLog");
            }
            
            $this->file_res = fopen($this->path, "w+");
        }
        
        fwrite($this->file_res, date("d.m.y - H:i", time()).": ".$log."\n");
    }
    
}

?>