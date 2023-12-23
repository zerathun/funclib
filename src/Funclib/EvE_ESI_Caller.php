<?php 
namespace EmbDev\models;

use EmbDev\models\ssoHandler;
use Database\Database;
use Funclib\ErrorHandler;
use Swagger\Client\ObjectSerializer;
use Swagger\Client\ApiException;
use Swagger\Client\Api\AllianceApi;
use Swagger\Client\Api\AssetsApi;
use Swagger\Client\Api\BookmarksApi;
use Swagger\Client\Api\CalendarApi;
use Swagger\Client\Api\CharacterApi;
use Swagger\Client\Api\ClonesApi;
use Swagger\Client\Api\ContactsApi;
use Swagger\Client\Api\CorporationApi;
use Swagger\Client\Api\DogmaApi;
use Swagger\Client\Api\FactionWarfareApi;
use Swagger\Client\Api\FittingsApi;
use Swagger\Client\Api\FleetsApi;
use Swagger\Client\Api\IncursionsApi;
use Swagger\Client\Api\IndustryApi;
use Swagger\Client\Api\InsuranceApi;
use Swagger\Client\Api\KillmailsApi;
use Swagger\Client\Api\LocationApi;
use Swagger\Client\Api\LoyaltyApi;
use Swagger\Client\Api\MailApi;
use Swagger\Client\Api\MarketApi;
use Swagger\Client\Api\OpportunitiesApi;
use Swagger\Client\Api\PlanetaryInteractionApi;
use Swagger\Client\Api\RoutesApi;
use Swagger\Client\Api\SearchApi;
use Swagger\Client\Api\SkillsApi;
use Swagger\Client\Api\SovereigntyApi;
use Swagger\Client\Api\StatusApi;
use Swagger\Client\Api\UniverseApi;
use Swagger\Client\Api\UserInterfaceApi;
use Swagger\Client\Api\WalletApi;
use Swagger\Client\Api\WarsApi;

class EvE_ESI_Caller {
    
    private $methods;
    private $ApiObjects;
    private $config;
    
    public function __construct($config) {
        
        $this->config = $config;
        $client = new \GuzzleHttp\Client();
        $this->ApiObjects = array(
            'AllianceApi' => new AllianceApi($client, $config),
            'AssetsApi' => new AssetsApi($client, $config),
            'BookmarksApi' => new BookmarksApi($client, $config),
            'CalendarApi' => new CalendarApi($client, $config),
            'CharacterApi' => new CharacterApi($client, $config),
            'ClonesApi' => new ClonesApi($client, $config),
            'ContactsApi' => new ContactsApi($client, $config),
            'CorporationApi' => new CorporationApi($client, $config),
            'DogmaApi' => new DogmaApi($client, $config),
            'FactionWarfareApi' => new FactionWarfareApi($client, $config),
            'FittingsApi' => new FittingsApi($client, $config),
            'FleetsApi' => new FleetsApi($client, $config),
            'IncursionsApi' => new IncursionsApi($client, $config),
            'IndustryApi' => new IndustryApi($client, $config),
            'InsuranceApi' => new InsuranceApi($client, $config),
            'KillmailsApi' => new KillmailsApi($client, $config),
            'LocationApi' => new LocationApi($client, $config),
            'LoyaltyApi' => new LoyaltyApi($client, $config),
            'MailApi' => new MailApi($client, $config),
            'MarketApi' => new MarketApi($client, $config),
            'OpportunitiesApi' => new OpportunitiesApi($client, $config),
            'PlanetaryInteractionApi' => new PlanetaryInteractionApi($client, $config),
            'RoutesApi' => new RoutesApi($client, $config),
            'SearchApi' => new SearchApi($client, $config),
            'SkillsApi' => new SkillsApi($client, $config),
            'SovereigntyApi' => new SovereigntyApi($client, $config),
            'StatusApi' => new StatusApi($client, $config),
            'UniverseApi' => new UniverseApi($client, $config),
            'UserInterfaceApi' => new UserInterfaceApi($client, $config),
            'WalletApi' => new WalletApi($client, $config),
            'WarsApi' => new WarsApi($client, $config),
        );
        
        $this->methods = array();
        foreach($this->ApiObjects as $class) {
            $array = get_class_methods($class);
            foreach($array as $methodName) {
                if($methodName != '__construct' && $methodName != 'getConfig') {
                    $cn = get_class($class);
                    $this->methods[$methodName] = array('full_class' => $cn, 'class' => str_replace('Swagger\\Client\\Api\\', '', $cn));
                }
            }
        }
    }
    
