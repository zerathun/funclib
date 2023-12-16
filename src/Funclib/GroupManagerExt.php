<?php
namespace Funclib;

class GroupManagerExt
{
    private $groups;
    
    private function __construct() {
        $this->groups = array();
    }
    
    private static $instance;
    
    public static function getInstance() {
        if(empty(FileLog::$instance)) {
            GroupManagerExt::$instance = new GroupManagerExt();
        }
        return GroupManagerExt::$instance;
    }
    
    public function ParseGroupResponse($response)
    {
        $result = array();
        
        if(is_object($response) && !empty($response->groups))
        {
            $arrKeys = get_object_vars($response->groups);
            foreach($arrKeys as $group_id => $groupObj)
            {
                $group = new GroupExt();
                $group->setFromObj($groupObj);
                $this->addGroup($group);
            }
        }
        return $result;
    }
    
    public function addGroup($group) {
        $this->groups[$group->getId()] = $group;
    }
    
    public function removeGroup($id) {
        unset($this->groups[$id]);
    }
    
    public function searchGroupById($id) {
        if(isset($this->groups[$id])) {
            return $this->groups[$id];
        } else {
            return null;
        }
    }
}
?>