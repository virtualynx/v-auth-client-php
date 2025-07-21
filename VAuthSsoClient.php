<?php

namespace VLynx\Sso;

require_once "HttpUtil.php";
require_once "HttpClient.php";

/**
 * For PHP >= 5.4.0
 */
class VAuthSsoClient {
    private $server_url;
    private $client_id;
    private $client_secret;
    private $http_client;
    private $server_url_local;

    private const ACCESS_TOKEN_NAME = 'vauthat';
    private const REFRESH_TOKEN_NAME = 'vauthrt';
    private const SILENT_LOGOUT_REASONS = [
        'Missing access and refresh token',
        'Missing access_token',
        'Missing refresh_token',
        'Expired session'
    ];
    // private const MSG_SESSION_EXPIRED = 'Your session is expired, please login again !';
    private const MSG_SESSION_EXPIRED = 'Waktu sesi telah berakhir, silahkan login kembali !';

    function __construct(
        $server_url, 
        $client_id, 
        $client_secret,
        $server_url_local = null
    ){
        if(
            empty($server_url) ||
            empty($client_id) ||
            empty($client_secret)
        ){
            throw new \Exception("server_url, client_id, and client_secret cannot be null");
        }

        $this->server_url = rtrim($server_url, '/');
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->http_client = new HttpClient();
        $this->server_url_local = $server_url_local;
    }

    function LoginPage($params = null){
        $verifier = self::GenerateRandomString(64);
        setcookie('pkce_verifier', $verifier, time() + (60*60*24 * 3), '/', HttpUtil::GetDomain(), false, true);

        $challenge = base64_encode(hash('sha256', $verifier));
        
        if(empty($params)){
            $params = [];
        }
        $params['client_id'] = $this->client_id;
        $params['response_type'] = 'code';
        $params['challenge'] = $challenge;
        $params['challenge_method'] = 's256';
        
        $url_query = http_build_query($params);
        $login_url = "$this->server_url?$url_query";

        header("Location: $login_url");
        exit();
    }

    /**
     * Call this on your callback endpoint
     */
    function SsoCallbackHandler(){
        if(empty($_GET['action'])){
            throw new \Exception('Missing "action"');
        }

        $action = $_GET['action'];

        $data = null;
        if(!empty($_GET['data'])){
            $data = json_decode(base64_decode($_GET['data']));
        }

        if($action == 'login'){
            return [
                'action' => 'login',
                'data' => self::_CallbackLogin($data)
            ];
        }else if($action == 'auth_code_response'){
            throw new \Exception('Yet to be implemented');
        }
        /*else if($action == 'token_info'){
            return [
                'action' => 'token_info',
                'data' => self::_CallbackTokenInfo($data)
            ];
        }*/
        else{
            throw new \Exception('Invalid action');
        }

        if(!empty($_GET['redirect'])){
            header("Location: ".$_GET['redirect']);
            exit();
        }
    }

    private function _CallbackLogin($data){
        if($data->login_method != 'google'){
            // if(!isset($_GET['code'])){
            //     throw new \Exception('Missing "code"');
            // }

            // $loginpageParams = [];
            // if(!empty($_GET['redirect'])){
            //     $loginpageParams['redirect'] = $_GET['redirect'];
            // }

            // if(!isset($_COOKIE['pkce_verifier'])){
            //     $loginpageParams['alert'] = 'You left your login-page open for a long period of time. Please try logging in again !';
            //     $this->LoginPage($loginpageParams);
            // }

            // try{
            //     $pkce_verifier = $_COOKIE['pkce_verifier'];
            //     setcookie('pkce_verifier', '', time() - 1, '/', self::GetDomain(), false, true);

            //     $resp = $this->http_client->post(
            //         $this->server_url.'/token', 
            //         [
            //             'grant_type' => 'authorization_code',
            //             'code' => $_GET['code'],
            //             'verifier' => $pkce_verifier
            //         ]
            //     );
            //     if($resp){
            //         $json = json_decode($resp);
            //         if($json->status != 'success'){
            //             throw new \Exception($json->status, 500);
            //         }
            //         self::SaveTokens($json->token_data);

            //         return $json->user;
            //     }

            //     throw new \Exception('Empty response from Code-Exchange API', 500);
            // }catch(\Exception $e){
            //     if($e->getCode() == 401 && $e->getMessage() == 'PKCE challenge failed'){
            //         $loginpageParams['alert'] = 'Login failed, make sure not to open multiple SSO-Login Page at once !';
            //         $this->LoginPage($loginpageParams);
            //     }

            //     throw $e;
            // }
            
            throw new \Exception('Yet to be implemented');
        }

        save_userinfo($data->user);

        // $this->SetToken($data->tokens);
        self::SaveTokens($data->tokens);

        return $data->user;
    }

