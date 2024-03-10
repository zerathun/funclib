<?php 
namespace Funclib;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;

class LinkMng {
    
    private static $delete_links = array ('rhash' => -1, 'ruid' => -1, 'rglink' => -1);
    
    public static function getUrl(array $changed_variables) {
        if(count($changed_variables) < 1) {
            return LinkMng::url();
        }
        $appendix = "?";
        if(!isset($_GET) || empty($_GET)) {
            $arr_keys = array_keys($changed_variables);
            $count = 0;
            foreach($arr_keys as $kx) {
                if(strlen($changed_variables[$kx]) > 0) {
                    if($count > 0)
                        $appendix .= "&";
                        $appendix .= "$kx=".$changed_variables[$kx];
                        $count++;
                }
            }
        } else {
            foreach($_GET as $key => $value) {
                if(!array_key_exists($key, LinkMng::$delete_links)) {
                    if(array_key_exists ($key, $changed_variables)) {
                        if(strlen($changed_variables[$key]) > 0) {
                            $sbstr = substr($appendix, -1);
                            if($sbstr != '&' && $sbstr != '?')
                                $appendix .= "&";
                            $appendix .= "$key=".$changed_variables[$key];
                        }
                    } else {
                        if(strlen($appendix) > 1)
                            $appendix .= "&";
                        $appendix .= "$key=$value";
                    }
                }
            }
            
            foreach($changed_variables as $vk => $cv) {
                if(!array_key_exists($vk, $_GET) && $cv != "" && !empty($cv)) {
                    $sbstr = substr($appendix, -1);
                    if($sbstr != '&' && $sbstr != '?')
                        $appendix .= "&";
                    $appendix .= "$vk=".$cv;
                }
            }
        }
        return LinkMng::url().$appendix;
    }
    
    private static function url(){
        return sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],''
        );
    }
    
    public static function getCurrentURL() {
        $https = !empty($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'http';
        $server_name = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
        
        return sprintf(
            "%s://%s%s",
            !empty($https) && $https != 'off' ? 'https' : 'http',
            $server_name,$_SERVER['REQUEST_URI']
            );
    }
    
    public static function getEvEOnlineImageUrl($type_id, $size=64)
    {
        switch($size)
        {
            case 64: $ssize = 64; break;
            case 32: $ssize = 32; break;
            case 128: $ssize = 128; break;
            case 256: $ssize = 256; break;
            case 16: $ssize = 16; break;
            default: $ssize = 64;
        }
        
        return 'https://image.eveonline.com/Type/'.$type_id.'_'.$ssize.'.png';
    }
    
    /**
     *
     * @param unknown $type_id
     * @param number $size
     * @param string $type (icon/render/bpc/bp)
     * @return string
     */
    public static function getEvEImageUrl($type_id, $size=32, $typeArg='icon') : string
    {
        switch($size)
        {
            case 64: $ssize = 64; break;
            case 32: $ssize = 32; break;
            case 128: $ssize = 128; break;
            case 256: $ssize = 256; break;
            case 512: $ssize = 512; break;
            case 1024: $ssize = 1024; break;
            default: $ssize = 64;
        }
        
        $client = new Client();
        
        try {
            $response = $client->request('GET', 'https://images.evetech.net/types/'.$type_id);
        } catch(RequestException $e) {
            return "https://industry.embin.ch/images/48px-Memberdelay.png";
        }
        
        $result = json_decode($response->getBody()->getContents());
        if(is_array($result))
        {
            foreach($result as $arrR)
            {
                if($arrR  == 'icon') {
                    $type = 'icon';
                }
                else if($arrR == 'bp') {
                    $type = 'bp';
                }
                else if($arrR == 'bpc')
                    $type = 'bpc';
                    else {
                        $type = 'icon';
                    }
            }
        }
        
        $sizeUrl = '?size='.intval($ssize);
        $url = 'https://images.evetech.net/types/'.$type_id.'/'.$type.''.$sizeUrl; ///'.$type.'/'.$sizeUrl;
        
        return $url;
    }
    
    public static function getEvEImageRenderUrl($type_id)
    {
        return 'https://images.evetech.net/types/'.$type_id.'/render';
    }
    
    
    public static function getCharacterImageUrl($character_id, $size)
    {
        switch($size)
        {
            case 64: $ssize = 64; break;
            case 32: $ssize = 32; break;
            case 128: $ssize = 128; break;
            case 256: $ssize = 256; break;
            case 512: $ssize = 512; break;
            case 1024: $ssize = 1024; break;
            default: $ssize = 64;
        }
        
        $url = "https://image.eveonline.com/Character/".$character_id."_".$ssize.".jpg";
        return $url;
    }
    
}

?>