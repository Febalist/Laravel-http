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

if (!function_exists('runscope')) {
    function runscope($url)
    {
        $debug = config('app.debug', false);
        $config = config('http.runscope', []);

        if ($config['enabled'] === false || ($config['enabled'] === null && !$debug)) {
            return $url;
        }

        $parts = parse_url($url);
        $host = str_replace(['-', '.'], ['~', '-'], $parts['host']);
        $host = str_replace(
            '~',
            '--',
            sprintf('%s-%s.%s', $host, $config['bucket'], $config['gatetway'])
        );

        if (isset($parts['user']) || isset($parts['pass'])) {
            $host = sprintf(
                '%s:%s@%s',
                $parts['user'],
                $parts['pass'],
                $host
            );
        }

        $url = \Febalist\LaravelHttp\Request::http_build_url(null,
            [
                'scheme'   => $parts['scheme'],
                'host'     => $host,
                'path'     => isset($parts['path']) ? $parts['path'] : '/',
                'query'    => isset($parts['query']) ? $parts['query'] : null,
                'fragment' => isset($parts['fragment']) ? $parts['fragment'] : null,
                'port'     => isset($parts['port']) ? $parts['port'] : null,
            ]);

        return $url;
    }
}
