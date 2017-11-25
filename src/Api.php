<?php

namespace Febalist\LaravelHttp;

abstract class Api
{
    protected $throttler;

    public function __construct()
    {
    }

    public function throttler($id, $limit = 1, $timeout = null)
    {
        $this->throttler = new Throttler($id, $limit, $timeout);
    }

    public function throttle()
    {
        if ($this->throttler) {
            $this->throttler->throttle();
        }
    }

    protected function request($method, $url, $params = [], $headers = [], $options = [])
    {
        $this->throttle();
        $request = new Request($url, $options);
        $request->headers($headers);

        return $request->send($method, $params);
    }
}
