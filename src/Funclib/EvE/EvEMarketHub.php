<?php
namespace Funclib\EvE;

use Funclib\Ifaces\Saveable;
use Funclib\Database;
use \PDO;
use \Exception as Exception;


class EvEMarketHub extends Saveable
{
    
    private $name;
    private $id;
    private $system_id;
    private $station_id = 0;
    private $region_id = 0;
    private $constellation_id = 0;
    private $owner_id = 0;
    private $timestamp = 0;
    private $saveableSettings;
    private $structure_hub = 1; // The Hub is a Station (private) or System (public)
    private $active_market = 0; // Initialize variable for Database sanity
    
    private $loaded_from_db = false;
    
    private $MarketHubItems = array();
    
    private $itemChange = array();
    
    public function __construct($marketHubId = 0)
    {
        $this->setTable('embindustry_markethub'); // Set the MySQL Table for the Groups
        parent::__construct();
        $this->saveableSettings = json_encode(array());
        if($marketHubId > 0) {
            $this->load(array('id' => intval($marketHubId)));
            $this->id = $marketHubId;
        }
    }
    
    public function AddMarketItem(EvEMarketItem $item)
    {
        $this->MarketHubItems[$item->getTypeId()] = $item;
    }
    
    public function GetMarketItems()
    {
        return $this->MarketHubItems;
    }
    
    public function GetMarketItem(int $type_id)
    {
        if($type_id < 0)
            throw new Exception('type_id must be greater than 0');
            
            if($type_id == 0)
                return null;
                
                if(empty($this->MarketHubItems) || empty($this->MarketHubItems[$type_id]))
                {
                    $sql = "SELECT * FROM `embindustry_marketitem` WHERE type_id = :TypeID AND hub_id = :HubId";
                    $dbh = Database::getInstance()->getPDOConnection('ember-industry');
                    $q=$dbh->prepare($sql);
                    $q->bindValue(':TypeID', $type_id, PDO::PARAM_INT);
                    $q->bindValue(':HubId', $this->getId(), PDO::PARAM_INT);
                    $q->execute();
                    
                    $type_id = 0;
                    $counter = 0;
                    $marketItem = null;
                    while($r=$q->fetch(PDO::FETCH_OBJ))
                    {
                        if(!empty($r->type_id))
                        {
                            $marketItem = new EvEMarketItem();
                            $marketItem->setWithObject($r);
                            $counter++;
                        }
                    }
                    return $marketItem;
                }
                
                
                return $this->MarketHubItems[$type_id];
    }
    
    public function GetTwigArray() : array
    {
        if(empty($this->MarketHubItems))
            return array();
            
            $result = array();
            
            foreach($this->MarketHubItems as $hubItem)
            {
                
                $result[] = $hubItem->getTwigArray();
            }
            return $result;
    }
    
    // Define the Table Names
    protected $assignAttrFunc = array(
        'name' => 'setName',
        'id' => 'setId',
        'region_id' => 'setRegionId',
        'constellation_id' => 'setConstellationId',
        'system_id' => 'setSystemId',
        'owner_id' => 'setOwnerId',
        'timestamp' => 'setTimestamp',
        'saveableSettings' => 'setSetting',
        'active_market' => 'setActiveMarket',
        'structure_hub' => 'setStructureHub',
        'esi_access_allowed' => 'setEsiAccessAllowed',
    );
    
