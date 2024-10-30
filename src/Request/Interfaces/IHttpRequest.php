<?php
/**
 * Http request interface
 */

namespace Clickio\Request\Interfaces;

/**
 * Http request interface
 *
 * @package Request\Interfaces
 */
interface IHttpRequest
{
    /**
     * Make GET request
     *
     * @param string $url request url
     * @param array $query url query string
     *
     * @return IHttpResponse
     */
    public function get(string $url, array $query = []): IHttpResponse;

    /**
     * Make POST request
     *
     * @param string $url request url
     * @param mixed $body request body
     *
     * @return IHttpResponse
     */
    public function post(string $url, $body = ""): IHttpResponse;
}