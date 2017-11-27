<?php

namespace Febalist\LaravelHttp;

use GuzzleHttp\Client;

/**
 * @method self query($data)
 * @method self body($data)
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
        $this->options = $options + [
                'http_errors' => false,
            ];
    }

    public static function runscope($url)
    {
        $debug = config('app.debug', false);
        $config = config('http.runscope', []);

        if ($config['enabled'] === false || ($config['enabled'] === null && !$debug)) {
            return $url;
        }

        $host_old = parse_url($url)['host'];
        $host_new = str_replace(['-', '.'], ['~', '-'], $host_old);
        $host_new = sprintf('%s-%s.%s', $host_new, $config['bucket'], $config['gateway']);
        $host_new = str_replace('~', '--', $host_new);

        return str_replace_first($host_old, $host_new, $url);
    }

    public function __call($name, $arguments)
    {
        $this->options[$name] = $arguments[0];

        return $this;
    }

    /** @return Response */
    public function send($method, $params = null)
    {
        $method = strtoupper($method);
        if ($params && $method == 'GET') {
            $this->query($params);
        } elseif ($params) {
            $this->body($params);
        }
        $client = new Client();
        $response = $client->request($method, static::runscope($this->url), $this->options);

        return new Response($response);
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
    public function post($body = null)
    {
        if ($body) {
            $this->body($body);
        }

        return $this->send('POST');
    }

    /** @return self */
    public function form($data)
    {
        $this->form_params($data);

        return $this;
    }

    /** @return self */
    public function redirects($follow)
    {
        if ($follow === false || is_array($follow)) {
            $this->allow_redirects($follow);
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
}
