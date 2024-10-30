<?php

/**
 * Http request wrapper
 */

namespace Clickio\Request;

use Clickio\Authorization\TokenAuthorizationManager;
use Clickio\ClickioPlugin;
use Clickio\Request\Interfaces\IHttpRequest;
use Clickio\Request\Interfaces\IHttpResponse;
use Clickio\Utils\Container;

/**
 * Http request wrapper
 *
 * @package Request
 */
class Request extends Container implements IHttpRequest
{
    /**
     * Timeout
     *
     * @var int
     */
    protected $timeout = 15;

    /**
     * User-Agent
     *
     * @var string
     */
    protected $ua = '';

    /**
     * Async
     *
     * @var bool
     */
    protected $blocking = true;

    /**
     * Verify ssl
     *
     * @var bool
     */
    protected $sslverify = false;

    /**
     * Signed request
     *
     * @var bool
     */
    protected $signed = true;

    /**
     * Http headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Make GET request
     *
     * @param string $url request url
     * @param array $query url query string
     *
     * @return IHttpResponse
     */
    public function get(string $url, array $query = []): IHttpResponse
    {
        $params = $this->toArray();
        $query = http_build_query($query);
        if ($this->signed) {
            $auth = TokenAuthorizationManager::create();
            $token = $auth->generateToken($query);
            $url = sprintf("%s?%s&token=%s", $url, $query, $token);
        } else {
            if (!empty($query)) {
                $url = sprintf("%s?%s", $url, $query);
            }
        }


        return $this->_request('get', $url, $params);
    }

    /**
     * Make POST request
     *
     * @param string $url request url
     * @param mixed $body request body
     * @param array $extra extra body values
     *
     * @return IHttpResponse
     */
    public function post(string $url, $body = "", array $extra = []): IHttpResponse
    {
        $params = $this->toArray();

        if ($this->signed) {
            $auth = TokenAuthorizationManager::create();
            $payload = json_encode($body);
            $token = $auth->generateToken($payload);
            $params['body'] = $extra;
            $params['body']['payload'] = $payload;
            $params['body']['token'] = $token;
        } else {
            $params['body'] = $body;
        }
        return $this->_request('post', $url, $params);
    }

    /**
     * Perform request
     *
     * @param string $method http request method
     * @param string $url requested url
     * @param array $params request params
     *
     * @return IHttpResponse
     */
    private function _request(string $method, string $url, array $params): IHttpResponse
    {
        $params['method'] = strtoupper($method);
        $resp = wp_remote_request($url, $params);
        if (is_wp_error($resp)) {
            throw new \Exception($resp->get_error_message());
        }

        return Response::create($resp);
    }

    /**
     * Copy self value into array
     *
     * @return array
     */
    public function toArray(): array
    {
        $arr = parent::toArray();
        $ua = $arr['ua'];
        unset($arr['ua']);
        $arr['user-agent'] = $ua;
        return $arr;
    }

    /**
     * Setter
     * Set http header
     *
     * @param string $name header name
     * @param string $value header value
     *
     * @return void
     */
    public function setHeader(string $name, string $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Setter
     * Remove http headers
     *
     * @param string $name http header name
     *
     * @return void
     */
    public function removeHeader(string $name)
    {
        if (array_key_exists($name, $this->headers)) {
            unset($this->headers[$name]);
        }
    }

    /**
     * Factory method
     *
     * @param array $params input params
     *
     * @return static
     */
    public static function create(array $params = [])
    {
        $obj = parent::create($params);
        if (empty($obj->ua) && method_exists(ClickioPlugin::class, "getPluginUA")) {
            $obj->ua = ClickioPlugin::getPluginUA();
        } else {
            $obj->ua = 'WP';
        }
        return $obj;
    }
}
