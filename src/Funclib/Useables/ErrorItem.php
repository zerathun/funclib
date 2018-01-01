<?php
namespace Funclib\Useables;

use Funclib\Useables\EListItem;
use Funclib\Ifaces\Displayable;

class ErrorItem extends EListItem implements Displayable {
    /*
     * (non-PHPdoc)
     * @see ListItem::isEqual()
     */
    private $error_no = 0;
    private $error_msg = "";
    
    public function __construct($msg, $err_no) {
        $this->setErrorMsg ( $msg );
        $this->setErrorNo ( $err_no );
    }
    
    private function setErrorNo($error_no) {
        $this->error_no = $error_no;
    }
    public function getErrorNo() {
        return $this->error_no;
    }
    public function getErrorMsg() {
        if (empty ( $this->error_msg ))
            throw new \Exception ( "Error message is empty" );
            return $this->error_msg;
    }
    public function setErrorMsg($msg) {
        $this->error_msg = $msg;
    }
    
    public function isEqual(EListItem $listItem) {
        if (! ($listItem instanceof ErrorItem)) {
            throw new \Exception ( "Wrong item given" );
        }
        return ($this->getErrorNo () == $listItem->getErrorNo ());
    }
    
    
    /*
     * (non-PHPdoc)
     * @see ListItem::isGreater()
     */
    public function isGreater(EListItem $listItem) {
        // TODO Auto-generated method stub
        if (! ($listItem instanceof ErrorItem)) {
            throw new \Exception ( "Wrong item given" );
        }
        return (intval ( $this->getErrorNo () ) > intval ( $listItem->getErrorNo () ));
    }
    
    /*
     * (non-PHPdoc)
     * @see ListItem::isSmaller()
     */
    public function isSmaller(EListItem $listItem) {
        // TODO Auto-generated method stub
        if (! ($listItem instanceof ErrorItem)) {
            throw new \Exception ( "Wrong item given" );
        }
        return (intval ( $this->getErrorNo () ) > intval ( $listItem->getErrorNo () ));
    }
    /*
     * (non-PHPdoc)
     * @see \ClassLibrary\Ifaces\Displayable::getOutput()
     */
    public function getOutput() {
        $err_msg = new TemplateReader ();
        $err_msg->readFile ( "Templates/ErrorMsg.html" );
        $err_msg->inputVariable ( "ERROR_MSG", $this->getErrorMsg () );
        $err_msg->finalizeOutput ();
        return $err_msg->getOutput ();
    }
}

?>