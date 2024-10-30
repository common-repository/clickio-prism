<?php
/**
 * Rest api action
 */

namespace Clickio\RestApi\Actions;

use Clickio\Logger\LoggerAccess;
use Clickio\RestApi as rest;
use Clickio\Utils\AcmeChallenge as UtilsAcmeChallenge;
use Exception;
use WP_REST_Response;

/**
 * Set and verify acme-challenge
 *
 * Sinopsys:
 *      GET http://domain.name/wp-json/clickio/acme/
 *
 * @package RestApi\Actions
 */
class AcmeChallenge extends rest\BaseRestAction implements rest\Interfaces\IRestApi
{
    use LoggerAccess;

    /**
     * Handle http get method
     *
     * @return mixed
     */
    public function get()
    {
        try {
            $opts = UtilsAcmeChallenge::getList();
        } catch (Exception $err) {
            $msg = $err->getMessage();
            static::logError($msg);
            return new WP_REST_Response(["error" => $msg], 200);
        }
        return $opts;
    }

    /**
     * Handle http post method
     *
     * @return mixed
     */
    public function post()
    {
        $body = $this->getPayload();
        if (empty($body) || !array_key_exists("url", $body) || !array_key_exists("body", $body)) {
            return new \WP_REST_Response(null, 400);
        }

        $resp = null;
        $code = 202;
        try {
            $key = UtilsAcmeChallenge::addRule($body['url'], $body['body']);
            $resp = ["key" => $key];
        } catch (Exception $err) {
            $code = 400;
            $resp = ["error" => $err->getMessage()];
        }
        return new \WP_REST_Response($resp, $code);
    }

    /**
     * Handle http delete method
     *
     * @return mixed
     */
    public function delete()
    {
        $body = $this->getPayload();
        if (empty($body) || !array_key_exists("key", $body)) {
            return new \WP_REST_Response(null, 400);
        }
        try {
            $res = UtilsAcmeChallenge::remove($body['key']);
        } catch (Exception $err) {
            $msg = $err->getMessage();
            static::logError($msg);
            return new \WP_REST_Response(['error' => $msg], 200);
        }

        return ["removed" => $res];
    }
}
