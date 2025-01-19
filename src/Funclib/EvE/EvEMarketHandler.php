<?php

namespace EmbIndustry\Market;


use Funclib\ErrorHandler;
use Database\Database;
use EmbIndustry\LinkMng;
use \Exception as Exception;


class EvEMarketHandler
{
    
    private function __construct() { }
    
    private ?MarketHub $marketHub = null;
    private array $marketHubList;
    
    protected static EvEMarketHandler $instance;
    
    public static function getInstance() : EvEMarketHandler {
        if(empty(EvEMarketHandler::$instance)) {
            EvEMarketHandler::$instance = new EvEMarketHandler();
        }
        return EvEMarketHandler::$instance;
    }
    
    
    
    
}



?>