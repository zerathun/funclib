<?php 
namespace Funclib\EvE;

use Funclib\EvE\EvEMarketHub;
use Funclib\EvE\EvEMarketItem;

use Funclib\ErrorHandler;
use Funclib\Database;
use Funclib\LinkMng;
use Funclib\EvE_ESI_Caller;
use \PDO;
use \Exception as Exception;

class EvEMarketPriceManager
{
    
    private function __construct() { }

    private array $marketHubList;
    private EvEToken $token;
    private $ESICaller;
    private $tempMarketData = array();
    
    protected static EvEMarketPriceManager $instance;

    public static function getInstance() : EvEMarketPriceManager 
    {
        if(empty(EvEMarketPriceManager::$instance)) 
        {
            EvEMarketPriceManager::$instance = new EvEMarketPriceManager();
        }
        return EvEMarketPriceManager::$instance;
    }

    public function SetEvEToken(EvEToken $token)
    {
        $this->token = $token;
    }

    public function getSelectedToken()
    {
        return $this->token;
    }
    
    public function SetESICaller($esicaller)
    {
        $this->ESICaller = $esicaller;
    }
    
    public function SetMarketHub(EvEMarketHub $marketHub)
    {
        $this->marketHubList[$marketHub->getId()] = $marketHub;
    }
    
    public function GetMarketHub(int $hub_id)
    {
        if(!empty($this->marketHubList[$hub_id]))
        {
            return $this->marketHubList[$hub_id];
        } else return null;
    }
    
    
    public function LoadMarketTypeIdIntoAllHubs(int $type_id, $refresh_from_db = false)
    {
        $sql = "SELECT * FROM `embindustry_marketitem` WHERE type_id = ".intval($type_id);
        $res = Database::getInstance()->sql_query($sql);

        $counter = 0;
        while($row = Database::getInstance()->sql_fetch_object($res))
        {
            // Check wether the Hub exists / loaded in database / has access through loaded market hubs
            $counter++;
            if( !empty($this->marketHubList[$row->hub_id]) )
            {
                $hub = $this->marketHubList[$row->hub_id];
                $item = $hub->GetMarketItem($type_id);
                
                if(empty($item) || !$refresh_from_db)
                {
                    if(empty($item))
                    {
                        $item = new EvEMarketItem();
                        $item->setWithObject($row);
                        $hub->AddMarketItem($item);
                    }
                    
                    if($hub->getStructureHub())
                    {
                        // Privately Own Market Structure
                        $this->UpdateMarketAverageDataTypeId($hub->getId(), $item->getTypeId(), $refresh_from_db, true);
                    } else 
                        
                    {
                        // Public Market / System
                        $this->UpdatePublicMarketDataPricesObj($hub, $item->getTypeId(), $refresh_from_db);
                    }
                }
            }
        }
        
        $flag = false;
        // If an Item was not in the Market-Hub List add them to the market watch list
        if($counter <= count($this->marketHubList))
        {
            $typeObj = $this->SearchTypeIDWithTypeID($type_id);
            
            foreach($this->marketHubList as $marketHub)
            {
                $item = $marketHub->GetMarketItem($type_id);
                if(empty($item) && !empty($typeObj))
                {
                    //21 	30003731 	Hedbergite 	454 	25 	[] 	0 	[] 	0 	0 	0 	527 		0 	0 	0
                    
                    $sql = "INSERT INTO `embindustry_marketitem`
                    (`type_id`, `hub_id`, `name`, `group_id`, `category_id`, `active_item`, `settings`, `timestamp`, `last_change`, `image_url`)
                    VALUES (:TypeId, :HubId, :ItemName, :GroupId, :CategoryId, :ActiveItem, :Settings, :Timestamp, :LastChange, :ImageUrl)
                    ON DUPLICATE KEY UPDATE name=:ItemName, group_id = :GroupId, category_id = :CategoryId, active_item = :ActiveItem, settings = :Settings, timestamp = :Timestamp, last_change = :LastChange, image_url = :ImageUrl";
                    
                    $dbh = Database::getInstance()->getPDOConnection('ember-industry');
                    $q=$dbh->prepare($sql);
                    $q->bindValue(':TypeId', intval($type_id), PDO::PARAM_INT);
                    $q->bindValue(':HubId', intval($marketHub->getId()), PDO::PARAM_INT);
                    
                    $q->bindValue(':ItemName', $typeObj->name, PDO::PARAM_STR);
                    
                    $q->bindValue(':Settings', json_encode(array()), PDO::PARAM_STR);
                    $q->bindValue(':Timestamp', time(), PDO::PARAM_INT);
                    $q->bindValue(':LastChange', json_encode(array()), PDO::PARAM_STR);
                    $q->bindValue(':ImageUrl', json_encode(array()), PDO::PARAM_STR);
                    $q->bindValue(':GroupId', intval($typeObj->group_id), PDO::PARAM_INT);
                    
                    $category_id = 0;
                    if($typeObj->group_id > 0)
                    {
                        $sql2 = "SELECT * FROM `eve_static_groups` WHERE group_id = ".intval($typeObj->group_id);
                        $q2=$dbh->prepare($sql2);
                        $q2->execute();
                        $r=$q2->fetch(PDO::FETCH_OBJ);
                        if(!empty($r))
                        {
                            $category_id = $r->category_id;
                        }
                    }
                    
                    $q->bindValue(':CategoryId', intval($category_id), PDO::PARAM_INT);
                    $q->bindValue(':ActiveItem', 0, PDO::PARAM_INT);
                    $q->execute();
                    
                    /*(class, methodFunction, ident_key, timestamp, value)
                     VALUES ('$class', '$method', ".$identKey.", ".time().", ".$serialized.")
                     ON DUPLICATE KEY UPDATE class='$class', methodFunction='$method', ident_key=".$identKey.", timestamp=".time().", value=".$serialized."";
                     ";*/
                    $flag = true;
                }
            }
            
            if($flag)
                $this->LoadMarketTypeIdIntoAllHubs($type_id, false);
        }

    }
    
    
    public function UpdateMarketOrdersInStructure(EvEMarketHub $hub)
    {
        $structureId = $hub->getId();
        $this->marketHubList[$structureId] = $hub;
            
        if(!empty($this->tempMarketData[$structureId]))
        {
            return $this->tempMarketData[$structureId];
        }
        $url = "https://esi.evetech.net/latest/markets/structures/$structureId/";

        $page = 0;
        $structOrders = array();
        while(empty($structureOrders->error))
        {
            $page++;
            $fields = array('datasource' => 'tranquility', 'page' => $page);
            $structureOrders = $this->ESICaller->DirectESICallGET($url, $fields, $this->token, true);
            if(empty($structureOrders->error))
            {
                foreach($structureOrders as $struc)
                {
                    $structOrders[$struc->type_id][] = $struc;
                }
            }
        }
        $this->indexedItemType[$structureId] = $structOrders;

        $this->calculateAveragePrice($structureId);
        
        return;
    }
    
       
    private $averageItemPrice = array();
    private $indexedItemType = array();
    
