<?php

namespace Febalist\Laravel\Http;

use GuzzleHttp\Client;

/**
 * @method self query($data)
 * @method self json($data)
 * @method self headers($data)
 * @method self timeout($seconds)
 * @method self verify(bool $enabled)
 */
class Request
{
    protected $options;

    public function __construct($url, $options = [])
    {
        $this->url = $url;
        $this->options = array_merge([
            'http_errors' => false,
            'verify'      => !config('app.debug'),
        ], $options);
    }

    public function __call($name, $arguments)
    {
        $this->option($name, $arguments[0]);

        return $this;
    }

    /** @return Response */
    public function send($method, $params = null)
    {
        $method = strtoupper($method);
        if ($params && $method == 'GET') {
            $this->query($params);
        } elseif ($params) {
            $this->form($params);
        }
        $client = new Client();
        $response = $client->request($method, $this->url, $this->options);

        return new Response($response, $this);
    }

    /** @return Response */
    public function get($query = null)
    {
        if ($query) {
            $this->query($query);
        }

        return $this->send('GET');
    }

    /** @return Response */
    public function post($form = null)
    {
        if ($form) {
            $this->form($form);
        }

        return $this->send('POST');
    }

    /** @return self */
    public function form($data)
    {
        $this->option('form_params', $data);

        return $this;
    }

    /** @return self */
    public function redirects($follow)
    {
        if ($follow === false || is_array($follow)) {
            $this->option('allow_redirects', $follow);
        }

        return $this;
    }

    /** @return self */
    public function auth_basic($username, $password = null)
    {
        $this->auth([$username, $password]);

        return $this;
    }

    /** @return self */
    public function auth_digest($username, $password = null)
    {
        $this->auth([$username, $password, 'digest']);

        return $this;
    }

    protected function option($key, $value)
    {
        $this->options[$key] = $value;
    }
}
