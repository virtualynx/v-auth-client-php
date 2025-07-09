<?php

namespace VLynx\Sso;

class HttpUtil {
    function __construct(){}

    public static function GetDomain() {
        // $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $host = preg_replace('/:\d+$/', '', $host); // Remove port
        
        // Handle localhost and IP addresses
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }
        
        // Handle special cases like .co.uk, .com.br, etc.
        $specialTlds = [
            'co.uk', 'com.br', 'co.jp', 'co.in', 'co.za', 
            'org.uk', 'net.br', 'gov.br', 'ac.uk', 'test',
            'localhost.local'
        ];
        
        // Check for special TLDs first
        foreach ($specialTlds as $tld) {
            if (preg_match("/\.{$tld}$/", $host)) {
                // For special TLDs, we need three parts (e.g., "bbc.co.uk")
                $parts = explode('.', $host);
                if (count($parts) >= 3) {
                    return implode('.', array_slice($parts, -3));
                }
            }
        }
        
        // Remove www. and other common subdomains
        $host = preg_replace('/^(www|app|api|m)\./', '', $host);
        
        // Standard domain processing (get last two parts)
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            return implode('.', array_slice($parts, -2));
        }
        
        return '.'.$host; // fallback
    }

    public static function GetCurrentUrl($strip_params = false){
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $currentUrl = "$scheme://$host$uri";

        if($strip_params){
            $url_param_array = explode('?', $currentUrl);
            $currentUrl = $url_param_array[0];
        }

        return $currentUrl;
    }

    public static function getDomainAndPort(string $url) {
        $components = parse_url($url);
        
        if ($components === false) {
            throw new \InvalidArgumentException("Invalid URL provided");
        }
        
        // Get domain (host)
        $domain = $components['host'] ?? '';
        if (empty($domain)) {
            throw new \InvalidArgumentException("URL must contain a hostname");
        }
        
        // Get port (default to 80 for http, 443 for https)
        $port = $components['port'] ?? 
                (($components['scheme'] ?? 'https') === 'http' ? 80 : 443);
        
        // return [
        //     'domain' => $domain,
        //     'port' => $port
        // ];

        return "$domain:$port";
    }
}