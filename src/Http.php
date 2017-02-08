<?php

namespace Febalist\LaravelHttp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class Http
{
    const CODES = [
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
        422 => 'Unprocessa ble Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        429 => 'Too Many Requests',
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
        'timeout'     => 0,
        'rate_limit'  => 0,
        'rate_key'    => null,
        'retry_times' => 1,
        'retry_sleep' => 0,
        'allow_empty' => false,
    ];

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->client = new Client();
    }

    public function code_message($code)
    {
        return array_get(static::CODES, $code);
    }

    public function get($uri, $params = [], $options = [])
    {
        return $this->load($uri, $params, 'GET', $options);
    }

    public function post($uri, $params = [], $options = [])
    {
        return $this->load($uri, $params, 'POST', $options);
    }

    public function load($uri, $params = [], $method = 'GET', $options = [])
    {
        $params_type = strtoupper($method) == 'POST' ? 'form_params' : 'query';
        $response = $this->request($uri, $method, array_merge($this->options, $options, [
            $params_type => $params,
        ]));

        return $response->getBody()->getContents();
    }

    public function request($uri, $method = 'GET', $options = [])
    {
        $options['http_errors'] = true;

        $times = $this->options['retry_times'];
        $request = new Request($method, $uri);

        beginning:

        $exception = null;

        if ($options['rate_limit'] > 0) {
            rate_limit($options['rate_key'], $options['rate_limit']);
        }

        try {
            $response = $this->client->send($request, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $exception = $e;
        }

        $status = $response->getStatusCode();
        $body = $response->getBody();

        if ($exception instanceof ClientException && $status != 429) {
            $exception = null;
        }

        if (!$exception && !$this->options['allow_empty'] && !$body->getContents()) {
            $exception = new BadResponseException('Empty response content', $request, $response);
        }

        if ($exception) {
            $times--;
            if ($times) {
                microsleep($this->options['retry_sleep']);
                goto beginning;
            } else {
                throw $exception;
            }
        }

        return $response;
    }
}
