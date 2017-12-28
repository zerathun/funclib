<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\Viewport;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\ListItem;

class MenuItem extends ListItem {
	private $link;
	private $name;
	function __construct($name, $link) {
	}
	public function setName($name) {
		$this->name = $name;
	}
	public function getName() {
		return $this->name;
	}
	public function setLink($link) {
		$this->link = $link;
	}
	public function getLink() {
		return $this->link;
	}
	
	/*
	 * (non-PHPdoc)
	 * @see listItem::isEqual()
	 */
	public function isEqual(ListItem $listItem) {
		$this->isSameInstance ( $listItem, 1 );
		return ($listItem->getName () == $this->getName () && $listItem->getLink () == $this->getLink ());
	}
	
	/*
	 * (non-PHPdoc)
	 * @see listItem::isGreater()
	 */
	public function isGreater(ListItem $listItem) {
		$this->isSameInstance ( $listItem, 1 );
		return (strcmp ( $this->getName (), $listItem->getName () ));
	}
	
	/*
	 * (non-PHPdoc)
	 * @see listItem::isSmaller()
	 */
	public function isSmaller(ListItem $listItem) {
		$this->isSameInstance ( $listItem, 1 );
		return (strcmp ( $listItem->getName (), $this->getName () ));
	}
}

?>