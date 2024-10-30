<?php
/**
 * Rest api action interface
 */

namespace Clickio\RestApi\Interfaces;

use WP_REST_Request;

/**
 * Simple rest api action interface
 *
 * @package RestApi\Interfaces
 */
interface IRestApi
{
    /**
     * Rest api entry dispatcher
     *
     * @param WP_REST_Request $request http request
     *
     * @return mixed
     */
    public static function dispatch(WP_REST_Request $request);
}