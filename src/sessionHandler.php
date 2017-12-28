<?php 
namespace src;

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
	    if(!empty($_SESSION[$this->getSessionPrefix().'name']))
	      return $_SESSION[$this->getSessionPrefix().'name'];
	    else
	      return NULL;
	}
	
	public function setSession($name, $value) {
		$_SESSION[$this->getSessionPrefix().'name'] = $value;
	}
		
	public function getSessionPrefix() {
		return $this->session_prefix;
	}
	
}

?>