<?php

/**
 * Designed to works with laravel
 */

use VLynx\Sso\VAuthSsoClient;

if(!defined('V_AUTH_SESSION_USERINFO')){
    define('V_AUTH_SESSION_USERINFO', 'v_auth_userinfo');
}

if (!function_exists('save_userinfo')) {
    function save_userinfo($data) {
        return session()->put(V_AUTH_SESSION_USERINFO, $data);
    }
}else{
    throw new Exception('save_userinfo exists');
}

if (!function_exists('userinfo')) {
    function userinfo() {
        $userinfo = session()->get(V_AUTH_SESSION_USERINFO);

        if(empty($userinfo)){
            $server_url = config('app.sso.server_url');
            $client_id = config('app.sso.client_id');
            $client_secret = config('app.sso.client_secret');
            $server_url_local = config('app.sso.server_url_local');

            $sso = new VAuthSsoClient($server_url, $client_id, $client_secret, $server_url_local);
            $userinfo = $sso->UserInfo();
        }
        
        return $userinfo;
    }
}else{
    throw new Exception('userinfo exists');
}

if (!function_exists('is_loggedin')) {
    function is_loggedin() {
        $userinfo = userinfo();

        return !empty($userinfo);
    }
}else{
    throw new Exception('is_loggedin exists');
}

if (!function_exists('has_role')) {
    function has_role(string $input) {
        if(!is_loggedin()){
            return false;
        }

        $userinfo = userinfo();

        return in_array($input, $userinfo->roles);
    }
}else{
    throw new Exception('has_role exists');
}
