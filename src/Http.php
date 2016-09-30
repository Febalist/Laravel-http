<?php
namespace Febalist\LaravelHttp;

use Cache;
use Exception;
use GuzzleHttp\Client;
use Log;

class Http
{
    protected $client;
    protected $options = [
        'exceptions'     => false,
        'timeout'        => 0,
        'rate_limit'     => 0,
        'retry_delay'    => 0,
        'retry_attempts' => 0,
    ];

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->client  = new Client();
    }

    public function get($uri, $params = [], $headers = [], $options = [])
    {
        return $this->request($uri, $params, 'GET', $headers, $options);
    }

    public function post($uri, $params = [], $headers = [], $options = [])
    {
        return $this->request($uri, $params, 'POST', $headers, $options);
    }

    public function request($uri, $params = [], $method = 'GET', $headers = [], $options = [])
    {
        $option_body = strtoupper($method) == 'POST' ? 'form_params' : 'query';
        $options     = array_merge($this->options, $options, [
            'headers' => $headers,
            $body     => $params,
        ]);

        Log::debug("HTTP $method $uri", compact('headers', 'params'));

        $error = null;
        for ($attempt = 1; $attempt <= $options['retry_attempts'] + 1; $attempt++) {
            if ($attempt > 1) {
                Log::warning("HTTP retry $method $url");
                $this->delay($options['retry_delay']);
            }
            $this->rate_delay($options['rate_limit']);

            $time = microtime(true);
            try {
                $response = $this->client->request($method, $url, $options);
            } catch (ConnectException $e) {
                $response = null;
                $error    = $e->getMessage();
            } catch (ErrorException $e) {
                $response = null;
                $error    = $e->getMessage();
                if (!str_contains('cURL error', $error)) {
                    throw $e;
                }
            }
            $time = round((microtime(true) - $time) * 1000, 2);

            if (!$response) {
                continue;
            }

            $status = $response->getStatusCode();
            if ($status == 429 || $status >= 502) {
                $error = "HTTP error $status";
                continue;
            }

            $headers = $response->getHeaders();
            $content = $response->getBody()->getContents();
            if (str_contains(array_get($headers, 'Content-Type.0'), 'application/json')) {
                $content = json_parse($content);
            }

            Log::debug("HTTP $time ms", compact('status', 'headers', 'content'));

            return [$content, $status, $headers];
        }
        throw new Exception($error);
    }

    public function delay($time)
    {
        if ($time > 0) {
            usleep($time * 1000000);
        }
    }

    public function rate($limit = 1, $key = '')
    {
        if ($limit > 0) {
            $class    = get_class($this);
            $key      = "api.last.$class.$key";
            $interval = 1 / $limit + 0.001;
            $now      = microtime(true);
            $last     = Cache::get($key, 0);
            $passed   = $now - $last;
            $wait     = $interval - $passed;
            if ($wait < 0) {
                $wait = 0;
            }
            $remember = $interval + $wait;
            Cache::put($key, $now + $wait, ceil($remember / 60));
            $this->delay($wait);
        }
    }
}
