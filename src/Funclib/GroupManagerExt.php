<?php
namespace Funclib;

class GroupManagerExtern
{
    
    private function __construct() {
        // Initialize Later
    }
    
    private static $instance;
    
    public static function getInstance() {
        if(empty(FileLog::$instance)) {
            GroupManagerExtern::$instance = new GroupManagerExtern();
        }
        return GroupManagerExtern::$instance;
    }
    
    public function ParseGroupResponse($response)
    {
        $result = array();
        
        if(is_object($response))
        {
            
            
            
        }
        
        return $result;
    }
    
}
?>