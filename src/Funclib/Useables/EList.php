<?php
namespace Funclib\Useables;

use FuncLib\Useables\EListItem;


class EList extends \ArrayObject {
    protected $elist;
    protected $iterator;
    
    /**
     * deprecated
     *
     * @var unknown
     */
    private $currentIndex;
    
    public function __construct() {
        $this->iterator = $this->getIterator ();
    }
    
    public function getList() {
        return $this->getArrayCopy ();
    }
    
    public function sortAsc() {
        $this->asort ();
    }
    
    /**
     *
     * @param ClassLibrary\system\ListItem|ClassLibrary\system\EList $listItem
     * @throws \Exception
     */
    public function addItem($listItem) {
        if ($listItem instanceof EListItem || $listItem instanceof EList) {
            $this->append ( $listItem );
        } else {
            throw new \Exception ( "Wrong list item type: " . get_class ( $listItem ) );
        }
    }
    
    public function removeItemI($index) {
        if ($this->offsetExists ( $index )) {
            $this->offsetUnset ( $index );
            return true;
        }
        return false;
    }
    
    public function resetListIndex() {
        $iterator = $this->getIterator ();
        $iterator->rewind ();
    }
    
    public function getNext() {
        $this->iterator->next ();
        return $this->iterator->current ();
    }
    
    /**
     *
     * @return boolean
     */
    public function isLast() {
        $bool = (($this->currentIndex - 1) < $this->getCount () || $this->currentIndex >= $this->getCount ());
        return ( int ) $bool;
    }
    public function getFirst() {
        if ($this->count () > 0) {
            return $this->offsetGet ( 0 );
        } else {
            return null;
        }
    }
    public function getCurrent() {
        if(empty($this->iterator))
            $this->iterator = $this->getIterator ();
            return $this->iterator->current ();
    }
    
    public function getCount() {
        return $this->count ();
    }
    public function isUniqueListItem(EListItem $listItem) {
        if (! ($listItem instanceof EListItem)) {
            throw new \Exception ( "Wrong Item given" );
        }
        $this->iterator = $this->getIterator ();
        while ( $this->iterator->current () ) {
            if ($this->iterator->current () === $listItem) {
                return false;
            }
            $this->iterator->next ();
        }
        return true;
    }
    public function purgeList() {
        $iterator = $this->getIterator ();
        while ( $iterator->current () ) {
            $key = $iterator->key ();
            $this->offsetUnset ( $key );
            $iterator = $this->getIterator ();
        }
    }
}

?>