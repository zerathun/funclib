<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
//use zeradun\api_manager\includes\Ember\ClassLibrary\system\SingletonTask_LoadManageUpdates;

class UpdateTask {
	
	private $taskname = "";
	private $finished;
	private $last_executed;
	private $last_query_id;
	private $notindb;
	private $period;
	
	public function __construct($taskname) {
		$this->taskname = $taskname;

		$array = SingletonTask_LoadManageUpdates::gI()->getTaskStatusList();
		if(empty($array[$this->taskname])) {
			$this->finished = 1;
			$this->last_executed = 0;
			$this->last_query_id = 0;
			$this->notindb = true;
			$this->period = 86400;
		} else {
			$row = $array[$this->taskname];
			$this->finished = $row['finished'];
			$this->last_query_id = $row['last_query_id'];
			$this->last_executed = $row['last_executed'];
			if($row['period'] <= 0) $row['period'] = 86400;
			$this->period = $row['period'];
			$this->notindb = false;
		}
	}
	
	public function updateTask($finished, $lastQuery=0) {
		$this->finished = $finished = $finished ? 1:0;
		$this->last_query_id = $lastQuery = intval($lastQuery);
		$this->last_executed = time();
		
		if($this->notindb) {
			$sql = "INSERT INTO evestatic_manageupdates (task, last_query_id, finished, last_executed, period) VALUES ('".$this->taskname."', $lastQuery, $finished, ".$this->last_executed.", ".$this->period.")";
			Database::getInstance ()->sql_query ( $sql );
			SingletonTask_LoadManageUpdates::gI(true);
		} else {
			$sql = "UPDATE evestatic_manageupdates SET last_query_id = '".$lastQuery."', finished = $finished, last_executed = ".$this->last_executed.", period = '".$this->period."' WHERE task = '".$this->taskname."'";
			Database::getInstance ()->sql_query ( $sql );
		}
			
		// This list is not for the Task Object
		//$this->update_status[$task] = array('task' => $this->taskname, 'last_query_id' => $lastQuery, 'finished' => $finished, 'last_executed' => time());;
	}
	
	public function setPeriod($intval) {
		$this->period = intval($intval);	
	}
	
	/** Getter Functions */
	public function isFinished() {
		return (bool)$this->finished;
	}
	
	public function lastExecuted() {
		if(isset($this->last_executed))
			return intval($this->last_executed);
		else return 0;
	}
	
	public function getLastQueryID() {
		return $this->last_query_id;
	}
	
	public function isRunnable() {
		if($this->finished && $this->last_executed < intval(time()-$this->period))
			return true;
		else
			return false;
	}
}

class SingletonTask_LoadManageUpdates {

	private static $instance;
	private $list;

	private function __construct() {
		$sql  = "UPDATE evestatic_manageupdates SET finished = 1, last_query_id = 0 WHERE last_executed < ".(time()-604800).";";
		Database::getInstance ()->sql_query ( $sql );
		$sql = "SELECT * FROM evestatic_manageupdates";
		$res = Database::getInstance ()->sql_query ( $sql );
		while($row = Database::getInstance ()->sql_fetch_array($res)) {
			$this->list[$row['task']] = $row;
		}
	}

	public static function gI($reload=false) {
		if(empty(SingletonTask_LoadManageUpdates::$instance) || $reload) {
			SingletonTask_LoadManageUpdates::$instance = new SingletonTask_LoadManageUpdates();
		}
		return SingletonTask_LoadManageUpdates::$instance;
	}

	public function getTaskStatusList() {
		return $this->list;
	}
}
?>