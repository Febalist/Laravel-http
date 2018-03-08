<?php

namespace Febalist\LaravelHttp;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response
{
    protected $response;

    public function __construct(GuzzleResponse $response)
    {
        $this->response = $response;
    }

    public function body()
    {
        return (string) $this->response->getBody();
    }

    public function json()
    {
        try {
            return json_decode($this->body(), true);
        } catch (\Exception $exception) {
            return;
        }
    }

    public function header($header, $array = false)
    {
        return $array ? $this->response->getHeader($header) : $this->response->getHeaderLine($header);
    }

    public function headers()
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v[0]];
        })->all();
    }

    public function cookies()
    {
        $cookies = [];

        foreach ($this->header('Set-Cookie', true) as $part) {
            $cookie = explode('; ', $part)[0];
            $cookie = explode('=', $cookie);
            $key = urldecode($cookie[0]);
            $value = urldecode($cookie[1]);
            $cookies[$key] = $value;
        }

        return $cookies;
    }

    public function cookie($key = null)
    {
        if ($key) {
            return array_get($this->cookies(), $key);
        }

        $cookies = [];
        foreach ($this->cookies() as $key => $value) {
            $cookies[] = urlencode($key).'='.urlencode($value);
        }

        return implode('; ', $cookies);
    }

    public function status()
    {
        return $this->response->getStatusCode();
    }

    public function isSuccess()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function isOk()
    {
        return $this->isSuccess();
    }

    public function isRedirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    public function isClientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function isServerError()
    {
        return $this->status() >= 500;
    }

    public function __toString()
    {
        return $this->body();
    }
}
