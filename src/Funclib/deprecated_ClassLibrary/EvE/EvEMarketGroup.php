<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

class EvEMarketGroup {
	protected $name;
	protected $marketgroup_id;
	protected $items;
	function __construct() {
		$this->name = "";
		$this->marketgroup_id = 0;
		$this->items = array ();
	}
	public function setName($name) {
		$this->name = $name;
	}
	public function getName() {
		return $this->name;
	}
	public function setMarketGroupID($mgid) {
		$this->marketgroup_id = $mgid;
	}
	public function getMarketGroupID() {
		return $this->marketgroup_id;
	}
	public function getItems() {
		return $this->items;
	}
}

?>