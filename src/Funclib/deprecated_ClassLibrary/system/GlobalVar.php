<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

class GlobalVar {
	
	// Instance
	private static $instance;
	
	private $session_vars;
	
	private $session_id;
	
	public static function getInstance() {
		if(empty(GlobalVar::$instance)) {
			GlobalVar::$instance = new GlobalVar();
		}
		return GlobalVar::$instance;
	}
	
	public static function gI() {
		return GlobalVar::getInstance();
	}
	
	private function __construct() {
		$this->cleanupSessionsTable();
		$this->loadSession();
	}
	
	public function loadSession($sid=0) {
		global $user;
		if($sid==0) {
			$this->session_id = $sid = $user->session_id;
		}
		// Refresh session timer
		$sql = "UPDATE emb_globalvars SET timestamp = ".time()." WHERE session_id = '$this->session_id'";
		Database::getInstance ()->sql_query ( $sql );
		// Load session data
		$sql = "SELECT * FROM emb_globalvars WHERE session_id = '$this->session_id'";
		$res = Database::getInstance ()->sql_query ( $sql );
		while($row = Database::getInstance()->sql_fetch_array($res)) {
			$this->session_vars[$row['valuename']] = $row;
		}
	}
	
	public function getSession($sname, $intval=true) {
		if(!empty($this->session_vars[$sname])) {
			if($intval) {
				return $this->session_vars[$sname]['value'];
			}
			else {
				return $this->session_vars[$sname]['varch_value'];
			}
		} else {
			return 0;
		}
	}
	
	public function setSession($sname, $value, $intval=true) {
		$varch_value = !$intval ? $value : "";
		$intval = $intval ? intval($value):0;
		if(isset($this->session_vars[$sname])) {
			$sql = "UPDATE emb_globalvars SET value = $intval, varch_value = '".$varch_value."' WHERE session_id = '".$this->session_id."' AND valuename = '".$sname."'";
		} else {
			$sql = "INSERT INTO emb_globalvars (session_id, valuename, value, varch_value, timestamp) VALUES ('".$this->session_id."', '".$sname."', ".$intval.", '".$varch_value."', ".time().") ";
		}
		Database::getInstance ()->sql_query ( $sql );
		$this->session_vars[$sname] = array('varch_value' => $varch_value, 'value' => $intval, 'session_id' => $this->session_id, 'valuename' => $sname, 'timestamp' => time());
	}
	
	
	public function cleanupSessionsTable() 
	{
		$refresh = $this->getSession("cleanup_sessions_table");
		if($refresh < time()-86400) {
			$sql = "DELETE FROM emb_globalvars WHERE timestamp < ".(time()-86400);
			Database::getInstance ()->sql_query ( $sql );
		}
		else {
			$this->setSession('cleanup_sessions_table', time(), true);
		}
	}
	

	public function getStandardSelectedValue($form_name, $session_name, $standard=0) {
		global $request;
		$result = $post_set_region = $request->variable($form_name, 0);
		if($post_set_region == 0 ) {
			$result = $standard_region = GlobalVar::gI()->getSession($session_name, true);
		} else {
			GlobalVar::gI()->setSession($session_name, $post_set_region);
		}
		if($result == 0)
			$result = $standard;
		return $result;
	}
}

?>