    public function calculateAveragePrice($hub_id)
    {
        if(!empty($hub_id) && $hub_id > 0 && $this->indexedItemType[$hub_id])
        {
            $marketHub = $this->marketHubList[$hub_id];
            
            foreach($this->indexedItemType[$hub_id] as $type_id => $mktData)
            {           
                // Sell: 61´640.00 ISK (Volume: 120'770)
                
                /*
                 * stdClass Object[] (
                 *  [price] => 699900 [range] => region
                 *  [issued] => 2023-10-26T14:51:35+00:00
                 *  [type_id] => 4391 [duration] => 90
                 *  [order_id] => 6622709766
                 *  [min_volume] => 1
                 *  [location_id] => 1035949018593
                 *  [is_buy_order] =>
                 *  [volume_total] => 2
                 *  [volume_remain] => 2 )
                 */
                if(!empty($mktData))
                {
                    $tot_amount_items = 0;
                    $tot_price = 0;
                    $tot_amount_items_buy_order = 0;
                    $tot_buy_orders = 0;
                    $tot_price_buy_order = 0;
                    $lowPrice = 0;    
                    $highBuyPrice = 0;
                    foreach($mktData as $mkD)
                    {   
                        if($mkD->is_buy_order)
                        {
                            $tot_amount_items_buy_order += $mkD->volume_remain;
                            $tot_buy_orders++;
                            $tot_price_buy_order += $mkD->price;
                            
                            if($highBuyPrice == 0)
                            {
                                $highBuyPrice = $mkD->price;
                            }
                                else if ($highBuyPrice < $mkD->price)
                            {
                                $highBuyPrice = $mkD->price;
                            }
                        } else 
                        {
                        
                            //print "Add Type ID: ".$type_id."\n\n";
                            $tot_amount_items += $mkD->volume_remain;
                            $tot_price += $mkD->price;

                            if($lowPrice == 0)
                                $lowPrice = $mkD->price;
                            else if ($lowPrice > $mkD->price)
                            {
                                $lowPrice = $mkD->price;
                            }
                        }
                    }
                    
                    if($tot_amount_items > 0)
                    {
                        $avg_price = $tot_price / $tot_amount_items;
                        
                        
                    }
                    else
                    {
                        $avg_price = 0;
                    }
                       
                    
                        
                    if($tot_buy_orders > 0)
                    {
                        $avg_price_buy_order = $tot_price_buy_order / $tot_buy_orders;
                    } else {
                        $avg_price_buy_order = 0;
                    }
                    
                    
                    if($type_id == 4247)
                    {
                        print "Low Price: $lowPrice \n\n";
                        
                        
                        
                    }
                       
                    
                    $this->indexedItemType[$hub_id][$type_id] = EvEMarketPriceManager::SetupAverageArray($type_id,
                        $tot_amount_items,
                        $tot_amount_items_buy_order,
                        $avg_price_buy_order,
                        $tot_price,
                        $lowPrice,
                        $highBuyPrice,
                        is_object($mktData) ? $mktData->location_id : 0);

                    $item = $marketHub->GetMarketItem($type_id);
                    
                    
                    if(!empty($item)) {
                        $this->setAverageData($item, $this->indexedItemType[$hub_id][$type_id]);
                        $item->save();
                    }
                }
            }
        }
    }
    
