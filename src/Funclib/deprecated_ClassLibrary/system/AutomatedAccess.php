<?php
namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIKey;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;
use zeradun\api_manager\includes\Ember\af;



class AutomatedAccess {
	
	private $parentKey;
	private $userKeys = array();
	private $characterRoles;
	
	/**
	 * Definition of Group Access for the forum
	 *  [corporationId][roles/titles][ID]=>Group ID
	 *  
	 *  roles
	 *  	9007199254740992 => roleStarbaseConfig
	 *  	288230376151711744 => roleStarbaseCaretaker
	 *  	144115188075855872 => InfrastructureTacticalOfficer
	 *  
	 *  titles
	 *  	8 => Member
	 *  	16384 => POS Manager
	 * @var unknown
	 */
	private $roleAccess = array('98285854' => 
			array('roles' => 
					array('1' => array(8,9,10,11,12), '2' => array(), '128' => array(8,10),
							'9007199254740992' => array(8,11),
							'288230376151711744' => array(8,11),
							'144115188075855872' => array(8,11),
					),
				  'titles' =>
					array ( '1' => array(), '2' => array(), '4' => array(),
							'8' => array(), '16384' => array())),
							'16' => array(), '32' => array(), '64' => array(), '128' => array(), '512' => array(), '1024' => array(),
							'2048' => array(), '4096' => array(), '8192' => array(), '16384' => array(), '32768' => array(),
			);
	
	/**
	 * all roles listed
	 * @var unknown
	 */
	private $roleList = array(
		1 => 'roleDirector',
		128 => 'rolePersonnelManager',
		256	=> 'roleAccountant',
		512	=> 'roleSecurityOfficer',
		2048 => 'roleStationManager',
		1024	=> 'roleFactoryManager',
		4096 => 'roleAuditor',
		17179869184 => 'roleDiplomat',
		2199023255552 => 'roleEquipmentConfig',
		4503599627370496 => 'roleJuniorAccountant',
		9007199254740992 => 'roleStarbaseConfig',
		18014398509481984 => 'roleTrader',
		36028797018963968 => 'roleChatManager',
		72057594037927936 => 'roleContractManager',
		144115188075855872 => 'roleInfrastructureTacticalOfficer',
		288230376151711744 => 'roleStarbaseCaretaker',
		576460752303423488 => 'roleFittingManager',
	);
	
	public function __construct() {
		Database::getInstance();
		$loadCorpList = APIManager::getInstance()->getAllCorpAPIs();

		foreach($loadCorpList as $corp) {
			$chars = $corp->getCharacters();
			foreach($chars as $corpObj) {
				$this->corporationList[$corpObj->getId()] = $corp;
			}
		}
	}
	
	public function getRoleList() {
		return $this->roleList;
	}
	
