<?php

if (!function_exists('http')) {
    /** @return \Febalist\Laravel\Http\Request */
    function http($url, $options = [])
    {
        return new \Febalist\Laravel\Http\Request($url, $options);
    }
}

if (!function_exists('http_get')) {
    /** @return \Febalist\Laravel\Http\Response */
    function http_get($url, $query = [], $options = [])
    {
        return http($url, $options)->get($query);
    }
}

if (!function_exists('http_post')) {
    /** @return \Febalist\Laravel\Http\Response */
    function http_post($url, $body = [], $options = [])
    {
        return http($url, $options)->post($body);
    }
}

if (!function_exists('http_post')) {
    /** @return \Febalist\Laravel\Http\Response */
    function http_post_json($url, $data = [], $options = [])
    {
        return http($url, $options)->json($data)->post();
    }
}

if (!function_exists('throttler')) {
    /** @return \Febalist\Laravel\Http\Throttler */
    function throttler($id, $limit = 1, $timeout = null)
    {
        return new \Febalist\Laravel\Http\Throttler($id, $limit, $timeout);
    }
}

if (!function_exists('throttle')) {
    function throttle($id, $limit = 1, $timeout = null)
    {
        throttler($id, $limit, $timeout)->throttle();
    }
}
