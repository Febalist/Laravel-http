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

    function body()
    {
        return (string)$this->response->getBody();
    }

    public function json()
    {
        try {
            return json_decode($this->body(), true);
        } catch (\Exception $exception) {
            return null;
        }
    }

    function header($header)
    {
        return $this->response->getHeaderLine($header);
    }

    function headers()
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v[0]];
        })->all();
    }

    function status()
    {
        return $this->response->getStatusCode();
    }

    function isSuccess()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    function isOk()
    {
        return $this->isSuccess();
    }

    function isRedirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    function isClientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    function isServerError()
    {
        return $this->status() >= 500;
    }

    function __toString()
    {
        return $this->body();
    }
}
