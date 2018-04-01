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
        return new \Febalist\LaravelHttp\Throttler(...func_get_args());
    }
}

if (!function_exists('throttle')) {
    function throttle($id, $limit = 1, $timeout = null)
    {
        throttler(...func_get_args())->throttle();
    }
}

if (!function_exists('http_post')) {
    /** @return \Febalist\LaravelHttp\Response */
    function http_post($url, $body = [], $options = [])
    {
        return http($url, $options)->post($body);
    }
}

if (!function_exists('runscope')) {
    function runscope($url)
    {
        return \Febalist\LaravelHttp\Request::runscope($url);
    }
}

if (!function_exists('str_limit_hard')) {
    function str_limit_hard($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        $limit -= mb_strwidth($end, 'UTF-8');
        if ($limit < 0) {
            return '';
        }

        return str_limit($value, $limit, $end);
    }
}
