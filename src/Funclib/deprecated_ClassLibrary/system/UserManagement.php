<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\Pheal\Pheal;
use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Person;

class UserManagement implements Displayable {
		private static $Instance;
		private $Template;
		private $currentUser;
		
		private function __construct() {
			
		}
	
	/**
	 *
	 * @return \ClassLibrary\system\UserManagement;
	 */
	public static function getInstance() {
		if (empty ( UserManagement::$Instance )) {
			UserManagement::$Instance = new UserManagement ();
		}
		return UserManagement::$Instance;
	}
	
	public function initialize() {
		$this->loadUser ();
	}
	
	public function getUserTopMenu() {
		$this->initializeTemplate();
		if ($this->isLoggedIn ()) {
			$content = "Logged in as: ";
			$logout_button = "<a class=\"ym-button\" style=\"margin-left: 1em;\" href=\"?i=1&logout=1\" >Logout<a>";
			$user_link = '<a href="?id=2">' . $this->currentUser->getName () . "</a>" . $logout_button;
			$this->Template->getElement ( "USER_NAME" )->setContent ( $user_link );
		} else {
			$content = "<input  class=\"ym-searchbutton\" type=\"submit\" value=\"Login\">";
		}
		
		$this->Template->getElement ( "USER_STATUS" )->setContent ( $content );
		
		$this->Template->finalizeOutput ();
		return "
				" . $this->Template->getOutput () . "
		";
	}
	
	private $templateInitialized = false;
	public function initializeTemplate() {
		if(!$this->templateInitialized) {
			$this->Template = new TemplateReader ();
			$this->Template->readFile ( TMPL_PATH."/USER_TOP_MENU.html" );
			
			$TemplEl_UserStat = new TemplateElement ( "USER_STATUS" );
			$this->Template->addItem ( $TemplEl_UserStat );
		}
	}
	
	
	public function isLoggedIn() {
		global $user;

		return true;
	}
	
	private function loadUser() {
		$person = new Person();
		$person->setId($user->data['user_id']);
		$this->currentUser = $person;
	}
	
	public function loadUserWID($user_id) {
		$person = new Person();
		$person->setId($user_id);
		$person->LoadFromDB ( $user_id );
		$this->currentUser = $person;
		GlVars::getGlVars ()->setVar ( "user_id", $person->getId () );
		GlVars::getGlVars ()->setVar ( "user_logged_in", 1 );
	}
	
	public function old_func_isLoggedIn() {
		$GlVars = GlVars::getGlVars ();
		if (isset ( $_GET ['logout'] ) && $_GET ['logout'] == 1) {
			GlVars::getGlVars ()->unsetVar ( "user_id" );
			GlVars::getGlVars ()->unsetVar ( "user_logged_in" );
		}
		$user_logged_in = $GlVars->getVar ( "user_logged_in" );
		if (! empty ( $user_logged_in ) && $user_logged_in->getValue () == 1) {
			return true;
		}
		return false;
	}
	