    /**
     * if both client and server is on the same instance (server)
     * laravel's connection from the calling apps carry over to the SSO server thus -
     * causing unknown table name since its using the calling-app's database connection
     */
    function AuthCheck($token_type = 'access', $redirect_login_upon_fail = true){
        try{
            if(!in_array($token_type, ['access', 'refresh'])){
                throw new \Exception("Invalid token_type: $token_type");
            }

            $token = self::GetToken($token_type);
            $params = [];
            if($token_type == 'refresh'){
                $params['refresh'] = 'true';
            }

            $server_url = $this->GetServerUrl();
            $http = new HttpClient();
            $response = $http->get(
            // $response = $http->post(
                "$server_url/token/info", 
                $params, 
                [
                    'Cache-Control: no-cache, no-store',
                    'Authorization: Bearer '.$token,
                    'Client-id: '.$this->client_id
                ]
            );

            $response = json_decode($response, true);
            
            if($token_type == 'refresh'){
                $tokens = $response['data']['tokens'];
                self::SaveTokens($tokens);
            }

            return true;
        }catch(\Exception $e){
            if($e->getCode() == 401){
                $is_valid = false;

                if($e->getMessage() == 'Expired token'){ //token is expired
                    if($token_type == 'access'){
                        $is_valid = $this->AuthCheck('refresh', $redirect_login_upon_fail);
                    }
                }

                if(!$is_valid){
                    $this->RevokeTokens();

                    if($redirect_login_upon_fail){
                        $alert = '';
                        if(
                            ($token_type == 'refresh' && $e->getMessage() == 'Expired token') ||
                            in_array($e->getMessage(), self::SILENT_LOGOUT_REASONS)
                        ){
                            $alert = self::MSG_SESSION_EXPIRED;
                        }else{
                            $alert = $e->getMessage();
                        }

                        $loginParams = [
                            'alert' => $alert,
                            'redirect' => HttpUtil::GetCurrentUrl()
                        ];

                        $this->LoginPage($loginParams);
                    }
                }

                return $is_valid;
            }else{
                throw $e;
            }
        }
    }

    public function UserInfo(){
        $server_url = $this->GetServerUrl();
        $http = new HttpClient();
        $response = $http->get(
            "$server_url/user/info", 
            null, 
            [
                'Cache-Control: no-cache, no-store',
                'Authorization: Bearer '.self::GetToken('refresh'),
                'Client-id: '.$this->client_id
            ]
        );

        $response = json_decode($response, true);

        return $response['data'];
    }

    public function RevokeTokens(){
        $domain = HttpUtil::GetDomain();
        setcookie(self::ACCESS_TOKEN_NAME, '', time()-1, '/', $domain, false, true);
        setcookie(self::REFRESH_TOKEN_NAME, '', time()-1, '/', $domain, false, true);
    }

    public function Logout($redirectLoginPage = true){
        $token = null;

        try{
            $token = self::GetToken('access_token');
        }catch(\Exception $e){}
        
        if(empty($token)){
            try{
                $token = self::GetToken('refresh_token');
            }catch(\Exception $e){}
        }
        $this->RevokeTokens();

        if(!empty($token)){
            $resp = $this->http_client->post(
                $this->server_url.'/logout', 
                [], 
                ["Authorization: Bearer $token"]
            );
            if($resp){
                $json = json_decode($resp);
                if($json->status != 'success'){
                    throw new \Exception("SLO failed: $resp");
                }
            }else{
                throw new \Exception('Empty response from Logout API', 500);
            }
        }

        return $this->LoginPage(['alert' => 'You have been logged-out'], $redirectLoginPage);
    }