    private static function SetupAverageArray( int $type_id, 
                                        int $tot_amount_items, 
                                        int $tot_amount_items_buy_order, 
                                        float $avg_price_buy_order, 
                                        float $tot_price, 
                                        float $lowestSellPrice,
                                        float $highestBuyPrice,
                                        int $location_id)
    {
        return array(
            'type_id' => $type_id,
            'volume_total' => $tot_amount_items,
            'buy_volume_total' => $tot_amount_items_buy_order,
            'buy_price_avg' => $avg_price_buy_order,
            'price' => $tot_price,
            'lowest_sell_price' => $lowestSellPrice,
            'highest_buy_price' => $highestBuyPrice,
            'location_id' => $location_id,
        );
    }
    
    private $marketData = array();
    
    public function getAvgMarketData(int $hub_id) : array
    {
        if(!is_array($this->indexedItemType) || empty($this->indexedItemType[$hub_id]))
            return array();
        return $this->indexedItemType[$hub_id];
    }
    
    public function UpdateMarketAverageDataTypeId(int $hub_id, int $type_id, bool $force_update = false, bool $save_todatabase = true) : bool
    {
        if($hub_id < 0)
            throw new Exception("Hub ID is not set");
        if($type_id < 0)
            throw new Exception("Type ID is not set");
        
            
        if(empty($this->marketHubList[$hub_id]))
        {
            return false;
        } else {
            $marketHub = $this->marketHubList[$hub_id];
        }
        
        $hubItem = $marketHub->GetMarketItem($type_id);

        $averagePriceData = EvEMarketPriceManager::getInstance()->getAvgMarketData($hub_id);
            
        if(empty($averagePriceData[$type_id]))
        {
            // IF the Item is not in the list, there is no order available for this item == Null Amount Null Price
            $averagePriceData[$type_id] = EvEMarketPriceManager::SetupAverageArray($type_id,
                0,
                0,
                0,
                0,
                0,
                0,
                $hub_id);
        }
        
        
        if(!empty($averagePriceData[$type_id]))
        {
            $marketItem = new EvEMarketItem($type_id, $hub_id);
            
            if(!empty($hubItem)) {
                $marketItem->setActiveItem($hubItem->getActiveItem());
                $marketItem->setAmount($hubItem->getAmount());
            } else {
                $marketItem->setAmount(0);
            }
            
            if(!empty($averagePriceData[$type_id]))
            {
                $data = $averagePriceData[$type_id];
                $this->setAverageData($marketItem, $data);
            }

            $url = "https://esi.evetech.net/latest/universe/types/$type_id/"; //?datasource=tranquility&language=en";
            $fields = array(
                'datasource' => 'tranquility',
                'language' => 'en',
            );
            // public function DirectESICallGET(string $url_without_fields, $fields, EvEToken $token = null, $debug=false)
            $typeObj = $this->ESICaller->DirectESICallGET($url,$fields,null,false);

            
            $marketItem->setName($typeObj->name);
            $marketItem->setGroupId($typeObj->group_id);
            if(!empty($typeObj->market_group_id))
                $marketItem->setMarketGroupId($typeObj->market_group_id);
            $marketItem->setCategoryId(0);
            $marketItem->setTimestamp(time());
            
            $marketItem->setImageUrlSize(LinkMng::getEvEImageUrl($type_id, 128), 128);
            $marketItem->setImageUrlSize(LinkMng::getEvEImageUrl($type_id, 64), 64);
            $marketItem->setImageUrlSize(LinkMng::getEvEImageUrl($type_id, 32), 32);
            $marketItem->setImageUrlSize(LinkMng::getEvEImageUrl($type_id, 16), 16);
            if($save_todatabase) {
                $marketItem->save();
            }
            $marketHub->AddMarketItem($marketItem);
        }

        return true;
    }
    
