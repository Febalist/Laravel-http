<?php

if (!function_exists('http')) {
    function http($options = [])
    {
        return new Febalist\LaravelHttp\Http($options);
    }
}

if (!function_exists('http_get')) {
    function http_get($url, $params = [], $headers = [], $options = [])
    {
        return http()->get($url, $params, $headers, $options);
    }
}

if (!function_exists('http_post')) {
    function http_post($url, $params = [], $headers = [], $options = [])
    {
        return http()->post($url, $params, $headers, $options);
    }
}
