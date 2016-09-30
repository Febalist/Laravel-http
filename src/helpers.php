<?php

if (!function_exists('http')) {
    function http($options = [])
    {
        return new Febalist\LaravelHttp\Http($options);
    }
}

if (!function_exists('http_get')) {
    function http_get($uri, $params = [], $headers = [], $options = [])
    {
        return http()->get($uri, $params, $headers, $options);
    }
}

if (!function_exists('http_post')) {
    function http_post($uri, $params = [], $headers = [], $options = [])
    {
        return http()->post($uri, $params, $headers, $options);
    }
}
