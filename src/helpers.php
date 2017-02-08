<?php

if (!function_exists('http')) {
    function http($options = [])
    {
        return new Febalist\LaravelHttp\Http($options);
    }
}

if (!function_exists('http_get')) {
    function http_get($uri, $params = [], $options = [])
    {
        return http()->get($uri, $params, $options);
    }
}

if (!function_exists('http_post')) {
    function http_post($uri, $params = [], $options = [])
    {
        return http()->post($uri, $params, $options);
    }
}

if (!function_exists('http_code_message')) {
    function http_code_message($code)
    {
        return \Febalist\LaravelHttp\Http::code_message($code);
    }
}
