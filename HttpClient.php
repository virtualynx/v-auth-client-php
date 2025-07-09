<?php

namespace VLynx\Sso;

require_once "HttpUtil.php";

class HttpClient {
    private $dns_resolve = null;

    function __construct($dns_resolve = null){
        $this->dns_resolve = $dns_resolve;
    }

    public function get($url, $params = null, $headers = []){
        if(!empty($params)){
            // Check if URL already has query parameters
            $separator = (parse_url($url, PHP_URL_QUERY) == null) ? '?' : '&';
            $url .= $separator . http_build_query($params);
        }

        $curl = $this->init_curl($url, $headers);
    
        // Explicitly set as GET request (though this is the default)
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        
        $response = $this->run_curl($curl);
        
        return $response;
    }

    public function post($url, $params, $headers = []){
        $curl = $this->init_curl($url, $headers);
        
        curl_setopt($curl, CURLOPT_POST, true);

        if(!empty($params)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }
        
        $response = $this->run_curl($curl);

        return $response;
    }

    private function init_curl($url, $headers = []){
        $curl = curl_init($url);
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        if(!empty($headers)){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        // if(!empty($this->dns_resolve)){
        //     $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        //     curl_setopt($curl, CURLOPT_RESOLVE, ["$host:$this->dns_resolve"]);

        //     // $domainAndPort = HttpUtil::getDomainAndPort($host);
        //     // curl_setopt($curl, CURLOPT_RESOLVE, ["$domainAndPort:$this->dns_resolve"]);
        // }

        return $curl;
    }

    private function run_curl($curl){
        $response = curl_exec($curl);
        $http_resp_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        
        $error_no = '';
        $error_msg = '';
        if(($error_no = curl_errno($curl))) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);

        if($http_resp_code >= 400){
            $msg = !empty($response)? $response: $error_msg;
            throw new \Exception($msg, $http_resp_code);
        }else if($error_no != 0){
            throw new \Exception($error_msg, $error_no);
        }

        return $response;
    }

    public function redirect($url, $params){
        if(!empty($params)){
            // Check if URL already has query parameters
            $separator = (parse_url($url, PHP_URL_QUERY) == null) ? '?' : '&';
            $url .= $separator . http_build_query($params);
        }

        header("Location: $url");
        exit();
    }
}