    public function UpdatePublicMarketDataPricesObj(EvEMarketHub $hub, $type_id, bool $force_update = false) : bool
    {
        $marktItem = $hub->GetMarketItem($type_id);
        $publicMarketPrices = EvEMarketPriceManager::getInstance()->UpdatePublicMarketDataPrices($hub->getRegionId(),$hub->getSystemId(), $type_id);
        
        if(!empty($marktItem))
        {
            $settings = $marktItem->getSettings();
        } else
        {
            EvEMarketPriceManager::getInstance()->UpdateMarketAverageDataTypeId($hub->getId(), $type_id, true, false);
            $marktItem = $hub->GetMarketItem($type_id);
            $marktItem->setActiveItem(false);
        }
        if(!empty($marktItem))
        {
            $settings = $marktItem->getSettings();
        }
        
        $settings['marketAvgData'] = $publicMarketPrices;
        
        /**
         * Array
         (
         [lowest_sell_price] => 335700000
         [lowest_buy_price] => 24180000
         [highest_sell_price] => 550600000
         [highest_buy_price] => 330000000
         [total_volume_sell] => 152
         [total_volume_buy] => 54
         [avg_price_buy] => 0
         [avg_price_sell] => 0
         [median_price_buy] => 303600000
         [median_price_sell] => 373500000
         [buy_order_diff] => 5
         [sell_order_diff] => 38
         )
         */
        
        $marktItem->setTotalVolume($publicMarketPrices['total_volume_sell']);
        $marktItem->setTotalBuyVolume($publicMarketPrices['total_volume_buy']);
        $marktItem->setPrice($publicMarketPrices['median_price_sell']);
        $marktItem->setBuyPrice($publicMarketPrices['median_price_buy']);
        $marktItem->setLowestSellPrice($publicMarketPrices['lowest_sell_price']);
        $marktItem->setHighestBuyPrice($publicMarketPrices['highest_buy_price']);
        
        
        $marktItem->setSettingsArray($settings);
        $marktItem->save(false);
        
        //MarketManager::getInstance()->UpdateMarketAverageDataTypeId($hub->getId(), $marktItem->getTypeId());
        return true;
    }
    
