<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary>system;

class ForumUser {
	
	private $persons;
	public static $instance;
	public static function getInstance() {
		if(empty(ForumUser::$instance)) {
			ForumUser::$instance = new ForumUser();
		}
		return ForumUser::$instance;
	}
	
	private function __construct() {
		
	}
	
	public function loadForumUserId($fuid) {
		global $user;
		if($fuid <= 0)
			throw new Exception("LoadForumUserId in ForumUser.php has no id defined");
		if(empty($this->persons['$fuid'])) {
			$SQL = "SELECT * FROM emb_forum_user WHERE forum_user_id = '".$fuid."'";
			$res = Database::getInstance()->sql_query($SQL);
			$row = Database::getInstance()->sql_fetch_assoc($res);
			if(empty($row)) {
				$identifier = $this->generateTempKey(10);
				$sql_insert = "";
				
				$sql_insert = "INSERT INTO emb_forum_user (forum_user_id, ember_user_id) VALUES ();";
			}
		}
	}
	
	private function createNewEmbschemaUser($name, $password, $email, $temp_identifier) {
		$sql = "INSERT INTO emb_user (name, password, email, sys_user, temp_identifier) VALUES ('.$name.','.$password.','.$email.','5','.$temp_identifier.')";
		
		
	}
	
	private  function generateTempKey($length = 8) {
        $possibleChars = "abcdefghijklmnopqrstuvwxyz";
        $password = '';

        for($i = 0; $i < $length; $i++) {
            $rand = rand(0, strlen($possibleChars) - 1);
            $password .= substr($possibleChars, $rand, 1);
        }

        return $password."_".time();
    }
	
}


?>