<?php

/**
 * Designed to works with laravel
 */

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
        return session()->get(V_AUTH_SESSION_USERINFO);
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