	private function old_func_loadUser() {
		$GlVars = GlVars::getGlVars ();
		
		if (! $this->isLoggedIn ()) {
			foreach ( $_POST as $key => $evtl_inj ) {
				if (is_string ( $evtl_inj )) {
					$_POST [$key] = FuncLib::sqlInjectionSafe ( $evtl_inj );
				}
			}
			
			if (isset ( $_POST ['login_hidden_value'] ) && isset ( $_POST ['login_name'] ) && isset ( $_POST ['login_pw'] )) {
				$person = new Person ();
				
				try {
					$person->LoadFromDB ( $_POST ['login_name'] );
					if ($person->checkPassword ( $_POST ['login_pw'] )) {
						$this->status ['pw_check'] = 1;
						$GlVars->setVar ( 'user_logged_in', 1 );
						$GlVars->setVar ( 'user_id', $person->getId () );
						$this->currentUser = $person;
					} else {
						$this->status ['pw_check'] = 0;
						$this->loadGuestUser ();
					}
				} catch ( \Exception $e ) {
					$this->status ['pw_check'] = 0;
					$this->loadGuestUser ();
				}
			} else {
				$this->loadGuestUser ();
			}
		} else {
			$uid_Obj = GlVars::getGlVars ()->getVar ( "user_id" );
			if (! empty ( $uid_Obj )) {
				$uid = $uid_Obj->getValue ();
			} else {
				throw new \Exception ( "USER-ID Variable is not set!" );
			}
			
			if (isset ( $_GET ['adela'] )) {
				try {
					$person = new Person ();
					$person->LoadFromDB ( $uid );
					$person->DB_Delete ();
				} catch ( \Exception $e ) {
					throw new \Exception ( "Error deleting the user" );
				}
			}
			
			try {
				$person = new Person ();
				$person->LoadFromDB ( $uid );
				$this->currentUser = $person;
				GlVars::getGlVars ()->setVar ( "user_id", $person->getId () );
				GlVars::getGlVars ()->setVar ( "user_logged_in", 1 );
			} catch ( \Exception $e ) {
				GlVars::getGlVars ()->unsetVar ( "user_id" );
				GlVars::getGlVars ()->unsetVar ( "user_logged_in" );
				$this->loadGuestUser ();
			}
		}
		
		if (empty ( $this->currentUser ) || ! ($this->currentUser instanceof Person)) {
			throw new \Exception ( "User is not set" );
		}
	}
	private function loadGuestUser() {
		// Load Guest-User
		try {
			$person = new Person ();
			$person->LoadFromDB ( "Guest" );
			$this->currentUser = $person;
		} catch ( \Exception $e ) {
			print_r ( $e->getMessage () );
			ErrorHandler::getErrorHandler ()->addException ( $e );
		}
	}
	
	/**
	 *
	 * @throws \Exception
	 * @return \ClassLibrary\system\Person
	 */
	public function getCurrentUser() {
		if (empty ( $this->currentUser ))
			throw new \Exception ( "Current user is not set" );
		return $this->currentUser;
	}
	
	private $status;
	private function createNewUser($name, $pw, $sys_user = 0) {
		$sql = "INSERT INTO emb_user (name,password,sys_user) VALUES ('$name','$pw',1)";
		
		Database::getInstance ()->sql_query ( $sql );
	}
	public function getOutput() {
		$this->initializeTemplate();
		$TmplReader = new TemplateReader ( TMPL_PATH . "Login.html" );
		$TmplReader->inputVariable ( "TITLE_B1", $this->getTitle () );
		
		if (! $this->isLoggedIn ()) {
			$LoginForm = new TemplateReader ( TMPL_PATH . "Forms/LoginForm.html" );
			$LoginForm->inputVariable ( "ACTION_VARIABLE", "?id=0" );
			$value = isset ( $_POST ['login_name'] ) ? $_POST ['login_name'] : "";
			if (isset ( $this->status ['pw_check'] ) && $this->status ['pw_check'] == 0) {
				$LoginForm->inputVariable ( "LOGIN_MESSAGE", "Username or Password were wrong!" );
			}
			
			$LoginForm->inputVariable ( "LOGIN_NAME_VALUE", $value );
			$LoginForm->finalizeOutput ();
			$TmplReader->inputVariable ( "LOGIN_CONTENT", $LoginForm->getOutput () );
		} else {
		}
		
		$TmplReader->finalizeOutput ();
		return $TmplReader->getOutput ();
	}
	
