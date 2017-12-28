<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules\cap_production;

class cap_part {
	
	public function __construct();
	
	protected $amount = 0;
	protected $typeId = 0;
	
	public function setAmout($integer) {
		$this->amount = $integer;
	}
	
	public function getAmount() {
		return $this->amount;
	}
	
	public function getTypeId() {
		return $this->typeId;
	}
	
}

?>