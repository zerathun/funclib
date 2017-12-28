<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\ListItem as ListItem;

class TemplateElement extends ListItem {
	private $key; // Template Element Key in Template-File
	private $content;
	function __construct($key) {
		$this->setKey ( $key );
	}
	/*
	 * (non-PHPdoc)
	 * @see ListItem::isEqual()
	 */
	public function setContent($content) {
		$this->content = $content;
	}
	public function getContent() {
		return $this->content;
	}
	public function setKey($key) {
		$this->key = $key;
	}
	public function getKey() {
		return $this->key;
	}
	public function isEqual(ListItem $listItem) {
		// TODO Auto-generated method stub
		return (( string ) $listItem->getKey () == ( string ) ($this->getKey ()));
	}
	
	/*
	 * (non-PHPdoc)
	 * @see ListItem::isGreater()
	 */
	public function isGreater(ListItem $listItem) {
		// TODO Auto-generated method stub
		return (strcmp ( $this->getKey (), $listItem->getKey ));
	}
	
	/*
	 * (non-PHPdoc)
	 * @see ListItem::isSmaller()
	 */
	public function isSmaller(ListItem $listItem) {
		return (strcmp ( $listItem, $this->getKey ));
	}
	public function __toString() {
		return "Template Object{<br>
				Content" . $this->content . " <br>
				Key" . $this->key . " <br>
				}";
	}
}

?>