    private static function GetToken($name){
        $token_keymap = [
            'access' => self::ACCESS_TOKEN_NAME,
            'refresh' => self::REFRESH_TOKEN_NAME
        ];
        
        if(empty($_COOKIE[self::ACCESS_TOKEN_NAME]) && empty($_COOKIE[self::REFRESH_TOKEN_NAME])) {
            throw new \Exception('Missing access and refresh token', 401);
        }

        $tag = $token_keymap[$name];
        if(empty($_COOKIE[$tag])) {
            if($name == 'access' && !empty($_COOKIE[self::REFRESH_TOKEN_NAME])){
                throw new \Exception('Expired token', 401);
            }
            throw new \Exception("Missing $name", 401);
        }

        return $_COOKIE[$tag];
    }

    private static function SaveTokens($data){
        if(is_array($data)){
            $data = json_decode(json_encode($data));
        }

        $domain = HttpUtil::GetDomain();

        $expires = time() + 60 * 60 * 12;
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($expires);
        $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $expiresText = $dateTime->format('D, d M Y H:i:s e');

        $header_suffixes = "expires=$expiresText; path=/; domain=$domain; SameSite=Lax; httponly;";

        $access_token_name = self::ACCESS_TOKEN_NAME;
        header("Set-Cookie: $access_token_name=$data->access_token; $header_suffixes");

        if(!empty($data->refresh_token)){
            $refresh_token_name = self::REFRESH_TOKEN_NAME;
            header("Set-Cookie: $refresh_token_name=$data->refresh_token; $header_suffixes", false);
        }
    }
    
	private static function GenerateRandomString($length){
		return bin2hex(random_bytes(($length-($length%2))/2));
	}
    
    private function GetServerUrl(){
        return !empty($this->server_url_local)? $this->server_url_local: $this->server_url;
    }

    // private function _CallbackTokenInfo($data){
    //     if($data->status != 'success'){

    //     }

    //     if(!empty($data->message)){
    //         if($data->message == 'Expired token'){
    //             $this->AuthCheck('refresh');
    //         }else{
    //             $this->RevokeTokens();

    //             $loginParams = ['alert' => $data->message];
    //             if(!empty($_SERVER['HTTP_REFERER'])){
    //                 $loginParams['redirect'] = $_SERVER['HTTP_REFERER'];
    //             }
    //             $this->LoginPage($loginParams);
    //         }
    //     }

    //     if(!empty($data->tokens)){
    //         $this->SetToken($data->tokens);
    //     }

    //     return $data->payload;
    // }

    // private function SetToken($data){
    //     setcookie(self::ACCESS_TOKEN_NAME, $data->access_token, time() + (60*60*1), '/', HttpUtil::GetDomain(), false, true);

    //     if(!empty($data->refresh_token)){
    //         setcookie(self::REFRESH_TOKEN_NAME, $data->refresh_token, time() + (60*60*24 * 30), '/', HttpUtil::GetDomain(), false, true);
    //     }
    // }

    /**
     * Call this to check the validity of the tokens and SSO's shared-session
     */
    // function AuthCheck_redirect($token_type = 'access'){
    //     if(!in_array($token_type, ['access', 'refresh'])){
    //         throw new \Exception("Invalid token_type: $token_type");
    //     }

    //     $token = self::GetToken($token_type);

    //     $params = [
    //         'token' => $token,
    //         'client_id' => $this->client_id
    //     ];
    //     if($token_type == 'access'){
    //         $params['redirect'] = HttpUtil::GetCurrentUrl();
    //     }
    //     if($token_type == 'refresh'){
    //         $params['refresh'] = 'true';
    //         $params['redirect'] = $_GET['redirect'];
    //     }

    //     // $redirect_url = "$this->server_url/token/info?".(http_build_query($params));

    //     // header("Location: $redirect_url");
    //     // exit();

    //     $http = new HttpClient();
    //     $http->redirect("$this->server_url/token/info", $params);
    // }
}