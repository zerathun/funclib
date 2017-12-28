<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\Group;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Storable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\FuncLib;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\EvEAccount;


/*
 * use system\Database;
 * use Group;
 * namespace ClassLibrary\Ifaces;
 * use ClassLibrary\Ifaces\Displayable;
 * use Ifaces\EmbDB_Storable;
 * namespace system;
 * //require_once("ClassLibrary/Ifaces/EmbDB_Storable.php");
 */
class Person implements Storable, Displayable {
	public $name;
	public $pw;
	private $uid;
	private $group;
	private $email;
	private $last_login;
	private $APIList;
	private $last_eve_character;
	
	public function __construct() {
	}
	
	public function load($name, $pw) {
		$this->name = $name;
		$this->pw = $pw;
	}
	
	/**
	 * pass either a ID or the Username (Both should be unique)
	 *
	 * @param Int/String $arg        	
	 * @throws \Exception
	 */
	public function LoadFromDB($arg) {
		
		if (intval ( $arg ) > 0) {
			$sql_end = " emb_user.id = " . intval ( $arg );
		} else {
			$username = FuncLib::makePostInputSafe ( $arg );
			$sql_end = " emb_user.name = '" . $username . "'";
		}
		
		$sql = "SELECT *,
				emb_user.name as name,
				emb_user.password as password,
				emb_user.id as id,
   				emb_user.last_eve_character as eve_char,
   				emb_user.last_eve_api as eve_api_id
			FROM emb_user LEFT JOIN emb_group ON emb_group.id = emb_user.group
			WHERE " . $sql_end;
		
		$resource = Database::getInstance ()->sql_query ( $sql );
		$row = Database::getInstance ()->sql_fetch_object ( $resource );
		if (! empty ( $row )) {
			$this->setGroup ( new Group ( $row ) );
			$this->setId ( $row->id );
			$this->setName ( $row->name );
			$this->setPassword ( $row->password );
			$this->setEmail ( $row->email );
			$this->setLastlogin ( $row->last_login );
			$this->last_eve_character = $row->eve_char;
		} else {
			throw new \Exception ( "Wrong Username given" );
		}
	}
	
	public function updateLastLogin() {
		$sql = "UPDATE emb_user SET emb_user.last_login=" . time () . " WHERE emb_user.id = " . $this->getId ();
		Database::getInstance ()->sql_query ( $sql );
	}
	
	public function setLastEvEChar($char_id, $api_key) {
		$char = APIManager::getInstance ()->getCharacter ( $char_id );
		$key = APIManager::getInstance ()->getAPIKey ( $api_key );
		
		if (! (empty ( $char ) || empty ( $key ))) {
			$this->last_eve_character = $char_id;
			$this->last_eve_api = $api_key;
		} else {
			throw new \Exception ( "Character/APIKey ID's are not valid" );
		}
	}
	
	public function getLastEvECharId() {
		return $this->last_eve_character;
	}
	
	public function setGroup($group) {
		$this->group = $group;
	}
	public function getGroup() {
		return $this->group;
	}
	public function setId($id) {
		$this->uid = $id;
	}
	public function getId() {
		return $this->uid;
	}
	public function setName($name) {
		$this->name = $name;
	}
	public function getName() {
		return $this->name;
	}
	public function setEmail($email) {
		$this->email = $email;
	}
	public function getEmail() {
		return $this->email;
	}
	public function setLastlogin($timestamp) {
		$this->last_login = $timestamp;
	}
	public function getLastlogin() {
		return $this->last_login;
	}
	public function getMd5Pw() {
		if (! empty ( $this->pw ))
			return $this->pw;
		else
			return "";
	}
	public function changeMd5PW($md5_hash) {
		$md5_hash = FuncLib::sqlInjectionSafe ( $md5_hash );
		$this->setPassword ( $md5_hash );
		$sql = "UPDATE emb_user SET emb_user.password = '" . $md5_hash . "' WHERE id = " . $this->getId ();
		Database::getInstance ()->sql_query ( $sql );
	}
	public function changeEmail($email) {
		$email = FuncLib::sqlInjectionSafe ( $email );
		$sql = "UPDATE emb_user SET emb_user.email = '" . $email . "' WHERE id = " . $this->getId ();
		Database::getInstance ()->sql_query ( $sql );
	}
	public function setPassword($tx) {
		$this->pw = $tx;
	}
	public function DB_Store() {
		$sql = "UPDATE emb_user SET emb_user.last_eve_character = '" . $this->last_eve_character . "'
				WHERE id = " . $this->getId ();
		Database::getInstance ()->sql_query ( $sql );
	}
	public function DB_Delete() {
		$sql = "DELETE FROM emb_user WHERE emb_user.id = " . $this->getId ();
		Database::getInstance ()->sql_query ( $sql );
	}
	public function checkPassword($password) {
		return ($this->getMd5Pw () == $this->md5_pw ( $password ));
	}
	public static function md5_pw($pw) {
		$result = md5 ( md5 ( $pw ) . "md-keyx53x" );
		return $result;
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \Ifaces\Displayable::getOutput()
	 */
	public function getOutput() {
		// TODO Auto-generated method stub
	}
	
	public function loadAPI_Basic() {
		APIManager::getInstance ();
		
		$this->APIList = new EvEAccount();
		$this->APIList->loadCharacters($this->getId());
	}
	public function getAPIList() {
		if (empty ( $this->APIList )) {
			$this->loadAPI_Basic ();
		}
		return $this->APIList;
	}
}
?>