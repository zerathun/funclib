<?php
namespace Funclib;

/* Create Singleton Instance of Variables that will be put into the TWIG Loader and parsed*/

class twigVariables {
    private $variables = array();
    private static $inst;
    private function __construct() {  }
    
    public static function gI() {
        if(empty(twigVariables::$inst))
            twigVariables::$inst = new twigVariables();
        return twigVariables::$inst;
    }
    
    public function getVariables() { return $this->variables; }
    
    public function getVariable($identifier) {
        if(isset($this->variables[$identifier]))
            return $this->variables[$identifier];
        else return null;
    }
    
    
    public function setVariable($key, $value) { 
        $this->variables[$key] = $value;
    }
    
    public function setVariables($array) {
        foreach($array as $key => $value) {
            $this->variables[$key] = $value;
        }
    }
    
}

?>