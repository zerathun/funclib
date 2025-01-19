<?php 
namespace Funclib\Ifaces;

use Funclib\Database;
use \Exception as Exception;


abstract class Saveable {
    
    private $to_save = array();
    private $table;
    protected $assignAttrFunc = array();
    private $columns;
    private $loaded = array();
    
    
    public function __construct() {
        $this->requirementsSet();
        $SQL = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$this->table'";
        $res = Database::getInstance()->sql_query($SQL);
        while($row = Database::getInstance()->sql_fetch_array($res)) {
            $this->columns[] = $row;
        }
    }
    
    /**
     * get additional fields that are not being saved / stored as private variables in child class
     */
    protected function getLoaded() {
        return $this->loaded;
    }
    
    protected function changeLoadedInfo($identifier, $value) {
        $this->loaded[$identifier] = $value;
    }
      
    public function save($check_safe_sql=true) {
        $this->requirementsSet();

        $keys = $values = $update_string = "";

        foreach($this->to_save as $key => $val) {
            $flag = $this->fieldColumnExists($key);
            if($flag) {
                if(strlen($values) > 0) {
                    $values .=",";
                    $update_string .= ", ";
                    $keys .= ", ";
                }
                if($check_safe_sql) {
                    try {
                        Database::makeInjectionSafe($val);
                    } catch (Exception $e) {
                        print "Exception: ";
                        print_r($val);
                        print "<br><br>";
                    }
                }
                
                $values .= "'".$val."'";
                $keys .= $key;
                $update_string .= "$key="."'".$val."'";
            }
        }

        $sql = 'INSERT INTO '.$this->table.' ('.$keys.') VALUES ('.$values.") ON DUPLICATE KEY
UPDATE ".$update_string.'';

        Database::getInstance()->sql_query($sql);
        //print "<br><br>$sql<br>";
    }
    
    public function load(array $identifier) {
        $this->requirementsSet();
        $keys = array_keys($identifier);
        
        $where_ad = "";
        foreach($identifier as $key => $value) {
            if(strlen($where_ad) > 0) {
                $where_ad .= " AND ";
            }
            $where_ad .= "$key = '$value'";
        }
        
        $sql = "SELECT * FROM ".$this->table." WHERE $where_ad";

        $res = Database::getInstance()->sql_query($sql);
        $row = Database::getInstance()->sql_fetch_array($res);
        $this->loaded = $row;

        if(empty($row)) {
            throw new Exception("User not found with identifier $where_ad");
        }
        
        $methods =  $this->getMethods();

        foreach($row as $kx => $value) {
            if($this->fieldColumnExists($kx)) {
                $kxy = ucfirst($kx);
                $funcname = 'set'.$kxy;
                if(array_search($funcname, $methods)) {
                    $this->$funcname($value);
                } else {
                    $arr_attr = $this->getAssignedAttributes();
                    if(array_key_exists($kx, $arr_attr)) {
                        $fname = $arr_attr[$kx];
                        $this->$fname($value);
                    } else {
                        // "Funcname: $kx not found // Ignore <br>";
                    }
                }
                //$this->$kx = $value;
            }
        }
    }
    
    protected function setToSave($array) {
        $this->to_save = $array;
    }
    
    protected function setTable($table) {
        Database::getInstance()->makeInjectionSafe($table);
        $this->table = $table;
    }
    
    private function requirementsSet() {
        if(empty($this->table)) {
            throw new Exception("Table in saveable and child object is not set, where to save to");
        }
    }
    
    protected function fieldColumnExists($name) {
        $flag = false;
        foreach($this->columns as $cols_inf) {
            if($cols_inf['COLUMN_NAME'] == $name) {
                return true;
            }
        }
        return $flag;
    }
    
    protected abstract function getMethods();
    
    protected abstract function getAssignedAttributes();
}


?>