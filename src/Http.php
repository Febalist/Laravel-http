<?php

namespace Febalist\LaravelHttp;

use Cache;
use Exception;
use GuzzleHttp\Client;
use Log;

class Http
{
    const CODE_MESSAGES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
    ];
    protected $client;
    protected $options = [
        'exceptions' => false,
        'timeout' => 0,
        'rate_limit' => 0,
        'retry_delay' => 0,
        'retry_attempts' => 0,
        'use_interfaces' => false,
    ];

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->client = new Client();
    }

    public function get($uri, $params = [], $headers = [], $options = [])
    {
        return $this->request($uri, $params, 'GET', $headers, $options);
    }

    public function request($uri, $params = [], $method = 'GET', $headers = [], $options = [])
    {
        $option_body = strtoupper($method) == 'POST' ? 'form_params' : 'query';
        $options = array_merge($this->options, $options, [
            'headers' => $headers,
            $option_body => $params,
        ]);

        Log::debug("HTTP $method $uri", compact('headers', 'params'));

        $rate_key = null;
        $error = null;
        for ($attempt = 1; $attempt <= $options['retry_attempts'] + 1; $attempt++) {
            if ($attempt > 1) {
                Log::warning("HTTP retry $method $uri");
                $this->delay($options['retry_delay']);
            }
            if ($options['use_interfaces']) {
                $interfaces = config('services.http.interfaces');
                $interfaces = explode(',', $interfaces);
                if ($interfaces) {
                    $key = 'http.last.interface.'.get_class($this);
                    $index = Cache::get($key, -1) + 1;
                    if ($index > count($interfaces)) {
                        $index = 0;
                    }
                    Cache::put($key, $index);
                    $rate_key = $index;
                    $options['curl'] = [
                        CURLOPT_INTERFACE => $interfaces[$index],
                    ];
                }
            }
            $this->rate($options['rate_limit'], $rate_key);

            $time = microtime(true);
            try {
                $response = $this->client->request($method, $uri, $options);
            } catch (ConnectException $e) {
                $response = null;
                $error = $e->getMessage();
            } catch (ErrorException $e) {
                $response = null;
                $error = $e->getMessage();
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
            $class = get_class($this);
            $key = "http.last.time.$class.$key";
            $interval = 1 / $limit + 0.001;
            $now = microtime(true);
            $last = Cache::get($key, 0);
            $passed = $now - $last;
            $wait = $interval - $passed;
            if ($wait < 0) {
                $wait = 0;
            }
            $remember = $interval + $wait;
            Cache::put($key, $now + $wait, ceil($remember / 60));
            $this->delay($wait);
        }
    }

    public function post($uri, $params = [], $headers = [], $options = [])
    {
        return $this->request($uri, $params, 'POST', $headers, $options);
    }
}