    public function setName($name) {
        if(strlen($name) > 0 && strlen($this->name > 0) && $this->name != $name)
            $this->itemChange[] = "The name changed from ".$this->name." to ".$name;
            $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setActiveMarket($active)
    {
        
        $active = ((bool)($active)) ? 1:0;
        
        $this->active_market = $active;
    }
    
    public function getActiveMarket()
    {
        return $this->active_market;
    }
    
    public function setEsiAccessAllowed($esi_allowed)
    {
        $esi_allowed = ((bool)($esi_allowed)) ? 1:0;
        
        $this->esi_access_allowed = $esi_allowed;
    }
    
    public function getEsiAccessAllowed()
    {
        return $this->esi_access_allowed;
    }
    
    public function setStructureHub($active)
    {
        if(is_bool($active))
            $active = intval($active);
            $this->structure_hub = $active;
    }
    
    public function getStructureHub()
    {
        return $this->structure_hub;
    }
    
    public function setId($id)
    {
        $this->id = intval($id);
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setSystemId($system_id)
    {
        $this->system_id = intval($system_id);
    }
    
    public function getSystemId()
    {
        return $this->system_id;
    }
    
    public function setRegionId($region_id)
    {
        $this->region_id = intval($region_id);
    }
    
    public function getRegionId()
    {
        return $this->region_id;
    }
    
    public function setConstellationId($constellation_id)
    {
        if($constellation_id > 0 && $this->constellation_id > 0)
            $this->itemChange[] = "Constellation changed from ".$this->constellation_id." to ".$constellation_id;
            $this->constellation_id = intval($constellation_id);
    }
    
    public function getConstellationId()
    {
        return $this->constellation_id;
    }
    
    public function setOwnerId($owner_id)
    {
        if($owner_id > 0 && $this->owner_id > 0)
            $this->itemChange[] = "Owner ID Changed from ".$this->owner_id." to ".$owner_id;
            $this->owner_id = intval($owner_id);
    }
    
    public function getOwnerId()
    {
        return $this->owner_id;
    }
    
    public function getGroupsWithAccess() : array
    {
        $result = array();
        
        $settings = $this->getSetting();
        
        if(!empty($settings['groups']) && is_array($settings['groups']))
        {
            $result = $settings['groups'];
        }
        
        return $result;
    }
    
    public function getModeratorGroupsWithAccess() : array
    {
        $result = array();
        
        $settings = $this->getSetting();
        
        
        if(!empty($settings['moderator']) && is_array($settings['moderator']))
        {
            $result = $settings['moderator'];
        }
        
        return $result;
    }
    
    public function setTimestamp($timestamp)
    {
        $this->timestamp = intval($timestamp);
    }
    
    public function getTimestamp()
    {
        return $this->timestamp;
    }
    
    public function setHubType(int $hub_type)
    {
        
    }
    
    public function setSetting(string $settings)
    {
        $this->saveableSettings = $settings;
    }
    
    public function getSetting() : array|object
    {
        $result = json_decode($this->saveableSettings, true);
        
        if(is_array($result))
            return $result;
            else return array();
    }
    
    public function setSettingsRaw(array $settings)
    {
        $this->saveableSettings = json_encode($settings);
    }
    
    
    
    
    /**
     *
     * Implementation of Saveable
     *
     *
     */
    
    
    
    /**
     * Get the Class Methods
     *
     * @return Array
     */
    protected function getMethods() {
        return get_class_methods($this);
    }
    
    
    /**
     * Get the array whcih defines which Database-Fields are assgined to said method/object variable
     * @return Array
     */
    protected function getAssignedAttributes() {
        return $this->assignAttrFunc;
    }
    
    /**
     * Save the Object to the database
     *
     * @param boolean $check_safe_sql
     */
    public function save($check_safe_sql=true) {
        
        if(count($this->itemChange) > 0)
        {
            array_unshift($this->itemChange, "Hub Changed: ".date("Y/m/d H:i:s", time())." by {{unknown_user_id}}");
            $saveSettingJson = $this->getSettings();
            if(empty($saveSettingJson['change_history']))
                $saveSettingJson['change_history'] = array();
                $saveSettingJson['change_history'] = array_merge($saveSettingJson['change_history'],$this->itemChange);
                $this->setSettingsArray($saveSettingJson);
        }
        
        $vars = get_object_vars (  $this );
        $savearr = array();
        
        foreach($vars as $key => $value) {
            if(!array_key_exists(strtolower($key), $savearr)) {
                $savearr[$key] = $value;
            }
        }
        
        $this->setToSave($savearr);
        
        parent::save($check_safe_sql);
    }
    
    /**
     * Load the Object from the database with given identifiers e.g. ID
     *
     * @param array $identifier
     */
    public function load(array $identifier) {
        try {
            if(parent::load($identifier))
            {
                // The Item could be loaded from the Database
                $this->loaded_from_db = true;
            } else
            {
                // The Item could NOT be loaded from the Database and has to be saved
                $this->loaded_from_db = false;
            }
            
        } catch (Exception $e)
        {
            throw $e;
        }
        
        $this->loadNonGenericInformation();
    }
    
    public function IsLoadedFromDatabase() : bool
    {
        return $this->loaded_from_db;
    }
    
    
    public function loadNonGenericInformation() {
        //TODO Load non generic information to the MarketHub Object
    }
    
    /**
     * Permanently delete this object from the Database
     */
    public function Delete() {
        parent::Delete();
    }
    
    public function getLinkUrlArray() : array
    {
        return array('reg' => $this->getRegionId(), 'const' => $this->getConstellationId(), 'sys' => $this->getSystemId());
    }
    
}


?>