    public function UpdatePublicMarketDataPrices(int $region_id, int $system_id, int $type_id, bool $force_update = false) : array
    {
        if($system_id < 0)
            throw new Exception("System ID is not properly set");
        if($type_id < 0)
            throw new Exception("Type ID is not set");

        try {
            $page = 1;
            $itemData = array();
            do {
              $typeObj = $this->GetMarketOrdersInSystem($region_id, $system_id, $type_id, $page);

              if(!empty($typeObj))
              {
                  $itemData = array_merge($itemData, $typeObj);
                  $page++;
              }
              
            } while($typeObj != null && (count($typeObj) >= 500));
            
            $buy_price_array = array();
            $sell_price_array = array();
            
            $multiResult = array(
                'lowest_sell_price' => 0,
                'lowest_buy_price' => 0,
                'highest_sell_price' => 0,
                'highest_buy_price' => 0,
                'total_volume_sell' => 0,
                'total_volume_buy' => 0,
                'avg_price_buy' => 0,
                'avg_price_sell' => 0,
                'median_price_buy' => 0,
                'median_price_sell' => 0,
                'buy_order_diff' => 0, // Fullfilled orders
                'sell_order_diff' => 0, // Fullfilled orders
            );
            
            foreach($itemData as $data)
            {
                if($data->system_id > 0 && $data->system_id == $system_id)
                {
                    if(!empty($data->is_buy_order) && $data->is_buy_order)
                    {
                        // Is Buy Order
                        $buy_price_array[] = array($data->volume_remain, $data->price);
                        
                        // Calc lowest Buy Price
                        if($data->price < $multiResult['lowest_buy_price'] || $multiResult['lowest_buy_price'] <= 0)
                            $multiResult['lowest_buy_price'] = $data->price;
                            
                            // Calc highest Buy Price
                            if($data->price > $multiResult['highest_buy_price'])
                                $multiResult['highest_buy_price'] = $data->price;
                                
                            $multiResult['total_volume_buy'] += $data->volume_remain;
                            $multiResult['buy_order_diff'] += ($data->volume_total - $data->volume_remain);
                                
                    } else {
                        // Is Sell Order
                        $sell_price_array[] = array($data->volume_remain, $data->price);
                        
                        // Calc lowest sell price
                        if($data->price < $multiResult['lowest_sell_price'] || $multiResult['lowest_sell_price'] <= 0)
                            $multiResult['lowest_sell_price'] = $data->price;
                            
                            // Calc lowest sell price
                            if($data->price > $multiResult['highest_sell_price'])
                                $multiResult['highest_sell_price'] = $data->price;
                                
                            $multiResult['total_volume_sell'] += $data->volume_remain;
                            $multiResult['sell_order_diff'] += ($data->volume_total - $data->volume_remain);
                    }
                }
            }
            
            $avgmed = EvEMarketPriceManager::calc_avgandmedian_price($sell_price_array);
            $multiResult['avg_price_sell'] = $avgmed[1];
            $multiResult['median_price_sell'] = $avgmed[0];
            $avgmed = EvEMarketPriceManager::calc_avgandmedian_price($buy_price_array);
            $multiResult['avg_price_buy'] = $avgmed[1];
            $multiResult['median_price_buy'] = $avgmed[0];

            return $multiResult;
        } catch (Exception $e) {
            ErrorHandler::getErrorHandler()->addException($e);
        }
        
        return array();
    }
    
    public function GetMarketOrdersInSystem(int $region_id, int $system_id, int $type_id, int $page=1, $order_type = 'all', )
    {
        if($region_id <= 0)
            throw new Exception("Region ID is invalid");
            if($system_id <= 0)
                throw new Exception("System ID is invalid");
                if($page <= 0)
                    throw new Exception("Page number is invalid");
                    
                    if($order_type != 'all' && $order_type != 'buy' && $order_type != 'sell')
                        throw new Exception("Invalid order type. The type can be 'all', 'buy' or 'sell'! ");
                        
                    try {
                        //https://esi.evetech.net/latest/markets/10000002/orders/?datasource=tranquility&order_type=all&page=1&type_id=34
                        //https://esi.evetech.net/latest/markets/10000002/orders/?datasource=tranquility&order_type=all&page=1&type_id=34
                        $url = "https://esi.evetech.net/latest/markets/$region_id/orders/"; //?datasource=tranquility&language=en";
                        $fields = array(
                            'datasource' => 'tranquility',
                            'language' => 'en',
                            'order_type' => 'all',
                            'page' => 1,
                            'type_id' => $type_id,
                        );
                        // public function DirectESICallGET(string $url_without_fields, $fields, EvEToken $token = null, $debug=false)
                        $result = $this->ESICaller->DirectESICallGET($url,$fields,null,false);
                        
                        //$result = $this->ESICaller->callESI('getMarketsRegionIdOrders',$region_id, array($order_type, $region_id, "tranquility", 0 , $page , $type_id),false,10800,true);
                        
                        return $result;
                    }
                    catch (Exception $ex)
                    {
                        ErrorHandler::getErrorHandler()->addException($ex);
                        return array();
                    }
    }
    
    public static function calculate_median_price($arr) {
        $prices = array();
        foreach ($arr as $sub_arr) {
            $amount = $sub_arr[0];
            $price = $sub_arr[1];
            for ($i = 0; $i < $amount; $i++) {
                array_push($prices, $price);
            }
        }
        sort($prices);
        $count = count($prices);
        $middleval = floor(($count - 1) / 2);
        if ($count % 2) {
            $median = $prices[$middleval];
        } else {
            $low = $prices[$middleval];
            $high = $prices[$middleval + 1];
            $median = (($low + $high) / 2);
        }
        
        return $median;
    }
    
