<?php

use Funclib\Ifaces\Saveable;

class EvECharacter extends  Saveable
{

    protected $CharacterID = 0;
    protected $AllianceID = 0;
    protected $name = "";
    
    protected $assignAttrFunc = array(
        'CharacterID' => 'setCharacterID',
        'name' => 'setName',
        
        'password' => 'setPassword',
        'email' => 'setEmail',
        'mail_activated' => 'setMailactivated',
        'reg_hash' => 'setRegHash',
        'reset_pw_hash' => 'setResetPwHash',
        'rest_pw_timestamp' => 'setRestPwTimestamp',
        'reset_mails_sent' => 'setResetMailsSent',
        'userdata' => 'setUserdata',
        'id' => 'setId',
    );
    
    protected function getAssignedAttributes()
    {}


    protected function getMethods()
    {}
    
    public function SetCharacterID(int $CharacterId)
    {
        $this->CharacterID = $CharacterId;
    }
    
    public function GetCharacterID() : int
    {
        return $this->CharacterID;
    }
    
    public function SetName(string $name)
    {
        $this->name = $name;
    }
    
    public function GetName() : string
    {
        return $this->name;
    }
    
    public function SetAllianceID(int $allianceId)
    {
        $this->AllianceID = $allianceId;
    }
    
    public function GetAllianceID() : int
    {
        return $this->AllianceID;
    }
    
    public function GetEvECharacterImgUrl($size)
    {
        
    }
    
    

}


?>