	/**
	 * returns array of group ids for the forum user
	 * @param Int $forumUserId
	 * @throws Exception
	 * @return Array
	 */
	public function calculateAccess($forumUserId) {
		$this->getSettingsTable();
		$userKeys = array();
		$sql = "
				SELECT emb_api.apikeyID, vCode, expiration, assetsCache, refresh, corp_id, api_wrong, last_time_checked FROM emb_api,(	SELECT apikeyID /*emb_api.apikeyID, emb_api.vCode, emb_api.expiration, emb_api.assetsCache, emb_api.corp_id*/
				FROM emb_api_user, (SELECT emb_forum_user.ember_user_id FROM emb_forum_user 
					LEFT JOIN emb_user ON emb_forum_user.ember_user_id = emb_user.id
					WHERE emb_forum_user.forum_user_id = $forumUserId) as embuser_id
				WHERE emb_api_user.embuserID = embuser_id.ember_user_id) AS temp1
       			 WHERE emb_api.apikeyID = temp1.apikeyID
				";
		
		$res = Database::getInstance()->sql_query($sql);
		
		while($row = Database::getInstance()->sql_fetch_array($res)) {
			if(!empty($row['apikeyID']) && !empty($row['vCode'])) {
				$apk = new APIKey($row['apikeyID'], $row['vCode']);
				// Set key status and last time checked for invalid keys of user and limit their checkings
				$apk->setKeyStatus($row['key_wrong']);
				$apk->setLastTimeChecked($row['last_time_checked']);
				$phealResult = $apk->loadPheal();
				if($phealResult !== false) {
					$userKeys[] = $apk;
				}
			}
		}
		
		$group_ids = array();

		foreach($userKeys as $userApi) {
			if($userApi->getKeyMode() != 'Corporation') {
				$chars = $userApi->getCharacters();
				
				foreach($chars as $char) {
					if(!($char instanceof EvECorporation)) {
						$conts = $char->getContents();
						if(!empty($this->corporationList[$conts['corporationID']])) {
							if(empty($this->memberSecurity[$conts['corporationID']])) {
								$corpChar = $this->corporationList[$conts['corporationID']]->getCharacters();
								
								$mmSec_PhealObj = $this->corporationList[$conts['corporationID']]->executePheal($corpChar[0], 'MemberSecurity');

								$mmSec = $mmSec_PhealObj->toArray();						
								if(!empty($mmSec)) {
									foreach($mmSec['result']['members'] as $memberArr) {
										$this->memberSecurity[$conts['corporationID']][$memberArr['characterID']] = $memberArr;
									}
								}
							}
							
							$charRoles = $this->memberSecurity[$conts['corporationID']][$char->getCharacterId()];
							foreach($charRoles as $roleType => $roles) {
								if($roleType == "roles" || $roleType == "grantableRoles" || $roleType == "rolesAtHQ" || $roleType == "grantableRolesAtHQ" || $roleType == "rolesAtBase" || $roleType == "grantableROlesAtBase" || $roleType == "rolesAtOther" || $roleType == "grantableRolesAtOther")
								{ $x = 1; }
								elseif($roleType == "titles") {
									$x = 0; }
								else {
									// Meupmeup
									$x = -1;
								}
								if($x >= 0) {
									foreach($roles as $roleID => $role) {
										foreach($this->settingsTable[$conts['corporationID']] as $groupID => $groupToCheck) {
											foreach($groupToCheck[$x] as $roleKey => $d) {
												if($role['titleID'] && $x == 0) {
													if($role['titleID'] == $roleKey) {
														if($d['access'] == 1) {
															$group_ids[] = $groupID;
														}
													}
												} elseif($role['roleID'] && $x == 1)
												{
													if($role['roleID'] == $roleKey) {
														if($d['access'] == 1) {
															$group_ids[] = $groupID;
														}
													}
												} else {}
											}
										}
									}
								}
							}
						}
						
						if(!empty($this->characterRoles[$conts['corporationID']][$char->getCharacterId()])) {
							foreach($this->characterRoles[$conts['corporationID']][$char->getCharacterId()]['roles'] as $role_r){
								$grp = $this->roleAccess[$conts['corporationID']]['roles'][$role_r['roleID']];
								if(!empty($grp)) {
									$group_ids[] = $grp;
								}
							}
							foreach($this->characterRoles[$conts['corporationID']][$char->getCharacterId()]['titles'] as $role_r){
								$grp = $this->roleAccess[$conts['corporationID']]['titles'][$role_r['titleID']];
								if(!empty($grp)) {
									$group_ids[] = $grp;
								}
							}
						}
					}
				}
			}
		}
		
		$groups_def = array();
		foreach($group_ids as $group_id) {
			$groups_def[$group_id] = $group_id;	
		}

		return $groups_def;
	}
	
	
	private $corporationList;
	private $memberSecurity;
	