	public function getTitle() {
		if ( $this->isLoggedIn () ) {
			return "Active User: " . $this->currentUser->getName ();
		} else {
			return "Login";
		}
	}
	public function getRegistrationForm() {
		$this->initializeTemplate();
		$validate_registration_form = true;
		$ErrorBox = new TemplateReader ();
		$ErrorBox->readFile ( "Templates/ErrorWindow.html" );
		$ErrorMsg = new TemplateReader ();
		$ErrorMsg->readFile ( "Templates/ErrorMsg.html" );
		
		$Template = new TemplateReader ();
		$Template->readFile ( "Templates/Registration.html" );
		
		$RegForm_Tmpl = new TemplateReader ();
		$RegForm_Tmpl->readFile ( "Templates/Forms/RegistrationForm.html" );
		
		$RegForm_Tmpl->inputVariable ( "ACTION_VARIABLE", "" );
		
		if (isset ( $_POST ["reg_username"] ) && isset ( $_POST ["reg_pw"] ) && isset ( $_POST ["reg_pw_rep"] )) {
			if (isset ( $_POST ['add_keys'] )) {
				if (GlVars::getGlVars ()->getVar ( "api_key_reg_count" ) != null) {
					GlVars::getGlVars ()->setVar ( "api_key_reg_count", GlVars::getGlVars ()->getVar ( "api_key_reg_count" )->getValue () + 1 );
				} else {
					GlVars::getGlVars ()->setVar ( "api_key_reg_count", 1 );
				}
			}
			
			$_POST ['reg_username'] = FuncLib::sqlInjectionSafe ( $_POST ['reg_username'] );
			GlVars::getGlVars ()->setVar ( "reg_username", $_POST ['reg_username'], false );
			$sql = "SELECT count(id) as numbr FROM emb_user WHERE name = '" . $_POST ['reg_username'] . "'";
			$resource = Database::getInstance ()->sql_query ( $sql );
			$count_numbers = Database::getInstance ()->sql_fetch_object ( $resource );
			if (strlen ( $_POST ['reg_username'] ) < 3 || $count_numbers->numbr > ( int ) 0) {
				$validate_registration_form = false;
				if ($count_numbers->numbr > ( int ) 0) {
					$ErrorMsg->inputVariable ( "ERROR_MSG", "Username already exists" );
				} else {
					$ErrorMsg->inputVariable ( "ERROR_MSG", "Username too short" );
				}
				$RegForm_Tmpl->inputVariable ( "CSS_CLASS_USERNAME", "false_input" );
				try {
					$msg = $ErrorBox->getElement ( "ERROR_MESSAGE" );
					$ErrorMsg->finalizeOutput ();
					$msg->setContent ( $msg->getContent () . $ErrorMsg->getOutput () );
					$ErrorMsg->clearTemplateContentVars ();
				} catch ( \Exception $e ) {
				}
			}
			
			$_POST ['reg_email'] = FuncLib::makePostInputSafe ( $_POST ['reg_email'] );
			GlVars::getGlVars ()->setVar ( "reg_email", $_POST ['reg_email'], false );
			if (! FuncLib::is_valid_email ( $_POST ['reg_email'] )) {
				$validate_registration_form = false;
				$ErrorMsg->inputVariable ( "ERROR_MSG", "Invalid E-Mail" );
				$RegForm_Tmpl->inputVariable ( "CSS_CLASS_EMAIL", "false_input" );
				try {
					$msg = $ErrorBox->getElement ( "ERROR_MESSAGE" );
					$ErrorMsg->finalizeOutput ();
					$msg->setContent ( $msg->getContent () . $ErrorMsg->getOutput () );
					$ErrorMsg->clearTemplateContentVars ();
				} catch ( \Exception $e ) {
					throw new \Exception ();
				}
			}
			
			$reg_pw = ( string ) $_POST ['reg_pw'];
			$reg_pw_rep = ( string ) $_POST ['reg_pw_rep'];
			$_POST ["reg_pw"] = FuncLib::makePostInputSafe ( $_POST ['reg_pw'] );
			GlVars::getGlVars ()->setVar ( "reg_pw", $_POST ['reg_pw'], false );
			$_POST ["reg_pw_rep"] = FuncLib::makePostInputSafe ( $_POST ['reg_pw_rep'] );
			GlVars::getGlVars ()->setVar ( "reg_pw_rep", $_POST ['reg_pw_rep'], true );
			
			if ($reg_pw != $reg_pw_rep || $_POST ["reg_pw"] != $reg_pw || $_POST ["reg_pw_rep"] != $reg_pw_rep || strlen ( $_POST ["reg_pw_rep"] ) < 8) {
				$RegForm_Tmpl->inputVariable ( "CSS_CLASS_PW1", "false_input" );
				$RegForm_Tmpl->inputVariable ( "CSS_CLASS_PW2", "false_input" );
				$ErrorMsg->inputVariable ( "ERROR_MSG", "Passwords do not match or contain a not valid format. The length must be over 7 letters/digit." );
				$validate_registration_form = false;
				$msg = $ErrorBox->getElement ( "ERROR_MESSAGE" );
				$ErrorMsg->finalizeOutput ();
				$msg->setContent ( $msg->getContent () . $ErrorMsg->getOutput () );
				$ErrorMsg->clearTemplateContentVars ();
			}
			
			$RegForm_Tmpl->inputVariable ( "REG_USERNAME", $_POST ['reg_username'] );
			$RegForm_Tmpl->inputVariable ( "REG_PW", $_POST ["reg_pw"] );
			$RegForm_Tmpl->inputVariable ( "REG_PW_REP", $_POST ["reg_pw_rep"] );
			$RegForm_Tmpl->inputVariable ( "REG_EMAIL", $_POST ['reg_email'] );
			
			if (GlVars::getGlVars ()->getVar ( "api_key_reg_timestamp" ) != null && GlVars::getGlVars ()->getVar ( "api_key_reg_timestamp" )->getValue () < time ()) {
				$cVars = (GlVars::getGlVars ()->getVar ( "api_key_reg_count" ) != null) ? GlVars::getGlVars ()->getVar ( "api_key_reg_count" )->getValue () : 0;
				GlVars::getGlVars ()->setVar ( "api_key_reg_count", 1 );
				
				for($api_arr_result = 0; $api_arr_result < $cVars; $api_arr_result ++) {
					GlVars::getGlVars ()->unsetVar ( "api_key_reg_$api_arr_result" );
				}
			}
			
			GlVars::getGlVars ()->setVar ( "api_key_reg_timestamp", time () + 300 );
			if (GlVars::getGlVars ()->getVar ( "api_key_reg_count" ) == null) {
			}
			$cVars = GlVars::getGlVars ()->getVar ( "api_key_reg_count" )->getValue ();
			
			$reg_form_output = "";
			$error_arr = array ();
			for($xxx = 0; $xxx < $cVars; $xxx ++) {
				$reg_form_output .= $this->getAPIKeyForm ( $xxx, $error_arr );
				if (! $error_arr [$xxx]) {
					$flag = true;
					$ErrorMsg->inputVariable ( "ERROR_MSG", "The " . (1 + $xxx) . ". API Key or ID is invalid" );
					$validate_registration_form = false;
					$msg = $ErrorBox->getElement ( "ERROR_MESSAGE" );
					$ErrorMsg->finalizeOutput ();
					$msg->setContent ( $msg->getContent () . $ErrorMsg->getOutput () );
					$ErrorMsg->clearTemplateContentVars ();
				}
			}
			
			$RegForm_Tmpl->inputVariable ( "API_KEY_FORM", $reg_form_output );
			$API_USER_DETAIL = "";
			$API_Array = array ();
			// Read API Variables and display the Characters
			for($y = 0; $y < $cVars; $y ++) {
				if ($error_arr [$y]) {
					try {
						$api_id = GlVars::getGlVars ()->getVar ( "api_key_id_$y" )->getValue ();
					} catch ( \Exception $e ) {
						print $e->getTraceAsString ();
					}
					;
					try {
						$api_key = GlVars::getGlVars ()->getVar ( "api_key_vcode_$y" )->getValue ();
					} catch ( \Exception $e ) {
						print $e->getTraceAsString ();
					}
					;
					// $pheal = new Pheal("3811545", "ZpyAI7boFFSLvCyiV89GaVod1YhSQAa85hegCJCBzJ23inJnDvmYNnVJsudqLFsO");
					if (strlen ( $api_key ) > 1 && strlen ( $api_id ) > 1) {
						$api_arr_result = array ();
						try {
							$pheal = new Pheal ( $api_id, $api_key );
							$pheal->setAccess ( "Account", "32768" );
							$result = $pheal->APIKeyInfo ();
							$api_arr_result = $result->toArray ();
							$API_Array [$y] = $result->toArray ();
						} catch ( \Exception $e ) {
							$ErrorMsg->inputVariable ( "ERROR_MSG", "The " . (1 + $xxx) . ". API Key or ID is invalid: " . $e->getMessage () );
							$validate_registration_form = false;
							$msg = $ErrorBox->getElement ( "ERROR_MESSAGE" );
							$ErrorMsg->finalizeOutput ();
							$msg->setContent ( $msg->getContent () . $ErrorMsg->getOutput () );
							$ErrorMsg->clearTemplateContentVars ();
							$API_Array [$y] = array ();
						}
						
						if (isset ( $api_arr_result ['result'] ['key'] )) {
							$EvE_Char_Tmpl = new TemplateReader ( "Templates/API_Display_Reg.html" );
							
							$exp = ! empty ( $api_arr_result ['result'] ['key'] ['expires'] ) ? $api_arr_result ['result'] ['key'] ['expires'] : "No expiry";
							$EvE_Char_Tmpl->inputVariable ( "API_EXPIRATION", $exp );
							$EvE_Char_Tmpl->inputVariable ( "API_ACCESS_MASK", $api_arr_result ['result'] ['key'] ['accessMask'] );
							$EvE_Char_Tmpl->inputVariable ( "API_DISP_ID", $api_id );
							$EvE_Char_Tmpl->inputVariable ( "API_DISP_VCODE", $api_key );
							
							$chars = "";
							foreach ( $api_arr_result ['result'] ['key'] ['characters'] as $key => $element ) {
								$char_tmpl = new TemplateReader ( "Templates/CharacterList.html" );
								$char_tmpl->inputVariable ( "CHAR_ID", $element ["characterID"] );
								$char_tmpl->inputVariable ( "CHAR_NAME", $element ["characterName"] );
								$char_tmpl->inputVariable ( "CHAR_CORP", $element ["corporationName"] );
								$char_tmpl->inputVariable ( "CHAR_ALLY", $element ["allianceName"] );
								$char_tmpl->inputVariable ( "CORP_ID", $element ["corporationID"] );
								$char_tmpl->inputVariable ( "ALLIANCE_ID", $element ["allianceID"] );
								$char_tmpl->finalizeOutput ();
								$chars .= $char_tmpl->getOutput ();
							}
							$EvE_Char_Tmpl->inputVariable ( "EVE_CHARS", $chars );
							
							$EvE_Char_Tmpl->finalizeOutput ();
							$API_USER_DETAIL .= $EvE_Char_Tmpl->getOutput ();
						}
					}
				}
			}
			$RegForm_Tmpl->inputVariable ( "API_USER_DISPLAY", $s = isset ( $API_USER_DETAIL ) ? $API_USER_DETAIL : "" );
		} else {
			GlVars::getGlVars ()->setVar ( "api_key_reg_count", 1 );
			$RegForm_Tmpl->inputVariable ( "API_KEY_FORM", $this->getAPIKeyForm ( 0, $arr = array () ) );
		}
		
		if ($validate_registration_form && isset ( $_POST ['reg_username'] )) {
			
			if (isset ( $_POST ['reg_user_final'] ) && $_POST ['reg_user_final'] = "Register") {
				$sql = "
						INSERT INTO emb_user (name, password)
							VALUES ('" . $_POST ['reg_username'] . "','" . Person::md5_pw ( $_POST ['reg_pw'] ) . "');	
							";
				$resource = Database::getInstance ()->sql_query ( $sql );
				$sql = "SELECT id FROM emb_user WHERE name = '" . $_POST ['reg_username'] . "'";
				$resource = Database::getInstance ()->sql_query ( $sql );
				$user_id = Database::getInstance ()->sql_fetch_object ( $resource );
				$cVars = isset ( $cVars ) ? $cVars : 0;
				for($z = 0; $z < $cVars; $z ++) {
					$sql = "INSERT INTO emb_api (id,vCode,user_id) VALUES ('" . $_POST ['api_key_id_' . $z] . "','" . $_POST ['api_key_vcode_' . $z] . "','" . $user_id->id . "');";
					try {
						Database::getInstance ()->sql_query ( $sql );
						return $this->getOutput ();
					} catch ( \Exception $e ) {
						$expl_string = explode ( '<br>', $e->getMessage () );
						$ErrorMsg->inputVariable ( "ERROR_MSG", "Database Error: " . $expl_string [count ( $expl_string ) - 1] );
						$validate_registration_form = false;
						$msg = $ErrorBox->getElement ( "ERROR_MESSAGE" );
						$ErrorMsg->finalizeOutput ();
						$msg->setContent ( $msg->getContent () . $ErrorMsg->getOutput () );
						$ErrorMsg->clearTemplateContentVars ();
						$sql = "DELETE FROM emb_user WHERE id = " . $user_id->id;
						Database::getInstance ()->sql_query ( $sql );
						$ErrorBox->finalizeOutput ();
						$RegForm_Tmpl->inputVariable ( "ERROR_MESSAGE", $ErrorBox->getOutput () );
						$RegForm_Tmpl->inputVariable ( "REGISTER_BUTTON_STATUS", "disabled" );
					}
				}
			}
		} else {
			$ErrorBox->finalizeOutput ();
			if (! $validate_registration_form) {
				$RegForm_Tmpl->inputVariable ( "ERROR_MESSAGE", $ErrorBox->getOutput () );
			}
			$RegForm_Tmpl->inputVariable ( "REGISTER_BUTTON_STATUS", "disabled" );
		}
		
		$RegForm_Tmpl->finalizeOutput ();
		$Template->inputVariable ( "REGISTRATION_CONTENT", $RegForm_Tmpl->getOutput () );
		
		$Template->finalizeOutput ();
		return $Template->getOutput ();
	}
	
