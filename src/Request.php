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
    const HTTP_URL_REPLACE = 1; // Replace every part of the first URL when there's one of the second URL
    const HTTP_URL_JOIN_PATH = 2; // Join relative paths
    const HTTP_URL_JOIN_QUERY = 4; // Join query strings
    const HTTP_URL_STRIP_USER = 8; // Strip any user authentication information
    const HTTP_URL_STRIP_PASS = 16; // Strip any password authentication information
    const HTTP_URL_STRIP_AUTH = 32; // Strip any authentication information
    const HTTP_URL_STRIP_PORT = 64; // Strip explicit port numbers
    const HTTP_URL_STRIP_PATH = 128; // Strip complete path
    const HTTP_URL_STRIP_QUERY = 256; // Strip query string
    const HTTP_URL_STRIP_FRAGMENT = 512; // Strip any fragments (#identifier)
    const HTTP_URL_STRIP_ALL = 1024; // Strip anything but scheme and host

    protected $options;

    public function __construct($url, $options = [])
    {
        $this->url = $url;
        $this->options = $options + [
                'http_errors' => false,
            ];
    }

    /**
     * Build an URL.
     *
     * The parts of the second URL will be merged into the first according to the
     * flags argument.
     *
     * @param $url     string|array
     *                 (Part(s) of) an URL in form of a string or associative array like
     *                 parse_url() returns (optional)
     * @param $parts   array
     *                 Same as the first argument
     * @param $flags   integer
     *                 A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is
     *                 the default
     * @param $new_url array|Boolean
     *                 If set, it will be filled with the parts of the composed url like
     *                 parse_url() would return
     *
     * @return string
     */
    public static function http_build_url($url = null, $parts = [], $flags = self::HTTP_URL_REPLACE, &$new_url = false)
    {
        $keys = ['user', 'pass', 'port', 'path', 'query', 'fragment'];
        if ($flags & self::HTTP_URL_STRIP_ALL) {
            // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
            $flags |= self::HTTP_URL_STRIP_USER;
            $flags |= self::HTTP_URL_STRIP_PASS;
            $flags |= self::HTTP_URL_STRIP_PORT;
            $flags |= self::HTTP_URL_STRIP_PATH;
            $flags |= self::HTTP_URL_STRIP_QUERY;
            $flags |= self::HTTP_URL_STRIP_FRAGMENT;
        } elseif ($flags & self::HTTP_URL_STRIP_AUTH) {
            // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
            $flags |= self::HTTP_URL_STRIP_USER;
            $flags |= self::HTTP_URL_STRIP_PASS;
        }
        // Parse the original URL
        $parse_url = '';
        if (is_string($url)) {
            $parse_url = parse_url($url);
        }
        // Scheme and Host are always replaced
        if (isset($parts['scheme'])) {
            $parse_url['scheme'] = $parts['scheme'];
        }
        if (isset($parts['host'])) {
            $parse_url['host'] = $parts['host'];
        }
        // (If applicable) Replace the original URL with it's new parts
        if ($flags & self::HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key])) {
                    $parse_url[$key] = $parts[$key];
                }
            }
        } else {
            // Join the original URL path with the new path
            if (isset($parts['path']) && ($flags & self::HTTP_URL_JOIN_PATH)) {
                if (isset($parse_url['path'])) {
                    $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']),
                            '/') . '/' . ltrim($parts['path'], '/');
                } else {
                    $parse_url['path'] = $parts['path'];
                }
            }
            // Join the original query string with the new query string
            if (isset($parts['query']) && ($flags & self::HTTP_URL_JOIN_QUERY)) {
                if (isset($parse_url['query'])) {
                    $parse_url['query'] .= '&' . $parts['query'];
                } else {
                    $parse_url['query'] = $parts['query'];
                }
            }
        }
        // Strips all the applicable sections of the URL
        // Note: Scheme and Host are never stripped
        foreach ($keys as $key) {
            if ($flags & static::getIntegerConstantForPart($key)) {
                unset($parse_url[$key]);
            }
        }
        $new_url = $parse_url;

        return ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
            . ((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') . '@' : '')
            . ((isset($parse_url['host'])) ? $parse_url['host'] : '')
            . ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
            . ((isset($parse_url['path'])) ? $parse_url['path'] : '')
            . ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
            . ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '');
    }

    protected static function getIntegerConstantForPart($key)
    {
        $result = null;
        switch (strtoupper($key)) {
            case 'ALL':
                $result = self::HTTP_URL_STRIP_ALL;
                break;
            case 'AUTH':
                $result = self::HTTP_URL_STRIP_AUTH;
                break;
            case 'FRAGMENT':
                $result = self::HTTP_URL_STRIP_FRAGMENT;
                break;
            case 'PASS':
                $result = self::HTTP_URL_STRIP_PASS;
                break;
            case 'PATH':
                $result = self::HTTP_URL_STRIP_PATH;
                break;
            case 'PORT':
                $result = self::HTTP_URL_STRIP_PORT;
                break;
            case 'QUERY':
                $result = self::HTTP_URL_STRIP_QUERY;
                break;
            case 'USER':
                $result = self::HTTP_URL_STRIP_USER;
                break;
        }

        return (int) $result;
    }

    public function __call($name, $arguments)
    {
        $this->options[$name] = $arguments[0];

        return $this;
    }

    public function send($method, $params = null)
    {
        $method = strtoupper($method);
        if ($params && $method == 'GET') {
            $this->query($params);
        } elseif ($params) {
            $this->body($params);
        }
        $client = new Client();
        $response = $client->request($method, $this->url, $this->options);

        return new Response($response);
    }

    public function get($query = null)
    {
        if ($query) {
            $this->query($query);
        }

        return $this->send('GET');
    }

    public function post($body = null)
    {
        if ($body) {
            $this->body($body);
        }

        return $this->send('POST');
    }

    public function form($data)
    {
        $this->form_params($data);

        return $this;
    }

    public function redirects($follow)
    {
        if ($follow === false || is_array($follow)) {
            $this->allow_redirects($follow);
        }

        return $this;
    }

    public function auth_basic($username, $password = null)
    {
        $this->auth([$username, $password]);

        return $this;
    }

    public function auth_digest($username, $password = null)
    {
        $this->auth([$username, $password, 'digest']);

        return $this;
    }
}
