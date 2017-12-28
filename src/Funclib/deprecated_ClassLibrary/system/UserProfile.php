<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\Displayable;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\UserManagement;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\TemplateReader;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIKey;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;

class UserProfile implements Displayable {
	private $currentUser;
	private $errorList;
	
	function __construct() {
		$this->setUser ( UserManagement::getInstance ()->getCurrentUser () );
		$this->errorList = new ErrorHandler ();
		if (isset ( $_GET ['del_emb_api'] )) {
			$API = new APIKey ( $_GET ['del_emb_api'], "" );
			$API->setAPI ( $_GET ['del_emb_api'], "random" );
			$API->DB_Delete ();
		}
	}
	
	public function getOutput() {
		$tmplReader = new TemplateReader ();
		$tmplReader->readFile ( TMPL_PATH. "UserProfile.html" );
		
		if (isset ( $_GET ['change_pw'] )) {
			$op = $this->changePW ();
			$tmplReader->inputVariable ( "PROFILE_CONTENT", $op );
		} elseif (isset ( $_GET ['change_email'] )) {
			$op = $this->changeEmail ();
			$tmplReader->inputVariable ( "PROFILE_CONTENT", $op );
		} elseif (isset ( $_GET ['add_api'] )) {
			$op = $this->addAPIKey ();
			$tmplReader->inputVariable ( "PROFILE_CONTENT", $op );
		} else {
			$list = APIManager::getInstance ()->getCharacterList ();
			$opCharacters = "";
			if (! empty ( $list )) {
				foreach ( $list as $character ) {
					$opCharacters .= $character->getOutput ();
				}
			}
			$tmplReader->inputVariable ( "PROFILE_CONTENT", $opCharacters );
		}
		
		try {
			$this->currentUser->loadAPI_Basic ();
		} catch ( \Exception $API_Error ) {
			ErrorHandler::getErrorHandler ()->addException ( $API_Error );
		}
		
		$tmplReader->inputVariable ( "TITLE_B1", "Profile: " . $this->currentUser->getName () );
		$tmplReader->inputVariable ( "USER_EMAIL", $this->currentUser->getEmail () );
		$tmplReader->inputVariable ( "LAST_LOGIN", date ( "d. M. Y - H:i", $this->currentUser->getLastlogin () ) . " CET" );
		$tmplReader->inputVariable ( "DELETE_ACC_HREF", "?id=0&adela" );
		
		if (! isset ( $_GET ['add_api'] ))
			$tmplReader->inputVariable ( "ADD_API_KEY", '<p><a class="ym-button" href="?id=2&add_api">Add EvE API Key</a></p>' );
		
		$stringdump_apilist = $this->getAPIList ();
		$tmplReader->inputVariable ( "API_KEY_LIST", $stringdump_apilist );
		
		$tmplReader->finalizeOutput ();
		return $tmplReader->getOutput ();
	}
	
	private function getAPIList() {
		$api_key_list = "";
		$apilist = APIManager::getInstance ()->getAPIList ();
		
		if (count ( $apilist ) > 0) {
			$TableCreator = new TableCreator ( 4 );
			$TableCreator->loadTemplateFiles ( array (
					'outer_box' => TMPL_PATH . "Tables/APIList_Table.html",
					'inner_box' => TMPL_PATH . "Tables/APIList_TableElement.html" 
			) );
			$class_arr = array (
					"ym-grid-table-titleline",
					"ym-grid-table-titleline",
					"ym-grid-table-titleline",
					"ym-grid-table-titleline" 
			);
			$TableCreator->addContent ( array (
					"API-ID",
					"vCODE",
					"Access-Mask",
					"&nbsp;" 
			), $class_arr );
			
			foreach ( $apilist as $api_listitem ) {
				$cArr = array (
						$api_listitem->getAPIKey (),
						FuncLib::shortenText ( $api_listitem->getVCode (), 20 ),
						$api_listitem->getAccessMask (),
						'<a href="?id=2&del_emb_api=' . $api_listitem->getAPIKey () . '" class="">Delete API</a>' 
				);
				$TableCreator->addContent ( $cArr, array () );
			}
			return $TableCreator->getOutput ();
		}
	}
	private function changePW() {
		$errorHandler = new ErrorHandler ();
		if (isset ( $_POST ['hidden_pw_change'] )) {
			if ($this->currentUser->getMd5Pw () == Person::md5_pw ( $_POST ['verify_old_pw'] ) && Person::md5_pw ( $_POST ['new_pw_1'] ) == Person::md5_pw ( $_POST ['new_pw_2'] ) && strlen ( $_POST ['new_pw_1'] ) > 7) {
				$this->currentUser->changeMd5PW ( Person::md5_pw ( $_POST ['new_pw_1'] ) );
				$this->currentUser->LoadFromDB ( $this->currentUser->getId () );
				
				return "<div class=\"box info\" style=\"margin: 5em 20%;\"><h3>Password successfully changed!</h3></div>";
			} elseif ($this->currentUser->getMd5Pw () != Person::md5_pw ( $_POST ['verify_old_pw'] )) {
				$errorHandler->addError ( "The old password is not correct" );
			} elseif (Person::md5_pw ( $_POST ['new_pw_1'] ) != Person::md5_pw ( $_POST ['new_pw_2'] )) {
				$errorHandler->addError ( "The new passwords do not match" );
			} elseif (strlen ( $_POST ['new_pw_1'] ) < 8) {
				$errorHandler->addError ( "The new password is too short" );
			} else {
				$errorHandler->addError ( "The new passwords do not match" );
				$errorHandler->addError ( "The old password is not correct" );
			}
		}
		
		$changePWForm = new TemplateReader ();
		$changePWForm->readFile ( TMPL_PATH."Forms/ChangePW.html" );
		if ($errorHandler->getCount () > 0) {
			$changePWForm->inputVariable ( "INPUT_ERR_BOX", $errorHandler->getOutput () );
		}
		$changePWForm->finalizeOutput ();
		return $changePWForm->getOutput ();
	}
	
