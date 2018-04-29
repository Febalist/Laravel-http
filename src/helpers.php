<?php

if (!function_exists('http')) {
    /** @return \Febalist\LaravelHttp\Request */
    function http($url, $options = [])
    {
        return new \Febalist\LaravelHttp\Request($url, $options);
    }
}

if (!function_exists('http_get')) {
    /** @return \Febalist\LaravelHttp\Response */
    function http_get($url, $query = [], $options = [])
    {
        return http($url, $options)->get($query);
    }
}

if (!function_exists('throttler')) {
    /** @return \Febalist\LaravelHttp\Throttler */
    function throttler($id, $limit = 1, $timeout = null)
    {
        return new \Febalist\LaravelHttp\Throttler($id, $limit, $timeout);
    }
}

if (!function_exists('throttle')) {
    function throttle($id, $limit = 1, $timeout = null)
    {
        throttler($id, $limit, $timeout)->throttle();
    }
}

if (!function_exists('http_post')) {
    /** @return \Febalist\LaravelHttp\Response */
    function http_post($url, $body = [], $options = [])
    {
        return http($url, $options)->post($body);
    }
}
