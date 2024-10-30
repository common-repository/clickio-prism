<?php
/**
 * Rest API factory
 */

namespace Clickio\RestApi;

use Clickio\RestApi as rest;

/**
 * Rest api factory
 *
 * @package RestApi
 */
class RestApiFactory
{

    /**
     * Create action
     *
     * @param string $name action name in PSR-4
     * @param array $params extra params
     *
     * @return rest\Interfaces\IRestApi
     */
    public static function create(string $name, array $params = []): rest\Interfaces\IRestApi
    {
        return new $name(...$params);
    }

    /**
     * Create rest api manager
     *
     * @param string $type manager type
     * @param array $params manager extra params
     *
     * @return rest\Interfaces\IRestApiManager
     */
    public static function createManager(string $type, array $params = []): rest\Interfaces\IRestApiManager
    {
        switch($type){
            case 'default':
                return new rest\RestApiManager(...$params);
            default:
                throw new \Exception("No manager with type $type was found");
        }
    }
}