	private function changeEmail() {
		$this->errorList = new ErrorHandler ();
		if (isset ( $_POST ['hidden_email_change'] )) {
			if (isset ( $_POST ['new_email'] ) && FuncLib::is_valid_email ( $_POST ['new_email'] )) {
				$this->currentUser->changeEmail ( $_POST ['new_email'] );
				$this->currentUser->LoadFromDB ( $this->currentUser->getId () );
			} else {
				$this->errorList->addError ( "The given E-Mail is not correct" );
			}
		}
		$changePWForm = new TemplateReader ();
		$changePWForm->readFile ( TMPL_PATH."Forms/ChangeEmail.html" );
		if ($this->errorList->getCount () > 0) {
			$changePWForm->inputVariable ( "INPUT_ERR_BOX", $this->errorList->getOutput () );
		}
		$changePWForm->finalizeOutput ();
		return $changePWForm->getOutput ();
	}
	
	private function addAPIKey() {
		$TmplReader = new TemplateReader ();
		$TmplReader->readFile ( TMPL_PATH."Forms/API_Key_Form.html" );
		
		if (isset ( $_POST ['api_key_id_0'] )) {
			$api_key = FuncLib::makePostInputSafe ( $_POST ['api_key_id_0'] );
		} else {
			$api_key = "";
		}
		
		$TmplReader->inputVariable ( "API_KEY", $api_key );
		$TmplReader->inputVariable ( "API_KEY_ID", 0 );
		
		if (isset ( $_POST ['api_key_id_0'] )) {
			$api_key_vcode = FuncLib::makePostInputSafe ( $_POST ['api_key_vcode_0'] );
		} else {
			$api_key_vcode = "";
		}
		
		$TmplReader->inputVariable ( "API_VCODE", $api_key_vcode );
		$TmplReader->inputVariable ( "API_VCODE_ID", 0 );
		$TmplReader->finalizeOutput ();
		
		$form_container = new TemplateReader ();
		$form_container->readFile ( TMPL_PATH. "API_form_container.html" );
		$form_container->inputVariable ( "FORM_CONTENT", $TmplReader->getOutput () );
		$form_container->inputVariable ( "FORM_ACTION", "?id=2&add_api" );
		
		if (isset ( $_POST ['api_key_id_0'] ) && isset ( $_POST ['api_key_vcode_0'] )) {
			if (empty ( $_POST ['api_key_id_0'] ) || empty ( $_POST ['api_key_vcode_0'] )) {
				$this->errorList->addError ( "You must provide an API Key with ID and vCode!" );
			}
			
			$APIObj = new APIKey ( $api_key, $api_key_vcode );
			try {
				$APIObj->loadPheal ();
			} catch ( \Exception $e ) {
				$this->errorList->addError ( $e->getMessage () );
			}
			
			if ($this->errorList->getCount () < 1 && ErrorHandler::getErrorHandler ()->getCount () < 1) {
				$APIObj->DB_Store ();
				return '<br><br><h5>API Key stored</h5><br><a href="?id=2add_api" class="ym-button"/>Add API-Key</a>';
			}
		}
		
		if ($this->errorList->getCount () > 0)
			$form_container->inputVariable ( "ERROR_CONTENT", $this->errorList->getOutput () );
		
		$form_container->finalizeOutput ();
		return $form_container->getOutput ();
	}
	private function setUser(Person $p) {
		$this->currentUser = $p;
	}
}

?>