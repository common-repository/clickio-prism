<?php
/**
 * Rest api manager interface
 */

namespace Clickio\RestApi\Interfaces;
/**
 * Rest api manager interface
 *
 * @package RestApi\Interfaces
 */
interface IRestApiManager
{
    /**
     * Register rest api routes
     *
     * @return void
     */
    public function registerRestRoutes();
}
