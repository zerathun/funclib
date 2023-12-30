<?php
namespace Funclib;

use Database\Database;
use Funclib\LinkMng;
use GuzzleHttp\Client;
use Funclib\sessionHandler;

/**
 * This class/object ensures the correct authentification with the 0AuthServer
 */
class AuthClient {
    
    private $private_key = 'SET PRIVATE KEY GIVEN FROM EMBER AUTHORITY';
    private $appid = '999';
    private $user_id = 0;
    private $authServer = 'https://auth.embin.ch?auth=login';
    private $isAuthenticated = false;
    private $currentToken;
    private $sessionName = "random_session_name";
    private $refrTokenSession = "rnd_tk_sess";
    private $userIdSessName;
    
    public function __construct() {
        
    }
    
    public function SetPrivateAppKey(string $string)
    {
        $this->private_key = $string;
    }
    
    public function SetAppId(int $id)
    {
        $this->appid = $id;
    }
    
    public function SetSessionName(string $session_name)
    {
        $this->sessionName = $session_name;
    }

    
    /**
     * this method is called once; when there is getvar cookie or session with a token it is once validated
     * the token is set to the object if the token is valid other wise it is set false;
     */
    public function checkAuthentification() {
        twigVariables::gI()->setVariable('login_url', "?getauth=1");
        /**
         * If a Token is set on GET Variables (Response from the 0Auth2 Server then this token is taken and validated
         */
        if(isset($_GET['token']) && strlen($_GET['token']) > 0 && !empty($_GET['refresh_token'])) {
            $this->renewSession($_GET['token'], $_GET['refresh_token'], $_GET['uid']);
            // Redirect to forget $_GET Token
            $url = LinkMng::getUrl(array('token' => '', 'refresh_token' => '', 'uid' => ''));
            header('Location: '.$url);
        } elseif(sessionHandler::getInstance()->getSession($this->sessionName) != null) {
            // Use Session
            $token = sessionHandler::getInstance()->getSession($this->sessionName);
            $rToken = sessionHandler::getInstance()->getSession($this->refrTokenSession);
            $uid = sessionHandler::getInstance()->getSession($this->userIdSessName);
            $this->renewSession($token, $rToken, $uid);
        } elseif(!empty(sessionHandler::getInstance()->getCookie($this->sessionName))) {
            // Check if there is a cookie when the user opens the site
            $token = sessionHandler::getInstance()->getCookie($this->sessionName);
            $rToken = sessionHandler::getInstance()->getCookie($this->refrTokenSession);
            $uid = sessionHandler::getInstance()->getCookie($this->userIdSessName);
            
            $this->renewSession($token, $rToken, $uid);
        } else {
            // There is no variable with a token available
            // Therefore the user is not logged in
            $this->currentToken = false;
        }
        // If the user is not yet authenticated and wants to get authenticated redirect to the Auth-Page
        if(isset($_GET['getauth']) && !$this->isAuthenticated()) {
            $this->redirectIfNotLogin();
        }
    }
    
    public function renewSession($token, $refresh_token, int $user_id) {
        sessionHandler::getInstance()->setSession($this->sessionName, $token);
        sessionHandler::getInstance()->setCookie($this->sessionName, $token);
        
        sessionHandler::getInstance()->setSession($this->refrTokenSession, $refresh_token);
        sessionHandler::getInstance()->setCookie($this->refrTokenSession, $refresh_token);
        
        sessionHandler::getInstance()->setSession($this->userIdSessName, $user_id);
        sessionHandler::getInstance()->setCookie($this->userIdSessName, $user_id);
        
        $this->currentToken = $token;
        $this->refreshToken = $refresh_token;
        $this->user_id = $user_id;
        
        return true;
    }
    
    /**
     * forget the token saved -> this means logout basicly
     */
    public function forgetToken($redirect = false) {
        $this->renewSession(false, false, 0);
        
        if($redirect)
        {
            $url = LinkMng::getUrl(array('token' => '', 'refresh_token' => '', 'uid' => ''));
            header('Location: '.$url);
        }
    }
    
    /**
     * Checks if the user is authenticated (logged in), if so returned true
     * It is assumed (for performance issues) when there is a token it is valid
     */
    public function isAuthenticated() : bool {
        if(empty($this->currentToken) || $this->currentToken === false)
            return false;
            else return true;
            return null;
    }
    
    /**
     * Checks if the user is authenticated and returns the Auth-User Id (Unique)
     * 
     * @return int
     */
    public function GetUserId() : int {
        if($this->isAuthenticated())
        {
            $uid = sessionHandler::getInstance()->getSession($this->userIdSessName);
            if(empty($uid))
                $uid = sessionHandler::getInstance()->getCookie($this->userIdSessName);
                return $uid;
        }
        else
        {
            return 0;
        }
    }
    
    
    /**
     * If the user is correctly authenticated data can be fetched from the AUTH Server
     */
    public function getAuthData($type) {
        if($this->isAuthenticated()) {
            $client = new Client();
            
            if($this->validateToken($this->currentToken))
            {
                $multipart = [
                    [
                        'name' =>  'token',
                        'contents' => $this->currentToken
                    ],
                    [
                        'name' =>  'data_type',
                        'contents' => $type
                    ],
                    [
                        'name' =>  'uid',
                        'contents' => $this->user_id,
                    ],
                    [
                        'name' =>  'appid',
                        'contents' => $this->appid
                    ]];
                
                
                $response = $client->request('POST', 'https://auth.embin.ch?auth=data',
                    [ 'multipart' => $multipart ]
                 );
                
                $result = json_decode($response->getBody()->getContents());
                return $result;
            } else 
            {
                
            }
        } else {
            return false;
        }
    }
    
