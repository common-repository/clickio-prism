<?php

/**
 * Http response wrapper
 */

namespace Clickio\Request;

use Clickio\Request\Interfaces\IHttpResponse;
use Clickio\Utils\Container;
use Clickio\Utils\SafeAccess;

/**
 * Http response wrapper
 *
 * @package Request
 */
class Response extends Container implements IHttpResponse
{

    /**
     * Response body
     *
     * @var mixed
     */
    protected $body = '';

    /**
     * Response code
     *
     * @var int
     */
    protected $response = 0;

    /**
     * Response headers
     *
     * @var Requests_Utility_CaseInsensitiveDictionary
     */
    protected $headers = null;

    /**
     * Setter
     * Parse response body
     *
     * @param string $value field value
     *
     * @return void
     */
    protected function setBody(string $value)
    {
        if (empty($value)) {
            return ;
        }

        $body = '';
        if (preg_match("/application\/json/", $this->headers['content-type'])) {
            $parsed = $this->_parseJsonBody($value);
            if (array_key_exists('error_id', $parsed) && $parsed['error_id'] != 200) {
                throw new \Exception($parsed['error_text']);
            }

            if (array_key_exists('data', $parsed)) {
                $body = SafeAccess::fromArray($parsed, 'data', 'array', []);
            } else {
                $body = $parsed;
            }

        } else {
            $body = $value;
        }

        $this->body = $body;
    }

    /**
     * Parse json body
     *
     * @param string $body response body
     *
     * @return array
     */
    private function _parseJsonBody(string $body): array
    {
        $parsed = json_decode($body, true);
        if (empty($parsed)) {
            throw new \Exception(sprintf("Unable to parse response: %s", $body), 500);
        }

        return $parsed;
    }

    /**
     * Setter
     * Parse response body
     *
     * @param array $resp field value
     *
     * @return void
     */
    protected function setResponse(array $resp)
    {
        if (empty($resp)) {
            throw new \Exception("Empty response");
        }

        $this->response = $resp['code'];
    }
}