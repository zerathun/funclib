<?php
namespace Funclib;

use \Exception as Exception;
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
use Funclib\EvE\EvEToken;

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
    public function callESI(string $method, int|string $identKey, $args=array(), bool $use_cache=true, int $expire=(3600*6), $throw_errors=false) {
        if(($identKey == null OR $identKey <= 0) && !is_string($identKey)) {
            throw new Exception("Ident Key is not valid!");
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
                        ErrorHandler::getErrorHandler()->addException($e);
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
                        ErrorHandler::getErrorHandler()->addException($e);
                    } catch (Exception $e) {
                        ErrorHandler::getErrorHandler()->addException($e);
                    }
                    
                    $object = ObjectSerializer::sanitizeForSerialization($result);
                    
                    $formed_Obj = $this->formResult($object);
                    $serialized = json_encode($formed_Obj);
                    
                    $serialized = Database::getInstance()->PDOQuote($serialized);
                    
                    $sql = "INSERT INTO auth_esicache (class, methodFunction, ident_key, timestamp, value)
                VALUES ('$class', '$method', ".$identKey.", ".time().", ".$serialized.")
            ON DUPLICATE KEY UPDATE class='$class', methodFunction='$method', ident_key=".$identKey.", timestamp=".time().", value=".$serialized."";
                    
                    try {
                        Database::getInstance()->sql_query($sql);
                    } catch (Exception $e) {
                        ErrorHandler::getErrorHandler()->addException($e);
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
    
    
    /**
     * VARIABLE {VARIABLE}
     *
     * @param string $method
     * @param string $url
     * @param EvEToken $token
     * @return mixed|NULL[][]|boolean
     */
    public function DirectCallESI(string $post_url, EvEToken $token = null)
    {
        // CURL ALTERNATIVE
        //$url = preg_replace($pattern, $replacement, $subject)
        
        //$post_url = "https://esi.evetech.net/latest/corporations/$corp_id/titles/?datasource=tranquility";
        $curl = curl_init($post_url);
        
        if($token != null) {
            $headers = array(
                // 'Content-Type: application/json',
                'accept: application/json',
                'authorization: Bearer '.$token->getAccessToken()
            );
        } else {
            $headers = array(
                // 'Content-Type: application/json',
                'accept: application/json',
            );
        }
        
        //curl_setopt($curl, CURLOPT_USERPWD, "username":"Password");
        curl_setopt($curl, CURLOPT_URL, $post_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($curl, CURLOPT_POST, false);
        //curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($post_data) );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        try {
            $post_response = curl_exec($curl);
            
        } catch (Exception $e) {
            ErrorHandler::getErrorHandler()->addException($e);
        }
        
        $response = json_decode($post_response);
        
        return $response;
    }
    
    
    private static $curl_useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.91 Safari/537.36';
    private $SoftwareClient_ID = "";
    private $secret_key = "";
    
    public function SetSoftwareClientId($string)
    {
        $this->SoftwareClient_ID = $string;
    }
    
    public function SetClientSecretKey($string)
    {
        $this->secret_key = $string;
    }
    
    private function performCurlRequest($fields) {
        $header='Authorization: Basic '.base64_encode($this->SoftwareClient_ID.':'.$this->secret_key);
        $fields_string='';
        foreach ($fields as $key => $value) {
            $fields_string .= $key.'='.$value.'&';
        }
        rtrim($fields_string, '&');
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, 'https://login.eveonline.com/oauth/token');
        curl_setopt($ch, CURLOPT_USERAGENT, EvE_ESI_Caller::$curl_useragent);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $result = curl_exec($ch);
        
        if ($result===false) {
            auth_error(curl_error($ch));
        }
        curl_close($ch);
        return json_decode($result);
    }
    
    public function CheckIfTokenExpired(EvETOken $token)
    {
        return (($token->getTimestamp() + $token->getExpires_in() - 60) < time());
    }
    
    
    private function GenerateUrlEncodedPost($array)
    {
        $result = "";
        foreach($array as $key => $value)
        {
            $result .= $key . "?" . htmlspecialchars($value);
        }
        return $result;
    }
    
    
    public function RefreshToken(EvEToken $token) : EvEToken
    {
        $jsonResult = $this->performCurlRequest(
            array('refresh_token' => $token->getRefreshToken(),
                'grant_type' => 'refresh_token'
                
            ));
        
        $token->setRefreshToken($jsonResult->refresh_token);
        $token->setAccessToken($jsonResult->access_token);
        $token->setExpires_in($jsonResult->expires_in);
        
        
        $dbh = Database::getInstance()->getPDOConnection('auth');
        $sql = "
            UPDATE auth_evetokens SET AccessToken = :AccessToken, expires_in = :ExpiresIn, RefreshToken = :RefreshToken
            WHERE user_id = :UserId AND CharacterID = :CharacterID AND TokenType = :TokenType AND ServiceID = :ServiceID";
        
        $q=$dbh->prepare($sql);
        
        $q->bindValue(':AccessToken', $token->getAccessToken(), PDO::PARAM_STR);
        $q->bindValue(':RefreshToken', $token->getRefreshToken(), PDO::PARAM_STR);
        $q->bindValue(':ExpiresIn', $token->getExpires_in(), PDO::PARAM_INT);
        
        $q->bindValue(':UserId', $token->getUser_id(), PDO::PARAM_INT);
        $q->bindValue(':CharacterID', $token->getCharacterID(), PDO::PARAM_INT);
        $q->bindValue(':TokenType', $token->getTokenType(), PDO::PARAM_STR);
        $q->bindValue(':ServiceID', $token->getServiceID(), PDO::PARAM_INT);
        $q->execute();
        
        
        return $token;
    }
    
    /**
     * VARIABLE {VARIABLE}
     *
     * @param string $method
     * @param string $url
     * @param EvEToken $token
     * @return mixed|NULL[][]|boolean
     */
    public function DirectESICallGET(string $url_without_fields, $fields, EvEToken $token = null)
    {
        if(!empty($token) && $this->CheckIfTokenExpired($token))
        {
            $token = $this->RefreshToken($token);
        }
        
        if(!empty($token))
        {
            $headers = array(
                'Accept: application/json',
                'authorization: Bearer '.$token->getAccessToken(),
            );
        } else {
            $headers = array(
                'Accept: application/json',
            );
        }
        
        
        $fields_string='';
        foreach ($fields as $key => $value) {
            $fields_string .= $key.'='.$value.'&';
        }
        rtrim($fields_string, '&');
        
        $url_without_fields .= "?".$fields_string;
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url_without_fields);
        curl_setopt($ch, CURLOPT_USERAGENT, EvE_ESI_Caller::$curl_useragent);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_POST, count($fields));
        //curl_setopt($ch, CURLOPT_POST, 0);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, null);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        
        curl_setopt($ch, CURLINFO_HEADER_OUT, false); // enable tracking
        
        $result = curl_exec($ch);
        //$headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT ); // request headers
        //print_r($headerSent);
        
        if ($result===false) {
            
            print_r(curl_error($ch));
        }
        curl_close($ch);
        return json_decode($result);
    }
    
    
    public function SearchApi($searchString = '', $Category, EvEToken $token, $strict = false)
    {
        // Only allow a specific category allowed by ESI Call
        switch(strtolower($Category))
        {
            case "agent": $Category = $Category; break;
            case "alliance":  $Category = $Category; break;
            case "character":  $Category = $Category; break;
            case "constellation":  $Category = $Category; break;
            case "corporation":  $Category = $Category; break;
            case "faction":  $Category = $Category; break;
            case "inventory_type":  $Category = $Category; break;
            case "region":  $Category = $Category; break;
            case "solar_system":  $Category = $Category; break;
            case "station":  $Category = $Category;  break;
            case "structure":  $Category = $Category; break;
            default: $Category = "agent"; break;
        }
        
        if($strict)
            $strict = "true";
            else
                $strict = "false";
                
                $CharacterID = $token->getCharacterID();
                
                $post_url = "https://esi.evetech.net/latest/characters/".$CharacterID."/search/?categories=".$Category."&datasource=tranquility&language=en&search=".$searchString."&strict=".$strict."";
                $curl = curl_init($post_url);
                
                if($token != null) {
                    $headers = array(
                        // 'Content-Type: application/json',
                        'accept: application/json',
                        'authorization: Bearer '.$token->getAccessToken()
                    );
                } else {
                    throw new Exception("Token is not valid");
                }
                
                //curl_setopt($curl, CURLOPT_USERPWD, "username":"Password");
                curl_setopt($curl, CURLOPT_URL, $post_url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                //curl_setopt($curl, CURLOPT_POST, false);
                //curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($post_data) );
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
                
                try {
                    $post_response = curl_exec($curl);
                    
                    
                    
                } catch (Exception $e) {
                    ErrorHandler::getErrorHandler()->addException($e);
                }
                
                $response = json_decode($post_response);
                
                if(!empty($response->error))
                {
                    ErrorHandler::getErrorHandler()->addException(new Exception("EvE ESI Error: ".$response->error));
                }
                
                return $response;
    }
    
}

?>