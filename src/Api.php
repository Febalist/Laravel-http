<?php

namespace Febalist\LaravelHttp;

abstract class Api
{
    protected $throttler;

    public function __construct()
    {
    }

    public function throttle()
    {
        if ($this->throttler) {
            $this->throttler->throttle();
        }
    }

    protected function throttler_setup($id, $limit = 1, $timeout = null)
    {
        $this->throttler = new Throttler($id, $limit, $timeout);
    }

    protected function request($method, $url, $params = [], $headers = [], $options = [])
    {
        $this->throttle();
        $request = new Request($url, $options);
        $request->headers($headers);

        return $request->send($method, $params);
    }

    protected function get($url, $params = [], $headers = [], $options = [])
    {
        return $this->request('get', $url, $headers, $options);
    }

    protected function post($url, $params = [], $headers = [], $options = [])
    {
        return $this->request('post', $url, $headers, $options);
    }
}