	/**
	 * 
	 */
	public function calculateAllAccess() {
		global $db, $auth;

		$res = fopen("/var/www/log/cron.txt", "a");
		fwrite($res, date("d.m.y - h:i", time()).": func calculateAllAccess()\n");
		// Create the SQL statement
		$sql = 'SELECT user_id
        FROM ' . USERS_TABLE . '
        WHERE user_type = 0';
		
		// Run the query
		$result = $db->sql_query($sql);
		
		// $row should hold the data you selected
		while($row = $db->sql_fetchrow($result)) {
			$userAccess[$row['user_id']] = $this->calculateAccess($row['user_id']);
		}
		
		$del_sql = 'DELETE FROM ' . USER_GROUP_TABLE . ' WHERE';
		$del_where = "";
		$insert_sql = 'INSERT INTO ' . USER_GROUP_TABLE . ' (group_id, user_id, user_pending, auto_added) VALUES';
		$add_insert = "";
		foreach($userAccess as $userId => $userGroupAccess) {
			if(strlen($del_where) > 0) {
				$del_where .= " OR";
			}
			$del_where .= ' (auto_added = 1 AND user_id = \''.$userId.'\')';

			foreach($userGroupAccess as $groupAcc) {
				if($add_insert != "")
					$add_insert .= ", ";
				$add_insert .= " ('".$groupAcc."', '".$userId."', 0, 1)";
			}
		}
		

		FileLog::getInstance()->appendLog("Update all Forum-Users group access depending on their API-Checks");
		
		if(strlen($del_where) > 0) {
			$sql = $del_sql.$del_where;
			$db->sql_query($del_sql.$del_where);
		}
		
		if(strlen($add_insert) > 0) {
			$insert_sql .= $add_insert;
			$db->sql_query($insert_sql);
		}
		
		//print_r($loadCorpList);
		
		//$res = fopen("/var/www/log/cron.txt", "a");
		//fwrite($res, date("d.m.y - h:i", time()).": $sql\n");
		
		/*foreach($loadCorpList as $corp) {
			$chars = $corp->getCharacters();
			foreach($chars as $corpObj) {
				fwrite($res, date("d.m.y - h:i", time()).": Update Access for Members of CorpID".$corpObj->getId()."\n");
			}
		}*/
		
		$auth->acl_clear_prefetch();
	}
	
	/**
	 * SQL Query / Function to set the Groups
	 * @param unknown $groups_array
	 */
	public function setGroups($groups_array, $forum_user_id) {
		
		global $db, $auth;
		
		$del_sql = 'DELETE FROM ' . USER_GROUP_TABLE . ' WHERE (auto_added = 1 AND user_id = \''.$forum_user_id.'\')';
		$insert_sql = 'INSERT INTO ' . USER_GROUP_TABLE . ' (group_id, user_id, user_pending, auto_added) VALUES';
		$add_insert = "";

		foreach($groups_array as $groupAcc) {
			if($add_insert != "")
				$add_insert .= ", ";
			$add_insert .= " ('".$groupAcc."', '".$forum_user_id."', 0, 1)";
		}
		
		$insert_sql .= $add_insert;
		
		FileLog::getInstance()->appendLog("Update all Forum-Users group access depending on their API-Checks");
		
		$db->sql_query($del_sql);
		if(strlen($add_insert) > 0) {
			$db->sql_query($insert_sql);
		}
		$auth->acl_clear_prefetch();
		return true;
	}
	
	private $settingsTable;
	
