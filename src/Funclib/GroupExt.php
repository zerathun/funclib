<?php
namespace Funclib;
/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2007 Sebastian Winterhalder <zeradun@embin.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */


class GroupExt
{
    public function __construct()
    {
        
    }
    
    public function setFromObj($groupObj) {
        if(!empty($groupObj->id)) {
            $this->setId($groupObj->id);
        }
        if(!empty($groupObj->owner_id)) {
            $this->setOwnerId($groupObj->owner_id);
        }
        if(!empty($groupObj->group_name)) {
            $this->setGroupName($groupObj->group_name);
        }
        if(!empty($groupObj->group_description)) {
            $this->setGroupDescription($groupObj->group_description);
        }
        if(!empty($groupObj->parent)) {
            $this->setParent($groupObj->parent);
        }
        if(!empty($groupObj->public)) {
            $this->setPublic($groupObj->public);
        }
        if(!empty($groupObj->moderator)) {
            $this->setModerator($groupObj->moderator);
        }
        if(!empty($groupObj->assoc_admin)) {
            $this->setAssocAdmin($groupObj->assoc_admin);
        }
        if(!empty($groupObj->blocked)) {
            $this->setBlocked($groupObj->blocked);
        }
        if(!empty($groupObj->request_req)) {
            $this->setRequestReq($groupObj->request_req);
        }
        if(!empty($groupObj->group_locked)) {
            $this->setGroupLocked($groupObj->group_locked);
        }
        if(!empty($groupObj->automated_group)) {
            $this->setGroupLocked($groupObj->automated_group);
        }
    }
    
    private $id;
    private $owner_id;
    private $group_name;
    private $group_description;
    private $parent;
    private $public;
    private $moderator;
    private $assoc_admin;
    private $blocked;
    private $request_req;
    private $group_locked;
    private $automated_group;
    
    public function getId() {
        return $this->id;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getOwnerId() {
        return $this->owner_id;
    }
    
    public function setOwnerId($owner_id) {
        $this->owner_id = $owner_id;
    }
    
    public function getGroupName() {
        return $this->group_name;
    }
    
    public function setGroupName($group_name) {
        $this->group_name = $group_name;
    }
    
    public function getGroupDescription() {
        return $this->group_description;
    }
    
    public function setGroupDescription($group_description) {
        $this->group_description = $group_description;
    }
    
    public function getParent() {
        return $this->parent;
    }
    
    public function setParent($parent) {
        $this->parent = $parent;
    }
    
    public function getPublic() {
        return $this->public;
    }
    
    public function setPublic($public) {
        $this->public = $public;
    }
    
    public function getModerator() {
        return $this->moderator;
    }
    
    public function setModerator($moderator) {
        $this->moderator = $moderator;
    }
    
    public function getAssocAdmin() {
        return $this->assoc_admin;
    }
    
    public function setAssocAdmin($assoc_admin) {
        $this->assoc_admin = $assoc_admin;
    }
    
    public function getBlocked() {
        return $this->blocked;
    }
    
    public function setBlocked($blocked) {
        $this->blocked = $blocked;
    }
    
    public function getRequestReq() {
        return $this->request_req;
    }
    
    public function setRequestReq($request_req) {
        $this->request_req = $request_req;
    }
    
    public function getGroupLocked() {
        return $this->group_locked;
    }
    
    public function setGroupLocked($group_locked) {
        $this->group_locked = $group_locked;
    }
    
    public function getAutomatedGroup() {
        return $this->automated_group;
    }
    
    public function setAutomatedGroup($automated_group) {
        $this->automated_group = $automated_group;
    }
}
?>