    public static function calc_avgandmedian_price($items)
    {
        $total_price = 0;
        $prices = array();
        
        foreach ($items as $item) {
            $total_price += $item[0] * $item[1];
            array_push($prices, $item[1]);
        }
        
        if(count($items)  < 0)
            $average = $total_price / count($items);
        else
            $average = 0;
        
        sort($prices);
        $middle = floor(count($prices) / 2);
        
        if(count($prices) % 2) {
            $median = $prices[$middle];
        }
        else {
            if($middle <= 0)
                $middle = 1;
            $low = $prices[$middle - 1];
            $high = $prices[$middle];
            $median = (($low + $high) / 2);
        }
        return array($median, $average);
    }
    
    private function setAverageData(EvEMarketItem $item, array $data)
    {
        
        $item->setPrice($data['price']);
        $item->setTotalVolume($data['volume_total']);
        $item->setTotalBuyVolume($data['buy_volume_total']);
        $item->setBuyPrice($data['buy_price_avg']);
        $item->setLowestSellPrice($data['lowest_sell_price']);
        $item->setHighestBuyPrice($data['highest_buy_price']);  
    }
    
    public function getMarketData($hub_id)
    {
        if(!empty($this->marketData[$hub_id]))
            return $this->marketData[$hub_id];
        else return array();
    }
    
    public function GetCurrentMarketHub()
    {
        return $this->marketHub;
    }
    
    public function SetCurrentMarketHub(MarketHub $hub)
    {
        $this->marketHub = $hub;
    }
    
    public static function CanTypeIdBeMarket(int $type_id)
    {
        $array[35833] = true; // Fortizar
        $array[35826] = true; // Azbel
        $array[35825] = true; // Raitaru
        
        $array[35832] = true; // Astrahus
        $array[35834] = true; // Keepstar
        $array[35835] = true; // Athanor
        $array[35836] = true; // Tatara
        $array[35827] = true; // Sotiyo
        
        if(!empty($array[$type_id]))
            return $array[$type_id];
        else
            return false;
    }
    
    public function SearchTypeIDWithname($string, $strict=true)
    {
        /* The Item is unknown - fetch data from the EvE Universe to check if the item exists
        $url = "https://esi.evetech.net/latest/universe/types/$type_id/"; //?datasource=tranquility&language=en";
        $fields = array(
            'datasource' => 'tranquility',
            'language' => 'en',
        );
        // public function DirectESICallGET(string $url_without_fields, $fields, EvEToken $token = null, $debug=false)
        $typeObj = $this->ESICaller->DirectESICallGET($url,$fields,null,false);
        */
        $get_url = 'https://esi.evetech.net/latest/characters/'.$this->token->getCharacterID().'/search/';
        $fields = array(
            'datasource' => 'tranquility',
            'categories' => 'inventory_type',
            'search' => urlencode($string),
            'strict' => $strict?'true':'false',
        );
        
        $esiResult = $this->ESICaller->DirectESICallGET($get_url, $fields, $this->token, true);    

        return $esiResult;
    }
    
    /**
     * Checks if TypeID Exists and returns values;
     * If not returns false;
     * 
     * @param unknown $type_id
     * @param boolean $strict
     * @return unknown
     */
    public function SearchTypeIDWithTypeID(int $type_id, $strict=true)
    {
        /* The Item is unknown - fetch data from the EvE Universe to check if the item exists
         $url = "https://esi.evetech.net/latest/universe/types/$type_id/"; //?datasource=tranquility&language=en";
         $fields = array(
         'datasource' => 'tranquility',
         'language' => 'en',
         );
         // public function DirectESICallGET(string $url_without_fields, $fields, EvEToken $token = null, $debug=false)
         $typeObj = $this->ESICaller->DirectESICallGET($url,$fields,null,false);
         */

        $get_url = 'https://esi.evetech.net/latest/universe/types/'.intval($type_id).'/';
        $fields = array(
            'datasource' => 'tranquility',
            'language' => 'en',
        );
        
        $esiResult = $this->ESICaller->DirectESICallGET($get_url, $fields, null, true);
 
        return $esiResult;
    }
}


?>