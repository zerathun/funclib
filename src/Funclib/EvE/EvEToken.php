<?php
namespace Funclib\EvE;

class EvEToken
{
    // Declare the properties
    private $user_id;
    private $CharacterID;
    private $timestamp;
    private $expires_in;
    private $CharacterName;
    private $TokenType;
    private $CharacterOwnerHash;
    private $RefreshToken;
    private $AccessToken;
    private $scope;
    private $CorporationID;
    private $ServiceID;
    private $char_id;
    private $charName;
    private $img_url;
    private $corp_id;
    private $data;
    
    
    public function __construct()
    {
        
    }
    
    public function IsExpired() : bool
    {
        return (!empty($this->timestamp) && !empty($this->expires_in) && ($this->timestamp + $this->expires_in < time()));
    }
    
    public function IsNotExpired() : bool
    {
        return !$this->IsExpired();
    }
    
    
    
    // Define the getter methods for the properties
    public function getUser_id() {
        return $this->user_id;
    }
    
    public function getCharacterID() {
        return $this->CharacterID;
    }
    
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    public function getExpires_in() {
        return $this->expires_in;
    }
    
    public function getCharacterName() {
        return $this->CharacterName;
    }
    
    public function getTokenType() {
        return $this->TokenType;
    }
    
    public function getCharacterOwnerHash() {
        return $this->CharacterOwnerHash;
    }
    
    public function getRefreshToken() {
        return $this->RefreshToken;
    }
    
    public function getAccessToken() {
        return $this->AccessToken;
    }
    
    public function getScope() {
        return $this->scope;
    }
    
    public function getCorporationID() {
        return $this->CorporationID;
    }
    
    public function getServiceID() {
        return $this->ServiceID;
    }
    
    public function getChar_id() {
        return $this->char_id;
    }
    
    public function getCharName() {
        return $this->charName;
    }
    
    public function getImg_url() {
        return $this->img_url;
    }
    
    public function getCorp_id() {
        return $this->corp_id;
    }
    
    public function getData() {
        return $this->data;
    }
    
    // Define the setter methods for the properties
    public function setUser_id($user_id) {
        $this->user_id = $user_id;
    }
    
    public function setCharacterID($CharacterID) {
        $this->CharacterID = $CharacterID;
    }
    
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
    }
    
    public function setExpires_in($expires_in) {
        $this->expires_in = $expires_in;
    }
    
    public function setCharacterName($CharacterName) {
        $this->CharacterName = $CharacterName;
    }
    
    public function setTokenType($TokenType) {
        $this->TokenType = $TokenType;
    }
    
    public function setCharacterOwnerHash($CharacterOwnerHash) {
        $this->CharacterOwnerHash = $CharacterOwnerHash;
    }
    
    public function setRefreshToken($RefreshToken) {
        $this->RefreshToken = $RefreshToken;
    }
    
    public function setAccessToken($AccessToken) {
        $this->AccessToken = $AccessToken;
    }
    
    public function setScope($scope) {
        $this->scope = $scope;
    }
    
    public function setCorporationID($CorporationID) {
        $this->CorporationID = $CorporationID;
    }
    
    public function setServiceID($ServiceID) {
        $this->ServiceID = $ServiceID;
    }
    
    public function setChar_id($char_id) {
        $this->char_id = $char_id;
    }
    
    public function setCharName($charName) {
        $this->charName = $charName;
    }
    
    public function setImg_url($img_url) {
        $this->img_url = $img_url;
    }
    
    public function setCorp_id($corp_id) {
        $this->corp_id = $corp_id;
    }
    
    public function setData($data) {
        $this->data = $data;
    }
    
    public function setWithObject($obj) {
        foreach ($obj as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function IsCorporationToken() : bool
    {
        return ($this->getTokenType() == "Corporation");
    }
    
    
    
}


?>