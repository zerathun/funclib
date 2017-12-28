<?php 
namespace zeradun\api_manager\includes\Ember\ClassLibrary\modules;

/**
 * Module Interface for all Modules
 * @author Sebastian
 *
 */
class module_handler {
	
	private static $instance;
	private $module_cores;
	private $module_list = array(0 => 'pos_manager', 1 => 'finances_manager', 2 => 'cap_production', 3 => 'assets_list', 4 => 'marketprices', 5=> 'marketviewer', 6=>'itemviewer', 7=>'marketstockpile', 8=>'parts_production');
	private $deny_relist_url = array('deletejob', 'delTypeID', 'delPartID', 'okPartID', 'okTypeID');
	/**
	 * private constructor
	 */
	private function __construct() {
		
	}
	
	/**
	 * 
	 */
	public function setBaseExe($baseExe) {
		$this->baseExe = $baseExe;
	}
	
	private $baseExe;
	
	public function getBase() {
		return $this->baseExe;
	}
	
	/**
	 * Static getter Class for Singleton
	 */
	public static function getInstance() {
		if(empty(module_handler::$instance)) module_handler::$instance = new module_handler();
		return module_handler::$instance;
	}

	/**
	 * the order of ID's can be changed, but the Index has to stay the same, or it will mess up the database
	 * @return array
	 */
	public function getModuleList() {
		return $this->module_list;
	}
	
	/**
	 * get an instance of a said module
	 * @param Integer $module_id
	 */
	public function getModuleCore($module_id) {
		if(empty($this->module_cores[$module_id]))
		{
			$class = '\zeradun\api_manager\includes\Ember\ClassLibrary\modules\mod_' . $this->module_list[$module_id];
			$this->module_cores[$module_id] = new $class();
		}
		return $this->module_cores[$module_id];
	}
	
	/**
	 * check if the user(group) has access to the specified module / leave empty for any module
	 * @param number $module_id
	 */
	public function hasAccess($user_id, $module_id = -1) {
		$acc_groups = $this->getAccessGroups($user_id);
		if($module_id == -1) {
			return (!empty($acc_groups['group_access']));
		} else {
			return(!empty($acc_groups['group_access'][$module_id]));
		}
	}
	
	private function getAccessGroups($user_id) {
		global $db, $cache;
		if(!IN_PHPBB) {
			throw new \Exception("The Method 'hasAccess' requires to be Run in PHPBB3");
		}
	
		if(empty($this->groups_access)) {
			$this->groups_access = array('group_table' => array(), 'group_access' => array());
		
			$sql = "SELECT * FROM ".USER_GROUP_TABLE." WHERE user_id = ".$user_id." AND user_pending = 0";
			$res = $db->sql_query($sql);
			$sql_add = "";
				
			while($row = $db->sql_fetchrow($res)) {
				$this->groups_access['group_table'][$row['group_id']] = $row;
				if(strlen($sql_add) > 0) {
					$sql_add .= " OR";
				}
				$sql_add .= " group_id = ".$row['group_id'];
			}
				
			if(strlen($sql_add) > 0) {
				$sql = "SELECT * FROM emb_corp_accesslist WHERE $sql_add";
				$res = $db->sql_query($sql);
				while($row = $db->sql_fetchrow($res)) {
					$this->groups_access['group_access'][$row['module_id']][$row['group_id']] = $row;
				}
			}
		}
		return $this->groups_access;
	}
	
	private $groups_access;
	
	public function hasWriteAccess($user_id, $module_id) {
		$acc_groups = $this->getAccessGroups($user_id);
		if($module_id < 0) {
			return (!empty($acc_groups['group_access']));
		} else {
			foreach($acc_groups['group_access'][$module_id] as $group_id => $acc_arr) {
				if($acc_arr['access'] >= 2)
					return true;
			}
			return false;
		}
	}
	
	/**
	 * 
	 * @param Int $user_id
	 * @return Array
	 */
	public function getModuleListWA($user_id) {
		$list = $this->getModuleList();
		$result = array();
		foreach($list as $modId => $module) {
			if($this->hasAccess($user_id, $modId)) {
				$result[$modId] = $module;
			}
		}
		return $result;
	}
	
	public function getModuleURL($appendArr, $modId = 0) {
		global $request;
		$request->enable_super_globals();
		$urlstring = split("&", $_SERVER['QUERY_STRING']);
		$keys = array();
		
		foreach($urlstring as $key => $urlel) {
			$elements = split("=", $urlel);
			if(!in_array($elements[0],$this->deny_relist_url)) {
				$keys[$elements[0]] = $elements[1];
			}
		}

		if($modId == 0) {
			$modId = request_var('mod', 4);
		}
		
		$add = "";
		if(is_array($appendArr)) {
			foreach($appendArr as $key => $v) {
				$add .= "&$key=$v";
			}
		}
		
		foreach($keys as $k1 => $k2) {
			$flag=true;
			foreach($appendArr as $appK => $appId) {
				if($appK == $k1) {
					$flag = false;
				}
			}
			
			if($k1 != "mod" && $flag) {
				$add .= "&$k1=$k2";
			}
		}
		
		$res = "ember?mod=".$modId.$add;
		$request->disable_super_globals();
		return $res;
	}
}

?>