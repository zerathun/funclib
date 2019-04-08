<?php
namespace Funclib;

class sessionHandler {
    
    protected static $instance;
    protected $session_prefix;
    
    private function __construct() {
        $this->session_prefix =  "EmbDev_";
    }
    
    public static function getInstance() {
        if(empty (sessionHandler::$instance)) {
            sessionHandler::$instance = new sessionHandler();
        }
        return sessionHandler::$instance;
    }
    
    public function getSessionId() {
        return (string) session_id();
    }
    
    public function getSession($name) {
        if(!empty($_SESSION[$this->getSessionPrefix().$name]))
            return $_SESSION[$this->getSessionPrefix().$name];
            else
                return NULL;
    }
    
    public function setSession($name, $value) {
        $_SESSION[$this->getSessionPrefix().$name] = $value;
    }
    
    public function getSessionPrefix() {
        return $this->session_prefix;
    }
    
    public function setCookie($name, $value) {
        $_COOKIE[$this->getSessionPrefix().$name] = $value;
    }
    
    public function getCookie($name) {
        if(empty($_COOKIE[$this->getSessionPrefix().$name]))
            return false;
            else
                return $_COOKIE[$this->getSessionPrefix().$name];
    }
    
    /**
     * Set the Session Prefix the first time the sessionHandler is instantiated
     *
     * */
    public function setSessionPrefix($prefix) {
        $this->session_prefix = $prefix;
    }
}

?>