	public function getSettingsTable($reload=false) {
		global $db;
		
		if(!empty($this->settingsTable) && !$reload) return $this->settingsTable;
		$sql = "SELECT * FROM emb_forum_grouptable";
		$res = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($res)) {
			$this->settingsTable[$row['corpId']][$row['groupId']][$row['role_or_title']][$row['roleId']]['access'] = $row['access'];
			$this->settingsTable[$row['corpId']][$row['groupId']][$row['role_or_title']][$row['roleId']]['cron'] = $row['cron'];
			$this->settingsTable[$row['corpId']][$row['groupId']][$row['role_or_title']][$row['roleId']]['data'] = $row;
		}
	}
	
	public function saveSettingsTable($fieldset) {
		global $db;
		$queries['update_y'] = "UPDATE emb_forum_grouptable SET access = 1 WHERE FALSE";
		$queries['update_n'] = "UPDATE emb_forum_grouptable SET access = 0 WHERE FALSE";
		$queries['cron_y'] = "UPDATE emb_forum_grouptable SET cron = 1 WHERE FALSE";
		$queries['cron_n'] = "UPDATE emb_forum_grouptable SET cron = 0 WHERE FALSE";
		$queries['insert_y'] = "INSERT INTO emb_forum_grouptable (corpId,groupId,roleId,access,cron,role_or_title) VALUES ";
		
		$update_where_true = $update_where_false = $values = "";
		if(empty($fieldset[0]))
			$fieldset[0] = array();
		
		$titlesFieldset = $fieldset[0];

		foreach($titlesFieldset as $corp_id => $titles) {
			foreach($titles as $titleId => $grps) {
				foreach($grps as $grpId => $grp) {
					if(isset($this->settingsTable[$corp_id][$grpId][0][$titleId])) {
						if($this->settingsTable[$corp_id][$grpId][0][$titleId]['access'] != $grp['access'] || $this->settingsTable[$corp_id][$grpId][$titleId][0]['cron'] != $grp['cron']) {
							$this->settingsTable[$corp_id][$grpId][0][$titleId] = $grp;
							if($grp['access']) {
								$update_where_true .= " || (corpId = '".$corp_id."' AND groupId = '".$grpId."' AND roleId = '".$titleId."' AND role_or_title = 0)";
							}
							else {
								$update_where_false .= " || (corpId = '".$corp_id."' AND groupId = '".$grpId."' AND roleId = '".$titleId."' AND role_or_title = 0)";
							}
							if($grp['cron']) {
								$cron_where_true .= " || (corpId = '".$corp_id."' AND groupId = '".$grpId."' AND roleId = '".$titleId."' AND role_or_title = 0)";
							}
							else {
								$cron_where_false .= " || (corpId = '".$corp_id."' AND groupId = '".$grpId."' AND roleId = '".$titleId."' AND role_or_title = 0)";
							}
						}
					} else {
						$this->settingsTable[$corp_id][$grpId][0][$titleId] = $grp;
						if(!empty($values))
							$values .= ", ";
						$values .= "('".$corp_id."','".$grpId."','".$titleId."','".$grp['access']."','".$grp['cron']."',0)";
					}
				}
			}
		}
		
		if(!empty($values)) {
			$query1=$queries['insert_y'].$values.";";
			$db->sql_query($query1);
		}
		if(!empty($update_where_true)) {
			$query = $queries['update_y'].$update_where_true;
			$db->sql_query($query);
		}
		if(!empty($update_where_false)) {
			$query = $queries['update_n'].$update_where_false;
			$db->sql_query($query);
		}
		if(!empty($cron_where_true)) {
			$query = $queries['cron_y'].$cron_where_true;
			$db->sql_query($query);
		}
		
		if(!empty($cron_where_false)) {
			$query = $queries['cron_n'].$cron_where_false;
			$db->sql_query($query);
		}
		
		$queries['upd_y'] = "UPDATE emb_forum_grouptable SET access = 1 WHERE FALSE";
		$queries['upd_n'] = "UPDATE emb_forum_grouptable SET access = 0 WHERE FALSE";

		$upd_false = $upd_true = $values = "";
		if(empty($fieldset[1]))
			$fieldset[1] = array();
		$rolesFieldset = $fieldset[1];
		
		foreach($rolesFieldset as $corp_id => $roleList) {
			foreach($roleList as $roleId => $role) {
				foreach($role as $grpId => $grp) {
					if(isset($this->settingsTable[$corp_id][$grpId][1][$roleId])) {
						$this->settingsTable[$corp_id][$grpId][1][$roleId] = $grp;
						if($grp['access']) {
							$upd_true .= " || (corpId = '".$corp_id."' AND groupId = '".$grpId."' AND roleId = '".$roleId."' AND role_or_title = 1)";
						}
						else {
							$upd_false .= " || (corpId = '".$corp_id."' AND groupId = '".$grpId."' AND roleId = '".$roleId."' AND role_or_title = 1)";
						}
					}
					else {
						$this->settingsTable[$corp_id][$grpId][1][$roleId] = $grp;
						if(!empty($values))
							$values .= ", ";
						$values .= "('".$corp_id."','".$grpId."','".$roleId."','".$grp['access']."','".$grp['cron']."',1)";
					}
				}
			}
		}
		
		if(!empty($values)) {
			$query1=$queries['insert_y'].$values.";";
			$db->sql_query($query1);
		}
		if(!empty($upd_true)) {
			$query = $queries['upd_y'].$upd_true;
			$db->sql_query($query);
		}
		if(!empty($upd_false)) {
			$query = $queries['upd_n'].$upd_false;
			$db->sql_query($query);
		}
	}
	
	public function hasAccess($corpId, $groupId, $roleId,$role=0) {
		return (bool) $this->settingsTable[$corpId][$groupId][$role][$roleId]['access'];
	}
	
	public function isCron($corpId, $groupId, $roleId,$role=0) {
		return (bool) $this->settingsTable[$corpId][$groupId][$role][$roleId]['cron'];
	}
	
	public function loadAPIs($APIKeyArr) {
		
	}
	
}