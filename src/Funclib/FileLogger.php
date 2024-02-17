<?php 
namespace Funclib;

use Funclib\Ifaces\ZeraLogger;

class FileLogger implements ZeraLogger
{
    
    private static $instance;
    private $use_logger;
    
    private $file_res;
    private $path;
    
    private $log_messages = array();
    
    public static function getInstance() : ZeraLogger {
        if(empty(FileLogger::$instance)) {
            FileLogger::$instance = new FileLogger();
        }
        return FileLogger::$instance;
    }
    
    /**
     * Append the Log Information to a cache that is stored later
     * @param String $log
     * @param int $log_level
     * @param String $message
     */
    
    public function AppendLog(String $log, int $log_level = 0, String $message ="")
    {
        $this->log_messages[] = array('log' => $log, 'log_level' => $log_level, 'message' => $message);
    }
    
    /**
     * Implementation of WriteLog through ZeraLogger
     * @param String $log
     * @param int $log_level
     * @param String $message
     */
    public function WriteLog(String $log, int $log_level = 0, String $message = "")
    {
        if(empty($this->file_res)) {
            if(empty($this->path)) {
                throw new \Exception("Please define 'FILELOG_PATH' for FileLog");
            }
            
            $this->file_res = fopen($this->path, "a");
        }
        
        fwrite($this->file_res, date("d.m.y - H:i", time()).": ".$string."\n");
    }
    
    /**
     * External setter wether to use log or not / flag operation
     * @return bool
     */
    public function UseLog() : bool
    {
        return (bool)$this->use_logger;
    }
    
    /**
     * Set the Log Flag
     * @param bool $bool
     */
    public function SetLog(bool $bool)
    {
        $this->use_logger = $bool;
    }
    
    /**
     * 
     */
    public function Store()
    {
        // Store is not used in this case, because all logs are directly stored into the file
        if(empty($this->file_res)) {
            if(empty($this->path)) {
                throw new \Exception("Please define 'FILELOG_PATH' for FileLog");
            }
            
            $this->file_res = fopen($this->path, "a");
        }
        foreach($this->log_messages as $messageArr)
        {
            $msgVal = !empty($messageArr['message']) ? "[".$messageArr['message']."]:" : "";
            $message = "(".$messageArr['log_level'].") ".$msgVal.$messageArr['log']." ";
            $this->WriteFile($this->file_res, time(), $message);
        }
    }
    
    /**
     * 
     * @param resource|false $file_resource
     * @param int $time
     * @param String $message
     */
    private function WriteFile($file_resource, int $time, string $message)
    {
        if($file_resource !== false)
            fwrite($file_resource, date("d.m.y - H:i", $time).": ".$message."\n");
    }
    
    public function AppendArray(Array $arr, $message='') {
        $string = '';
        if(empty($arr)) {
            $string = " No values in array defined: size(arr) = 0";
        } else {
            foreach($arr as $key => $value) {
                $string .= " $key: ".$value.";";
            }
        }
        if(strlen($message) > 0) {
            $string = $message.":".$string;
        }
        $this->WriteLog($string);
    }
    
    private function __construct() {
        // Initialize Later
    }
    
    public function DefinePath($path) {
        $this->path = $path;
    }
}






?>