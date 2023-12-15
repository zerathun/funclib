<?php 
namespace Funclib;

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
}

?>