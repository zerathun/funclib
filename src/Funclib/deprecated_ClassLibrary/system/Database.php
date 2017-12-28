<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use \mysqli;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\FileLog;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ErrorHandler;

class Database {
	private $sqlC;
	private $mysql;
	private static $instance;
	private $query_counter = 0;
	private $server;
	private $db;
	private $uname;
	private $pw;
	private $dbh;
	
	public function __construct($server, $db, $uname = NULL, $pw = NULL) {
		$this->server = $server;
		$this->db = $db;
		$this->uname = $uname;
		$this->pw = $pw;
		
		//$this->mysql = mysql_connect ( $server, $uname, $pw );
		
		$dsn = 'mysql:dbname='.$db.';host='.$server;
		$user = $uname;
		$password = $pw;
		
		try {
			$this->dbh = new  \PDO($dsn, $user, $password);
		} catch (\PDOException $e) {
			FileLog::getInstance()->appendLog("SQL Failure: \n $sql\n".$e->getMessage());
			print_r('Connection failed: ' . $e->getMessage());
		}
		
		/*
		mysql_select_db ( $db, $this->mysql );
		if (mysql_error ()) {
			throw new \Exception ( mysql_error () );
		}
		
		/*
		 * $this->sqlC = new mysqli($server, $uname, $pw);
		 * $this->sqlC->mysqli_connect($server, $uname, $pw, $db);
		 * //if($this->sqlC->mysqli_error) {}
		 *
		 * /*mysql_select_db($db, $this->sqlC);
		 * if(mysql_error()) {
		 * throw new \Exception(mysql_error());
		 * }
		 */
	}
	
	public function getDBName() {
		return $this->db;
	}
	
	public static function getInstance() {
		if (empty ( Database::$instance )) {
			Database::startDatabaseConnection ();
		}
		return Database::$instance;
	}
	
	public static function startDatabaseConnection() {
		Database::$instance = new Database ( "localhost", "embin", "ember", "1234Asdf" );
	}
	
	public function getPDOConnection() {
		return $this->dbh;
	}
	
	/**
	 * deprecated
	 * @param unknown $query
	 * @throws \Exception
	 * @return unknown
	
	public function nsql_query($query) {
		$this->query_counter ++;
		$res = $this->dbh->query($query);
		return $res;
		$resource = mysql_query ( $query, $this->mysql );
		$this->query_counter ++;
		if (mysql_error ( $this->mysql ) <= 0) {
			return $resource;
		} else {
			print mysql_error ( mysql_error ( $this->mysql ) );
			throw new \Exception ( "Query Error: $query <br><br>" . mysql_error ( $this->mysql ) );
		}
	}
	 */
	
	/**
	 * Query Function 
	 * 
	 * @param SQLQuery $query
	 * @return Resource
	 */
	public function sql_query($query) {
		
		try {
			$stmt = $this->dbh->prepare($query);
			$stmt->execute();
			// Check if there is any error
			$err = $stmt->errorInfo();
			if(isset($err[0]) && intval($err[0]) != 0) {
				throw new \Exception($err[0]." ".$err[1]." ".$err[2]);
			}
			return $stmt;
		} catch(\Exception $e) {
			print "Error: ".$e->getMessage()."\n\n$query\n\n";
			FileLog::getInstance()->appendLog("SQL Failure: \n $sql\n".$e->getMessage());
			ErrorHandler::getErrorHandler()->addException($e);
		}
		/*
		return $this->nsql_query ( $query );
		
		$resource = mysqli_query ( $this->sqlC, $query );
		
		if (mysqli_errno ( $this->sqlC ) == 0) {
			return $resource;
		} else {
			throw new \Exception ( "SQL Query $query error ocurred <br><br>" . mysqli_error ( $this->sqlC ) . " " );
		}
		if (mysql_error ( $this->sqlC )) {
			throw new \Exception ( "Mysqlerror: " . mysql_error ( $this->sqlC ) );
		}
		/*
		 * $resource = mysql_query($query, $this->sqlC);
		 * $this->query_counter++;
		 * if(mysql_errno($this->sqlC) == 0) {
		 * return $resource;
		 * } else {
		 * throw new \Exception("SQL Query $query error ocurred <br><br>".mysql_error($this->sqlC));
		 * }
		 */
	}
	
	/**
	 *
	 * @param String $query        	
	 * @throws \Exception
	 * @return Resource
	 */
	public function multiple_sql_query($query) {
		return $this->sql_query($query);

		$this->sqlC = mysqli_connect ( $this->server, $this->uname, $this->pw, $this->db );
		$_SESSION ['mysql_updates'] [UserManagement::getInstance ()->getCurrentUser ()->getId ()] ['ident_1'] = 1;
		$resource = mysqli_multi_query ( $this->sqlC, $query );
		mysqli_store_result ( $this->sqlC );
		$this->query_counter ++;
		
		if (mysqli_errno ( $this->sqlC ) == 0) {
			if (empty ( $resource )) {
				mysqli_close ( $this->sqlC );
				$_SESSION ['mysql_updates'] [UserManagement::getInstance ()->getCurrentUser ()->getId ()] ['ident_1'] = 0;
			} else {
				return $resource;
			}
		} else {
			throw new \Exception ( "SQL Query $query error ocurred <br><br>" . mysqli_error ( $this->sqlC ) );
		}
	}
	
	public function sql_fetch_object($resource) {
		if (empty ( $resource )) {
			throw new \Exception ( "Resource is empty " );
		}
		return $resource->fetch(\PDO::FETCH_OBJ);
	}
	
	public function sql_fetch_array($resource) {
		if (empty ( $resource )) {
			throw new \Exception ( "Resource is empty " );
		}
		return $resource->fetch(\PDO::FETCH_ASSOC);
	}
	
	public function sql_fetch_row($resource) {
		return $this->sql_fetch_array($resource);
	}
	
	public function getQueryCount() {
		return $this->query_counter;
	}

	public function __destruct() {
	}
}

?>