	public function getAPIKeyForm($index, &$error_array) {
		$api_id = ! empty ( $_POST ["api_key_id_$index"] ) ? $_POST ["api_key_id_$index"] : "";
		$api_key = ! empty ( $_POST ["api_key_vcode_$index"] ) ? $_POST ["api_key_vcode_$index"] : "";
		
		if ((preg_match ( '/\A[a-zA-Z0-9]{64}\z/', $api_key ) && preg_match ( '/\A\d{5,12}\z/', $api_id )) || (strlen ( $api_key ) < 1 && strlen ( $api_id ) < 1)) {
			GlVars::getGlVars ()->setVar ( "api_key_id_$index", $api_id, false );
			GlVars::getGlVars ()->setVar ( "api_key_vcode_$index", $api_key, true );
			
			$error_array [$index] = true;
		} else {
			$error_array [$index] = false;
		}
		
		$API_Key_tmpl = new TemplateReader ();
		$API_Key_tmpl->readFile ( TMPL_PATH."/Forms/API_Key_Form.html" );
		$API_Key_tmpl->inputVariable ( "API_KEY", $api_id );
		$API_Key_tmpl->inputVariable ( "API_KEY_ID", $index );
		$API_Key_tmpl->inputVariable ( "API_VCODE", $api_key );
		$API_Key_tmpl->inputVariable ( "API_VCODE_ID", $index );
		$API_Key_tmpl->finalizeOutput ();
		return $API_Key_tmpl->getOutput ();
	}
}

?>