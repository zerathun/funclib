<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;

class POS {
	private $original_array;
	public function __construct() {
	}
	public function loadWithArray($array) {
		$sql = "SELECT * FROM ember.emb_user_assets as T1 
				LEFT JOIN ember.invtypes as T2 ON t2.typeID = T1.typeID
				WHERE itemID = '" . $array ['itemID'] . "' OR parentItemID = '" . $array ['itemID'] . "'";
		
		$res = Database::getInstance ()->sql_query ( $sql );
		print_r ( mysql_error () );
		while ( $row = Database::getInstance ()->sql_fetch_array ( $res ) ) {
			if ($row ['typeID'] == "4312" || $row ['typeID'] == "4246" || $row ['typeID'] == "4247" || $row ['typeID'] == "4051") {
				$array ['Fuel'] ['typeID'] = $row ['typeID'];
				$array ['Fuel'] ['Amount'] = $row ['quantity'];
				$array ['Fuel'] ['typeName'] = $row ['typeName'];
				print "meuuhh";
			}
			if ($row ['typeID'] == "16275") {
				$array ['Stront'] ['typeID'] = $row ['typeID'];
				$array ['Stront'] ['Amount'] = $row ['typeQuantity'];
				$array ['Stront'] ['typeName'] = $row ['typeName'];
			}
		}
		
		$this->original_array = $array;
	}
	public function getListElement() {
	}
	public function getOutput() {
		$this->template->finalizeOutput ();
		return $this->template->getOutput ();
	}
	public function getOutputArr() {
		$img = "<img style=\"width: 32px; height 32px; float: left;\" src=\"https://image.eveonline.com/Type/" . $this->original_array ['typeID'] . "_32.png\" />";
		$fuel_img = $img = "<img style=\"width: 32px; height 32px; float: left;\" src=\"https://image.eveonline.com/Type/" . $this->original_array ['typeID'] . "_32.png\" />";
		
		$fuel_line = $array ['Fuel'] ['Amount'] . " " . $array ['Fuel'] ['typeName'];
		
		return array (
				$img . $this->original_array ['typeName'],
				$this->original_array ['fuelQuantity'],
				$fuel_line,
				"4. line" 
		);
	}
}

?>