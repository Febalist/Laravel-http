<?php

namespace Febalist\Laravel\Http;

abstract class Api
{
    protected $default_throttler;

    public function __construct()
    {
    }

    protected function throttle($id, $limit, $timeout = null)
    {
        $throttler = new Throttler(...func_get_args());
        $throttler->throttle();
    }

    protected function throttler_setup($id, $limit, $timeout = null)
    {
        $this->default_throttle = func_get_args();
    }

    protected function request($method, $url, $params = [], $headers = [], $options = [])
    {
        if (isset($this->default_throttle)) {
            $this->throttle(...$this->default_throttle);
        }
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
