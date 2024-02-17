<?php
namespace Funclib\Ifaces;

interface ZeraLogger {
    
    public static function getInstance();
    
    /**
     * Append the Log Information to a cache that is stored later
     * @param String $log
     * @param int $log_level
     * @param String $message
     */
    public function AppendLog(String $log, int $log_level = 0, String $message = "");
    
    /**
     * Directly write the log to the storage
     * @param String $log
     * @param int $log_level
     * @param String $message
     */
    public function WriteLog(String $log, int $log_level = 0, String $message = "");
    
    /**
     * External setter wether to use log or not / flag operation
     * @return bool
     */
    public function UseLog() : bool;
    
    /**
     * Set the Log Flag
     * @param bool $bool
     */
    public function SetLog(bool $bool);
    
    /**
     * Stores appended log as a set of data into file/database
     */
    public function Store();
}

?>