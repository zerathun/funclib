<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;

class Asset extends ItemList {
	private $SQLobj;
	private $item_content;
	private $quantity;
	private $location;
	private $parent = 0;
	private $placeholder = 0;
	function __construct() {
	}
	public function loadItemDB_Obj($object) {
		foreach ( $object as $key => $el ) {
			if (! is_array ( $el ) && ! is_object ( $el )) {
				$item [$key] = $el;
			}
		}
		$this->loadItemDB ( $item );
	}
	public function loadItemDB($array) {
		if (! isset ( $array ['singleton'] ))
			throw new \Exception ( "Array 'singleton' is not set" );
		if (! isset ( $array ['quantity'] ))
			throw new \Exception ( "Array 'quantity' is not set" );
		else {
			$this->setQuantity ( $array ['quantity'] );
		}
		if (! isset ( $array ['itemID'] ))
			throw new \Exception ( "Array 'itemID' is not set" );
		if (! isset ( $array ['typeID'] ))
			throw new \Exception ( "Array 'typeID' is not set ON " . $array ['itemID'] );
		else {
			$this->setTypeID ( $array ['typeID'] );
		}
		if (! isset ( $array ['flag'] ))
			throw new \Exception ( "Array 'flag' is not set" );
		else {
			$this->setFlag ( $array ['flag'] );
		}
		
		if (! empty ( $array ['parentItemID'] )) {
			$this->setParent ( $array ['parentItemID'] );
		}
		
		$this->item_content = $array;
	}
	public function loadPlaceholder($itemId) {
		$this->item_content ['itemID'] = $itemId;
		$this->placeholder = 1;
	}
	public function isPlaceholder() {
		return ( bool ) $this->placeholder;
	}
	public function setPlaceholder($bool) {
		if ($bool)
			$this->placeholder = 1;
		else
			$this->placeholder = 0;
	}
	public function setQuantity($int) {
		if ($int >= 0) {
			$this->quantity = $int;
		}
	}
	public function getItemId() {
		if (empty ( $this->item_content ['itemID'] ))
			throw new \Exception ( "System error: ItemID of asset object is not properly set." );
		return $this->item_content ['itemID'];
	}
	public function getCount() {
		if (! isset ( $this->quantity )) {
			throw new \Exception ( "System error: Count of asset object is not properly set." );
		} else {
			return $this->quantity;
		}
	}
	public function setItemContent($item_key, $value) {
		$this->item_content [$item_key] = $value;
	}
	public function setFlag($flag) {
		if (empty ( $flag ))
			$flag = 0;
		$this->setItemContent ( 'flag', $flag );
	}
	public function getFlag() {
		return $this->item_content ['flag'];
	}
	
	/**
	 *
	 * @param Integer $parent        	
	 */
	public function setParent($parent) {
		$this->parent = $parent;
	}
	
	/**
	 *
	 * @return number
	 */
	public function getParent() {
		return $this->parent;
	}
	
