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

    protected function request_get($url, $params = [], $headers = [], $options = [])
    {
        return $this->request('get', $url, $params, $headers, $options);
    }

    protected function request_post($url, $params = [], $headers = [], $options = [])
    {
        return $this->request('post', $url, $params, $headers, $options);
    }
}
