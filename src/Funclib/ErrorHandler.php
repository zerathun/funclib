<?php
namespace Funclib;

use Funclib\Useables\EList;
use Funclib\FileLog;
use Funclib\Useables\ErrorItem;
use Funclib\Ifaces\Displayable;
use \Exception as Exception;


class ErrorHandler extends EList implements Displayable {
    
    /**
     * Allow multiple instances
     */
    public function __construct() {
    }
    
    protected static $errorHandler;
    private $display_trace = true;
    
    /**
     *
     * @return \ClassLibrary\system\ErrorHandler
     */
    public static function getErrorHandler() {
        if (empty ( ErrorHandler::$errorHandler )) {
            ErrorHandler::$errorHandler = new ErrorHandler ();
        }
        return ErrorHandler::$errorHandler;
    }
    
    public function addError($error_message, int $index=0, bool $system_err=false) {
        if($system_err)
            FileLog::getInstance()->appendLog($error_message."(".$this->getCount().")");
            
            if($index == 0) {
                $index = $this->getCount();
            }
            $errorItem = new ErrorItem ( $error_message, $index );
            $this->addItem ( $errorItem );
    }
    
    public function errorNoExists($index) {
        $this->resetListIndex();
        if($this->count() <= 0)
            return false;
            do {
                if(!empty($this->getCurrent()) && $index == $this->getCurrent()->getErrorNo()) {
                    return $this->getCurrent();
                }
            } while($this->getNext());
            return false;
    }
    
    public function addStandardError($error_no) {
        switch ($error_no) {
            case 1 :
                $this->addError ( "Error #" . $error_no . "Access denied! No access to this particular page" );
                break;
            default :
                $this->addError ( "Standard error message: The error #" . $error_no . " does not exist!" );
                break;
        }
    }
    
    public function addException(\Exception $e) {
        if ($e instanceof \Exception) {
            $msg = $e->getMessage ();
            if ($this->display_trace)
                $msg .= "<br>" . $e->getTraceAsString ();
                $this->addError ( $msg );
        } else {
            $this->addError ( "No Exception found" );
        }
    }
    
    /**
     *
     * @return bool
     */
    public function containsError() {
        $bool =  ( bool ) ($this->getCount() > 0);
        return $bool;
    }
    
    public function getListRendered() {
        $string = "";
        $list = $this->getList();
        
        foreach ( $list as $item ) {
            $string .= $item->getErrorMsg () . '<br \>';
        }
        return $string;
    }
    
    public function getList() {
        return parent::getList();
    }
    
    
    public function __toArray() {
        $this->resetListIndex();
        do {
            if(!empty($this->getCurrent())) {
                $array[] = $this->getCurrent()->getErrorMsg();
            }
        } while($this->getNext());
        return $array;
    }
    
    public function getOutput() {
        $template = new TemplateReader ();
        $template->readFile ( "Templates/ErrorWindow.html" );
        $output = "";
        
        $it = $this->getIterator ();
        if ($it->count () == 0)
            return "";
            while ( $it->current () ) {
                $output .= $it->current ()->getOutput ();
                $it->next ();
            }
            $template->inputVariable ( "ERROR_MESSAGE", $output );
        $template->finalizeOutput ();
        return $template->getOutput ();
    }
    
    public function logError(\Exception $e) {
        print "Logging error: " . $e->getMessage ();
        $sql = "INSERT INTO emb_log ('time', 'log_message', 'error_type', 'error_line', 'error_file')
					VALUES ('" . time () . "', '" . $e->getMessage () . "', '" . $e->getCode () . "', '" . $e->getLine () . "', '" . $e->getFile () . "');";
        Database::getInstance ()->sql_query ( $sql );
    }
}

?>