	/**
	 *
	 * @param integer $typeID        	
	 */
	public function setTypeID($typeID) {
		$this->setItemContent ( 'typeID', $typeID );
	}
	public function getTypeID() {
		return $this->item_content ['typeID'];
	}
	public function isSingleton() {
		return $this->item_content ['singleton'] ? true : false;
	}
	public function getOutput() {
		return "<b>Asset " . $this->getItemId () . "</b><br>";
	}
	public function getName() {
		return $this->item_content ['typeName'];
	}
	public function getListLine($subelement = false) {
		$element_info = "";
		$txt = "";
		foreach ( $this->item_content as $key => $element ) {
			if ($key != "contents") {
				$element_info .= "[$key]: " . $element . " || ";
			}
		}
		
		$sub_items = "";
		$iterator = $this->getIterator ();
		while ( $iterator->current () ) {
			$sub_items .= $iterator->current ()->getListLine ( true );
			$iterator->next ();
		}
		
		$item_div = '<div class="ym-gl" style="width:32px; height: 32px; border-bottom: #333 1px solid;">
		<img src="https://image.eveonline.com/Type/' . $this->getTypeID () . '_32.png" />
		</div>';
		
		if (empty ( $this->item_content ['LocationName'] )) {
			$this->item_content ['LocationName'] = "Empty";
		}
		if (empty ( $this->item_content ['typeName'] )) {
			$this->item_content ['typeName'] = "Empty";
		}
		if (empty ( $this->item_content ['flagText'] )) {
			$this->item_content ['flagText'] = "Empty";
		}
		
		if ($subelement) {
			return '<tr>
						<td style="border: 0px solid #F00; background-color: #DDD; padding: 0px; padding-left: 20px;"><img src="https://image.eveonline.com/Type/' . $this->getTypeID () . '_32.png" /></td>
						<td style="border: 0px solid #F00; background-color: #DDD; padding: 0px;">' . $this->getCount () . '</td>
						<td style="border: 0px solid #F00; background-color: #DDD; padding: 0px;">' . $this->item_content ['LocationName'] . '</td>
						<td style="border: 0px solid #F00; background-color: #DDD; padding: 0px;">' . $this->item_content ['typeName'] . '</td>
						<td style="border: 0px solid #F00; background-color: #DDD; padding: 0px;">' . $this->item_content ['flagText'] . '</td>
						<td style="border: 0px solid #F00; background-color: #DDD; padding: 0px;">' . $element_info . '</td>
					</tr>
								';
		} else
			return '
				<table>
					<tr>
						<td><img src="https://image.eveonline.com/Type/' . $this->getTypeID () . '_32.png" /></td>
						<td>' . $this->getCount () . '</td>
						<td>' . $this->item_content ['LocationName'] . '</td>
						<td>' . $this->item_content ['typeName'] . '</td>
						<td>' . $this->item_content ['flagText'] . '</td>
						<td>' . $element_info . '</td>
					</tr>
					' . $sub_items . '			
				</table>
				';
		
		return '<div class="ym-gl" style="width:32px; height: 32px; border-bottom: #333 1px solid;">
		<img src="https://image.eveonline.com/Type/' . $this->getTypeID () . '_32.png" />
		</div>
		<div class="ym-gl" style="border: #333 1px solid; margin-top: -1px; width: 50px; height: 32px;">' . $this->getCount () . '</div>
		<div class="ym-gl" style="border: #333 1px solid; border-left: 0px; margin-top: -1px; width: 300px; height: 32px;">' . $this->getName () . '</div>
		<div class="ym-gl" style="border: #333 1px solid; border-left: 0px; margin-top: -1px; width: 50px; height: 32px;">' . $this->item_content ['LocationName'] . '</div>
		<div class="ym-gl" style="border: #333 1px solid; border-left: 0px; margin-top: -1px; width: 50px; height: 32px;">' . $this->item_content ['flagText'] . '</div>
		<div class="ym-gl" style="border: #333 1px solid; border-left: 0px; margin-top: -1px; width: 500px; height: 32px;">' . $element_info . '</div>
		<div class="ym-clearfix">&nbsp;</div>';
	}
	public function appendDataImportFile($arrayObject) {
		if (! ($arrayObject instanceof \ArrayObject)) {
			throw new \Exception ( "Given Parameter is not ArrayObject" );
		}
		$OwnerId = UserManagement::getInstance ()->getCurrentUser ()->getId ();
		$CharacterID = $this->item_content ['CharacterID'];
		if ($this->getParent () != null) {
			$parent_id = $this->getParent ()->getItemId ();
		} else
			$parent_id = 0;
		
		if (! isset ( $this->quantity ))
			$this->quantity = 1;
		
		$file_line = "\"" . $OwnerId . "\",\"$CharacterID\",\"" . $this->item_content ['typeID'] . "\",\"" . $this->getItemId () . "\",\"" . $this->item_content ['locationID'] . "\",\"" . $this->getCount () . "\",\"" . $this->getFlag () . "\",\"" . $parent_id . "\",\"" . $this->item_content ['singleton'] . "\"\n";
		$arrayObject->append ( $file_line );
		
		$iterator = $this->getIterator ();
		while ( $iterator->current () ) {
			$iterator->current ()->setItemContent ( 'locationID', $this->item_content ['locationID'] );
			$iterator->current ()->appendDataImportFile ( $arrayObject );
			$iterator->next ();
		}
	}
	public function appendSQLInsert($arrayObject) {
		if (! ($arrayObject instanceof \ArrayObject)) {
			throw new \Exception ( "Given Parameter is not ArrayObject" );
		}
		$OwnerId = UserManagement::getInstance ()->getCurrentUser ()->getId ();
		$CharacterID = $this->item_content ['CharacterID'];
		
		if ($this->getParent () != null) {
			$parent_id = $this->getParent ()->getItemId ();
		} else
			$parent_id = 0;
		
		$sql = "INSERT INTO emb_user_assets (`OwnerID`,
												`characterID`,
												`typeID`,
												`itemID`,
												`locationID`,
												`quantity`,
												`flag`,
												`parentItemID`,
												`singleton`
												)
				VALUES ('" . $OwnerId . "',
						'$CharacterID',
						 '" . $this->item_content ['typeID'] . "',
						 '" . $this->getItemId () . "',
						 '" . $this->item_content ['locationID'] . "',
						 '" . $this->getCount () . "',
						 '" . $this->getFlag () . "',
						 " . $parent_id . ",
						 '" . $this->item_content ['singleton'] . "');
						 		\n";
		$arrayObject->append ( $sql );
		$iterator = $this->getIterator ();
		while ( $iterator->current () ) {
			$iterator->current ()->setItemContent ( 'locationID', $this->item_content ['locationID'] );
			$iterator->current ()->appendSQLInsert ( $arrayObject );
			$iterator->next ();
		}
	}
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\EvE\ItemList::DB_Store()
	 */
	public function DB_Store() {
		// TODO Auto-generated method stub
		throw new \Exception ( "This method is not being used" );
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \ClassLibrary\EvE\ItemList::DB_Delete()
	 */
	public function DB_Delete() {
		// TODO Auto-generated method stub
		throw new \Exception ( "This method is not being used" );
	}
}

?>