<?php
namespace Funclib\EvE;

use Funclib\Iface\Saveable;
use Funclib\LinkMng;

class EvEMarketItem extends Saveable
{
    
    private $name;
    private $type_id;
    private $hub_id;
    private $group_id;
    private $market_group_id = 0;
    private $category_id;
    private $amount = 0;
    private $amount_buy_order = 0;
    private $timestamp = 0;
    private $settings;
    private $last_change = 0;
    private $image_url = "";
    private $active_item = 0; // Initialize variable for Database sanity
    private $price = 0;
    private $total_volume = 0;
    private $buy_price_avg = 0;
    private $total_buy_volume = 0;
    
    
    private $loaded_from_db = false;
    private array $decoded_img_url = array();
    
    private $itemChange = array();
    
    public function __construct($type_id = 0, $hub_id = 0)
    {
        $this->setTable('embindustry_marketitem'); // Set the MySQL Table for the Groups
        parent::__construct();
        $this->settings = json_encode(array());
        $this->last_change = json_encode(array());
        if($type_id > 0) {
            $this->load(array('type_id' => intval($type_id), 'hub_id' => $hub_id));
            
            $this->type_id = $type_id;
            $this->hub_id = $hub_id;
        }
    }
    
    public function setWithObject($obj) {
        foreach ($obj as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function getTwigArray() : array
    {
        return array(
            'name' => $this->getName(),
            'type_id' => $this->getTypeId(),
            
            'hub_id' => $this->getHubId(),
            'group_id' => $this->getGroupId(),
            'category_id' => $this->getCategoryId(),
            'market_group_id' => $this->getMarketGroupId(),
            'timestamp' => $this->getTimestamp(),
            'settings' => $this->getSettings(),
            'last_change' => $this->getLastChange(),
            'active_item' => $this->getActiveItem(),
            'image_url' => $this->getImageUrlSize(16),
            'amount' => $this->getAmount(),
            'amount_buy_order' => $this->getAmountBuyOrder(),
            'price' => number_format($this->getPrice(), 2, ".", "´"),
            'total_volume' => $this->getTotalVolume(),
            'buy_total_volume' => $this->getTotalBuyVolume(),
            'buy_price_avg' => number_format($this->getBuyPrice(), 2, ".", "´"),
            'delta' => $this->getSellDelta()."|".$this->getBuyDelta(),
        );
    }
    
    // Define the Table Names
    protected $assignAttrFunc = array(
        'name' => 'setName',
        'type_id' => 'setTypeId',
        'hub_id' => 'setHubId',
        'group_id' => 'setGroupId',
        'category_id' => 'setCategoryId',
        'market_group_id' => 'setMarketGroupId',
        'timestamp' => 'setTimestamp',
        'settings' => 'setSettings',
        'last_change' => 'setLastChange',
        'active_item' => 'setActiveItem',
        'amount' => 'setAmount',
        'amount_buy_order' => 'setAmountBuyOrder',
        'image_url' => 'setImageUrl',
        'price' => 'setPrice',
        'total_volume' => 'setTotalVolume',
        'buy_price_avg' => 'setBuyPrice',
        'total_buy_volume' => 'setTotalBuyVolume',
    );
    
    public function setPrice(float $price)
    {
        $this->price = $price;
    }
    
    public function getPrice()
    {
        return $this->price;
    }
    
    public function setBuyPrice(float $price)
    {
        $this->buy_price_avg = $price;
    }
    
    public function getBuyPrice() : float
    {
        return (float) $this->buy_price_avg;
    }
    
    public function setTotalBuyVolume(int $volume)
    {
        $this->total_buy_volume = $volume;
    }
    
    public function getTotalBuyVolume() : float
    {
        return $this->total_buy_volume;
    }
    
    public function setTotalVolume(int $volume)
    {
        $this->total_volume = $volume;
    }
    
    public function getTotalVolume()
    {
        return $this->total_volume;
    }
    
    public function getSellDelta()
    {
        return $this->getTotalVolume() - $this->getAmount();
    }
    
    public function getBuyDelta()
    {
        return $this->getTotalBuyVolume() - $this->getAmountBuyOrder();
    }
    
    public function setImageUrl($image_url)
    {
        $this->image_url = $image_url;
    }
    
    public function getImageUrl()
    {
        return json_decode($this->image_url,true);
    }
    
    public function setImageUrlSize($url, int $size)
    {
        if($size <= 0)
            throw new Exception("Size cannot be lower or equal 0");
            $this->decoded_image_url[$size] = $url;
            $this->image_url = json_encode($this->decoded_image_url);
    }
    
    public function getImageUrlSize(int $size)
    {
        $array = @json_decode($this->image_url,true);
        if(!is_array($array))
            $array = array();
            if(empty($array[$size])) {
                $array[$size] = LinkMng::getEvEImageUrl($this->getTypeId(), 128);
                $this->save();
            }
            return $array[$size];
    }
    
    public function setName($name) {
        $this->name = $name;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function setTypeId(int $type_id)
    {
        $this->type_id = $type_id;;
    }
    
    public function getTypeId():int
    {
        return $this->type_id;
    }
    
    public function setHubId(int $hub_id)
    {
        $this->hub_id = $hub_id;;
    }
    
    public function getHubId():int
    {
        return $this->hub_id;
    }
    
    public function setGroupId(int $group_id)
    {
        $this->group_id = $group_id;;
    }
    
    public function getGroupId():int
    {
        return $this->group_id;
    }
    
    public function setMarketGroupId(int $group_id)
    {
        $this->market_group_id = $group_id;;
    }
    
    public function getMarketGroupId():int
    {
        return $this->market_group_id;
    }
    
    public function setCategoryId(int $category_id)
    {
        $this->category_id = $category_id;;
    }
    
    public function getCategoryId():int
    {
        return $this->category_id;
    }
    
    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = $timestamp;;
    }
    
    public function getTimestamp():int
    {
        return $this->timestamp;
    }
    
    public function setActiveItem(int $active_item)
    {
        if($active_item != $this->active_item) {
            $active = $active_item ? "active" : "inactive";
            $this->itemChange[] = "The item changed to $active on the market watch";
        }
        
        
        $this->active_item = $active_item;
    }
    
    public function getActiveItem():int
    {
        return $this->active_item;
    }
    
    public function setAmount(int $amount)
    {
        if($amount > 0 && $this->amount > 0 && $amount != $this->amount)
            $this->itemChange[] = "The item amount to be stocked in marked has changed (target value).";
            $this->amount = $amount;
    }
    
    public function getAmount():int
    {
        return $this->amount;
    }
    
    public function setAmountBuyOrder(int $amount)
    {
        if($amount > 0 && $this->amount_buy_order > 0 && $amount != $this->amount_buy_order)
            $this->itemChange[] = "The item amount to be on buyout/demand in marked has changed (target value).";
            $this->amount_buy_order = $amount;
    }
    
    public function getAmountBuyOrder():int
    {
        return $this->amount_buy_order;
    }
    
    
    public function setSettings(string $jsonEncoded)
    {
        $this->settings = $jsonEncoded;
    }
    
    public function setSettingsArray(array $settings) {
        $this->setSettings(json_encode($settings));
    }
    
    public function getSettings()
    {
        return json_decode($this->settings, true);
    }
    
    public function setLastChange(string $jsonEncoded)
    {
        $this->last_change = $jsonEncoded;
    }
    
    public function setLastChangeArray(array $last_change) {
        $this->setLastChange(json_encode($settings));
    }
    
    public function getLastChange()
    {
        return json_decode($this->last_change, true);
    }
    
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
            array_unshift($this->itemChange, "Item Changed: ".date("Y/m/d H:i:s", time())." by {{ unknown_user_id }}");
            $saveSettingJson = $this->getSettings();
            if(empty($saveSettingJson['change_history']))
                $saveSettingJson['change_history'] = array();
                $saveSettingJson['change_history'] = array_merge($saveSettingJson['change_history'],$this->itemChange);
                $this->setSettingsArray($saveSettingJson);
        }
        
        $vars = get_object_vars (  $this ) ;
        
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
}
?>