    /**
     * this method validates the token with the server
     * if the token is outdated the method returns false
     *
     * @result bool
     */
    private function validateToken($token) {
        $client = new Client(); // GuzzleHttp Client for a HTML Response of the Server
        $url = 'https://auth.embin.ch?auth=user&appid='.$this->appid."&token=".$token."&uid=".$this->user_id;

        $res = $client->request('GET', $url, [
            'auth' => ['user', 'pass']
            ]
        );
        
        // Status Code of the HTTP-Request is successful when 200
        // When correct HTTP Response has received, Check the Content if authentification data is correct
        if($res->getStatusCode() == 200) {
            $json = $res->getBody()->getContents();
            $result = json_decode($json);
          
            if($result->response->code == 700)
            {
                // Token is outdated and needs to be renewed;
                // Returns false if the Refresh-Token is invalid
                // Returns true if the Refresh-Token is valid and could be updated to a new one
                $boolResult = $this->renewTokenPubl();
                if($boolResult){
                    return true;
                }
                else {
                    $this->forgetToken(true);
                    return false;
                }
            } 
            else if($result->response->code == 200) // Ëquivalent with $result->response->auth == 'success'
            {
                // Token is valid and can be used to fetch data e.g.
                $this->currentToken = $result->response->token;
                return true;
            } else 
            {
                $this->forgetToken(true);
                return false;
            }
        }
        return false;
    }
    
    public function renewTokenPubl()
    {
        $rToken = sessionHandler::getInstance()->getCookie($this->refrTokenSession);
        if(empty($rToken))
        {
            $rToken = sessionHandler::getInstance()->getSession($this->refrTokenSession);
        }
        if(!empty($rToken))
        {
            return $this->renewTokenArg($rToken);
        }
        return false;
    }
    
    private function renewTokenArg($refresh_token)
    {
        $client = new Client(); // GuzzleHttp Client for a HTML Response of the Server
        $url = 'https://auth.embin.ch?auth=user&appid='.$this->appid."&refresh_token=".$refresh_token."&uid=".$this->user_id;

        $res = $client->request('GET', $url, 
            [
            'auth' => ['user', 'pass']
                ]
            );
        
        if($res->getStatusCode() == 200) {
            $json = $res->getBody()->getContents();

            $result = json_decode($json);
            
            if($result->response->code == 300)
            {
                $this->renewSession($result->response->token, $result->response->refresh_token, $result->response->uid);
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
    
    private function getRedirectUrl() {
        
    }
    
    public function redirectIfNotLogin() {

        // Check if database of AuthSystem-Client is setup properly - otherwise install it
        $sql = 'CREATE TABLE IF NOT EXISTS `authapp_client` (
          `request_id` INT(15) NOT NULL,
          `request_time` INT(15) NULL,
          `app_id` INT(15) NULL,
          `server_response` INT(15) NULL,
          `user_sess_id` VARCHAR(125) NULL,
          `auth_token` VARCHAR(255) NULL,
          PRIMARY KEY (`request_id`));';
        Database::getInstance()->sql_query($sql);
        
        // Generate Request UniqueID
        // First -> ping the server for a request that will be coming and reserve the ID
        // Create an unique ID in the database and send a reserve request
        $flag = true;
        do {
            $req_id = rand(1,999999999);
            $sql = "SELECT count(authapp_client.request_id) as numbr, authapp_client.* from `authapp_client` WHERE request_id = $req_id";
            $res = Database::getInstance()->sql_query($sql);
            $row = Database::getInstance()->sql_fetch_array($res);
            if($row['numbr'] <= 0 || (int) $request_time < 50) {
                $flag = false;
                $sql1 = "INSERT INTO `authapp_client` (request_id, request_time, user_sess_id, app_id) VALUES ('$req_id', ".time().", '".session_id()."', $this->appid); DELETE FROM `authapp_client` WHERE request_time < ".(time()-600).";";
                Database::getInstance()->sql_query($sql1);
            }
        } while($flag);
        
        // Send the request to the server -> if request is successfully complete -> redirect the user
        $client = new Client();
        $response = $client->request('POST', 'https://auth.embin.ch?auth=prepare_request', [
            'multipart' => [
                [
                    'name' =>  'request_id',
                    'contents' => $req_id
                ],
                [
                    'name' =>  'private_key',
                    'contents' => $this->private_key
                ],
                [
                    'name' =>  'app_id',
                    'contents' => $this->appid
                ]
            ]
        ]
            );
        $server_handshake = json_decode($response->getBody());
        // Compare the sent handshake hash
        $hashstring = $this->private_key."_".$this->appid."_".$req_id; // This must be the same String as in the Client to be successful
        $resp_hash = hash('sha512', $hashstring);
        
        if($resp_hash == $server_handshake->response->response_verification && $server_handshake->response->valid) {
            // Now since the user has verified the client -> go to authenthicate the user
            $htmlentities = htmlentities(LinkMng::getUrl(array('getauth' => '')));
            $url = $this->authServer."&callback=".$htmlentities."&appid=".$this->appid."&req_id=".$req_id;
            
            
            header('Location: '.$url);
        } else {
            // OOPS THIS SHOULD NOT HAPPEN -> BUG/HACK
            print "OOPS THIS SHOULD NOT HAPPEN! Stop!";
        }
    }
}