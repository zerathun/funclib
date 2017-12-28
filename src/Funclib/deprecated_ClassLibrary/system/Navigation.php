<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\EList;

class Navigation {
	private $navigation_items;
	private static $Navigation;
	private $current_site;
	
	private function __construct() {
		$this->navigation_items = new EList ();
	}
	
	/**
	 * Call static instance / create new Object if inexistent
	 */
	public static function getNavigation() {
		if (empty ( Navigation::$Navigation )) {
			Navigation::$Navigation = new Navigation ();
		}
		return Navigation::$Navigation;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getFinalizedOutput() {
		$CU = UserManagement::getInstance ()->getCurrentUser ();
		
		$curr_id = GlVars::emb_request_var('id', "_GET", 0);
		$sid = GlVars::emb_request_var('sid', "_GET", "");
		if(strlen($sid) > 0) {
			$sid = "&sid=".$sid;
		} else $sid = "";
		
		/*
		if(defined('IN_PHPBB') && IN_PHPBB) {
			$curr_id = request_var("id", 0);
		}
		if (isset ( $_GET ['id'] )) {
			$curr_id = $_GET ['id'];
		} else {
			$curr_id = 0;
		}
		*/
		
		
		$result = "<ul class=\"emb_ul\">";
		
		if (is_object($CU->getGroup ()) && $CU->getGroup ()->hasAccess ( 'usr_mng' )) {
			$result .= $this->getLink ( "User Management", "?id=6".$sid, ($curr_id == 6) );
		}
		
		if (is_object($CU->getGroup ()) && $CU->getGroup ()->hasAccess ( 'assets_access' )) {
			$result .= $this->getLink ( "Assets", "?id=3".$sid, ($curr_id == 3) );
		}
		
		if (is_object($CU->getGroup ()) && $CU->getGroup ()->hasAccess ( 'logistics' )) {
			$result .= $this->getLink ( "Logistics", "?id=4$sid", ($curr_id == 4) );
		}
		
		if (is_object($CU->getGroup ()) && $CU->getGroup ()->hasAccess ( 'industry_basics' )) {
			$result .= $this->getLink ( "Industry", "?id=5$sid", ($curr_id == 5) );
		}
		
		if (is_object($CU->getGroup ()) && $CU->getGroup ()->hasAccess ( 'corp_accounting' )) {
			$result .= $this->getLink ( "Corp-Accounting", "?id=7$sid", ($curr_id == 7) );
		}
		
		if (is_object($CU->getGroup ()) && $CU->getGroup ()->hasAccess ( 'pos_manager' )) {
			$result .= $this->getLink ( "POS Manager", "?id=8$sid", ($curr_id == 8) );
		}
		
		return $result . "\n</ul>";
	}
	
	/**
	 * 
	 * @param unknown $link
	 * @param unknown $url
	 * @param unknown $active
	 * @return string
	 */
	private function getLink($link, $url, $active) {
		if ($active) {
			return '<li class="emb_li active"><strong><a href="' . $url . '">' . $link . '</a></strong></li>';
		} else {
			return '<li class="emb_li"><a href="' . $url . '">' . $link . '</a></li>';
		}
	}
}

?>