    private function CreateTable()
    {
        $create_table = "
            DROP TABLE IF EXISTS `auth_esicache`;
            
            CREATE TABLE `auth_esicache` (
            `class` varchar(255) NOT NULL,
            `methodFunction` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
            `ident_key` int NOT NULL,
            `timestamp` int NOT NULL,
            `value` json NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            
            ALTER TABLE `auth_esicache`
            ADD PRIMARY KEY (`class`,`methodFunction`,`ident_key`);
        ";
    }
    
    public static function converteEveTime($string) {
        $arr1 = preg_split("[T]", $string);
        $arr2 = preg_split("(-)", $arr1[0]);
        $arr3 = preg_split("(\+)", $arr1[1]);
        $arr4 = preg_split("(:)", $arr3[0]);
        
        $time = mktime(10,07,10,5,4,2009);
        if(is_array($arr1) && is_array($arr2) && is_array($arr3) && is_array($arr4))
        $time = mktime(intval($arr4[0]), intval($arr4[1]), intval($arr4[2]), intval($arr2[2]), intval($arr2[1]), intval($arr2[0]));

        return $time;
    }
    
    public function setToken() {
        
    }
    
    public function getEsiCacheAge($method, $args = array())
    {
        if(!empty($args)) {
            $class = $this->methods[$method]['class'];
            $sql = "SELECT * FROM auth_esicache WHERE class = '$class' AND methodFunction = '$method' AND ident_key = '".$args[0]."' AND timestamp  > ".(time()-$expire);
            print $sql;
        }
    }
    
    /**
     * 
     * @param string $method
     * @param int $identKey
     * @param array $args
     * @param boolean $use_cache
     * @param number $expire
     * @param boolean $throw_errors
     * @throws Ambigous <Exception, ApiException>
     * @return boolean|unknown
     */
    public function callESI(string $method, int $identKey, $args=array(), bool $use_cache=true, int $expire=(3600*6), $throw_errors=false) {
        if($identKey == null OR $identKey <= 0) {
            throw new \Exception("Ident Key is not valid!");
        }
        if(empty($this->methods[$method]))
            throw new Exception("The Wanted CALL ESI Method is Unknown");
        if(empty($this->methods[$method]['class']))
            throw new Exception("Class unknown!");
        $class = $this->methods[$method]['class'];
                
                
        if($use_cache) {
            PerformanceMeasure::getInstance()->addMeasurementCheckpoint("db_".$method);
            
            $sql = "SELECT * FROM auth_esicache WHERE class = '$class' AND methodFunction = '$method' AND ident_key = '".$identKey."' AND timestamp  > ".(time()-$expire);
            $row = null;
            try {
                $row = Database::getInstance()->sql_fetch_array(Database::getInstance()->sql_query($sql));
            } catch (Exception $e) {
                print $e;
            }
        }
        
        if(!empty($row) && $use_cache) {
            $object = json_decode($row['value']);
            PerformanceMeasure::getInstance()->stopMeasurementCheckpoint("db_".$method);
            return $object;
        } else {
            PerformanceMeasure::getInstance()->addMeasurementCheckpoint($method);
            try {
                if(count($args) < 1) 
                    $args[0] = $identKey;
                $result = $this->callMethod($method, $args);
            } catch (ApiException $e) {
                $ssoHandler = new ssoHandler();
                $ssoHandler->refreshAllTokens();
                try {
                    $result = $this->callMethod($method, $args);
                } catch (ApiException $e) {
                    $delInvalidToken = "DELETE FROM auth_evetokens WHERE AccessToken = '".$this->config->getAccessToken()."'";
                    //Database::getInstance()->sql_query($delInvalidToken);
                    ErrorHandler::getErrorHandler()->addError("Currently used access token is not valid/temporarly unavailable");
                    if($throw_errors)
                        throw $e;
                    else {
                        ErrorHandler::getErrorHandler()->addException($e);
                    return false;
                    }
                }
            } catch (\Exception $e) {
                print_r($e);
            }
            
            $object = ObjectSerializer::sanitizeForSerialization($result);
            $formed_Obj = $this->formResult($object);
            $serialized = json_encode($formed_Obj);
            $serialized = Database::getInstance()->PDOQuote($serialized);
            
            $sql = "INSERT INTO auth_esicache (class, methodFunction, ident_key, timestamp, value) 
                VALUES ('$class', '$method', ".$args[0].", ".time().", ".$serialized.")
            ON DUPLICATE KEY UPDATE class='$class', methodFunction='$method', ident_key=".$identKey.", timestamp=".time().", value=".$serialized."";
            try {
                Database::getInstance()->sql_query($sql);
            } catch (Exception $e) {
                print $e;
            }
            PerformanceMeasure::getInstance()->stopMeasurementCheckpoint($method);
            return $formed_Obj;
        }
    }
    
    private function formResult($result) {
        if(is_object($result))
            $array = get_object_vars($result);
        elseif(is_array($result))
            $array = $result;
        else
            ErrorHandler::getErrorHandler()->addError("Unknown datatype in ESICache");
            
        foreach($array as $key => $value) {
            if(!empty($result->$key)) {
                if(is_string($result->$key))
                    $result->$key = htmlspecialchars($result->$key);
            }
        }
        return $result;
    }
    
    private function callMethod($methodname, $args = array()) {
        if(!empty($this->methods[$methodname])) {
            if(!empty($this->ApiObjects[$this->methods[$methodname]['class']])) {
                $object = $this->ApiObjects[$this->methods[$methodname]['class']];
                if(count($args) == 1)
                    $result = $object->$methodname($args[0]);
                elseif(count($args) == 2)
                    $result = $object->$methodname($args[0], $args[1]);
                elseif(count($args) == 3)
                 $result = $object->$methodname($args[0], $args[1], $args[2]);
                elseif(count($args) == 4)
                    $result = $object->$methodname($args[0], $args[1], $args[2], $args[3]);
                elseif(count($args) == 5)
                    $result = $object->$methodname($args[0], $args[1], $args[2], $args[3], $args[4]);
                elseif(count($args) < 1)
                    throw \Exception;
                else
                    $result = $object->$methodname($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
                return $result;
            } else {
                return false;
            }
        } else return false;
    }
    
}

?>