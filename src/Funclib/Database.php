<?php
namespace Funclib;

use \mysqli;
use Funclib\FileLog;
use Funclib\ErrorHandler;

class Database {
    private $sqlC;
    private $mysql;
    private static $instance;
    private $query_counter = 0;
    private $last_query;
    private $server;
    private $db;
    private $uname;
    private $pw;
    private $dbh;
    private static $counter = 0;
    private $last_query_row_count = 0;
    
    private function __construct() {
        Database::$counter++;
        // test
    }
    
    public function connect($dbname="") {
        if(empty($this->server) || (empty($this->db) && empty($dbname)) || empty($this->uname)) {
            $e = new \Exception("DB Credentials not set");
            $this->postError($e);
            throw $e;
        }
        
        if(strlen($dbname) <= 0)
        {
            $dbname = $this->db;
        }
        
        $dsn = 'mysql:dbname='.$dbname.';host='.$this->server;
        $user = $this->uname;
        $password = $this->pw;
        
        try {
            $this->dbh[$dbname] = NULL;
            $this->dbh[$dbname] = new  \PDO($dsn, $user, $password);
        } catch (\PDOException $e) {
            $this->postError($e);
        }
    }
    
    public function getInstanceCounter() {
        return Database::$counter;
    }
    
    private function postError(\Exception $e) {
        //FileLog::getInstance()->appendLog("SQL Error: \n $this->last_query\n".$e->getMessage());
        ErrorHandler::getErrorHandler()->addException($e);
    }
    
    public function setServerSettings($server, $db, $uname, $pw) {
        $this->setServer($server);
        $this->setDatabase($db);
        $this->setUsername($uname);
        $this->setPassword($pw);
    }
    
    public function setDatabase($db) {
        $this->db = $db;
    }
    
    public function setUsername($uname) {
        $this->uname = $uname;
    }
    
    public function setPassword($pw) {
        $this->pw = $pw;
    }
    
    public function setServer($server) {
        $this->server = $server;
    }
    
    public function getDBName() {
        return $this->db;
    }
    
    public static function getInstance($new = false) {
        if (empty ( Database::$instance ) || $new) {
            Database::startDatabaseConnection ();
        }
        return Database::$instance;
    }
    
    public static function startDatabaseConnection() {
        Database::$instance = new Database ();
    }
    
    public function getPDOConnection($dbname="") {
        if(strlen($dbname) <= 0)
            $dbname = $this->db;
            
        return $this->dbh[$dbname];
    }
    
    /**
     * Query Function
     *
     * @param SQLQuery $query
     * @return Resource
     */
    public function sql_query($query) {
        $this->last_query = $query;
        if(empty($this->dbh)) {
            $error_string = "SQL Failure: No Database selected / No Connection made in Program";
            $e = new \Exception($error_string);
            $this->postError($e);
            return false;
        }
        
        try {
            $stmt = $this->dbh->prepare($query);
            $stmt->execute();
            $this->last_query_row_count = $stmt->rowCount();
            // Check if there is any error
            $err = $stmt->errorInfo();
            if(isset($err[0]) && intval($err[0]) != 0) {
                throw new \Exception($err[0]." ".$err[1]." ".$err[2]);
            }
            
            return $stmt;
        } catch(\Exception $e) {
            $this->postError($e);
        }
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
            throw new \Exception ( "Resource is empty" );
        }
        return $resource->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function sql_fetch_row($resource) {
        return $this->sql_fetch_array($resource);
    }
    
    public function getQueryCount() {
        return $this->query_counter;
    }
    
    public function getAffectedRows() {
        return $this->last_query_row_count;
    }
    
    public function makeInjectionSafe($input) {
        $pregString = "";
        
        $rr = Database::getSQLMethods();
        foreach($rr as $x) {
            if(strlen($pregString) > 0) {
                $pregString .= '|';
            }
            $pregString .= $x;
        }
        
        if(preg_match('#\b('.$pregString.')\b#', strtoupper($input), $matches)) {
            throw new \Exception("SQL Injection Warning");
        }
        
        if(count($matches)  > 0 || Database::isInjectionSafe($input) == false)
            throw new \Exception("SQL Injection Warning");
    }
    
    private static function getSQLMethods() {
        return array (
            'DROP',
            'UPDATE',
            'DELETE',
            'PURGE',
            'ALTER',
            'CHANGE',
            'CREATE',
            'GRANT',
            'KILL',
            'RENAME',
            'RESTRICT',
            'SET',
            'UNDO',
            'UNLOCK',
            'LOCK',
            'WRITE',
            'SELECT'
        );
    }
    
    /**
     * returns true if the given string contains no dangerous keywords
     *
     * @param String $where_statement
     * @return Boolean
     */
    public static function isInjectionSafe($statement) {
        if(is_array($statement))
            throw new \Exception("Injection-Safe Statement expects a given string");
        $needle = Database::getSQLMethods();
        $count = 0;
        foreach ( $needle as $substring ) {
            $count += substr_count ( $statement, $substring );
        }
        return ($count <= 0);
    }
    
    public function __destruct() {
    }
}

?>