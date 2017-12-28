<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;

class Group implements Storable {
	private $access_list;
	
	/**
	 * DEFINE ACCESS LEVEL
	 *
	 * SYS_USER_GROUP
	 */
	
	/**
	 * ACCESS LEVEL
	 *
	 * @param unknown $row        	
	 */
	function __construct($row = array()) {
		if (! empty ( $row ) && is_object ( $row )) {
			$this->access_list ['admin'] = ( bool ) $row->admin ? true : false;
		} else
			throw new \Exception ( "Exception Message: " . $row );
	}
	
	/*
	 * id int(8) Nein kein(e) AUTO_INCREMENT Bearbeiten Bearbeiten Löschen Löschen
	 * Primärschlüssel Primärschlüssel
	 * Unique Unique
	 * Index Index
	 * Räumlich Räumlich
	 * Mehr
	 * 2 name varchar(255) utf8_bin Nein kein(e) Bearbeiten Bearbeiten Löschen Löschen
	 * Primärschlüssel Primärschlüssel
	 * Unique Unique
	 * Index Index
	 * Räumlich Räumlich
	 * Mehr
	 * 3 admin int(1) Nein 0 Bearbeiten Bearbeiten Löschen Löschen
	 * Primärschlüssel Primärschlüssel
	 * Unique Unique
	 * Index Index
	 * Räumlich Räumlich
	 * Mehr
	 * 4 user_mng int(1) Nein 0 Bearbeiten Bearbeiten Löschen Löschen
	 * Primärschlüssel Primärschlüssel
	 * Unique Unique
	 * Index Index
	 * Räumlich Räumlich
	 * Mehr
	 * 5 sys_user_grp
	 */
	private $KeyList_wAcc;
	public function loadKeyList($user) {
	}
	public function hasAccess($arg) {
		if (intval ( $this->access_list ['admin'] ) == 1) {
			return true;
		} else {
			if (isset ( $this->access_list [$arg] )) {
				return ( bool ) $this->access_list [$arg];
			} else
				return false;
		}
	}
	/*
	 * (non-PHPdoc)
	 * @see EmbDB_Storable::DB_Store()
	 */
	public function DB_Store() {
		// TODO Auto-generated method stub
	}
	public function DB_Delete() {
	}
}

?>