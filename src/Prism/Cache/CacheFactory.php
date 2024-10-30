<?php

/**
 * Cache factory
 */

namespace Clickio\Prism\Cache;

use Clickio\Logger\Logger;
use Clickio\Prism\Cache\Interfaces\ICacheEngine;
use Clickio\Prism\Cache\Engine\Dummy;
use Clickio\Utils\FileSystem;
use Clickio\Utils\SafeAccess;
use Exception;

/**
 * Cache factory
 *
 * @package Prism\Cache
 */
class CacheFactory
{

    protected static $engines = [];

    /**
     * Discover flag
     *
     * @var bool
     */
    protected static $discovered = false;

    /**
     * Discover all engines
     *
     * @return void
     */
    public static function discover()
    {
        if (static::$discovered) {
            return ;
        }

        $service_location = sprintf("%s/Engine", dirname(__FILE__));
        foreach (FileSystem::scandir($service_location) as $path) {
            $service_class = sprintf("%s\Engine\%s", __NAMESPACE__, basename($path, '.php'));
            $ifaces = class_implements($service_class);
            if (!empty($ifaces) && in_array(ICacheEngine::class, $ifaces)) {
                static::$engines[] = $service_class;
            }
        }

        static::$discovered = true;
    }

    /**
     * Create cache service
     *
     * @param string $service class name
     * @param array $params cache service extra params
     *
     * @return ICacheEngine
     */
    public static function make(string $service, array $params = []): ICacheEngine
    {
        if (!static::$discovered) {
            static::discover();
        }

        if (in_array($service, static::$engines)) {
            return new $service(...$params);
        }
        return new Dummy(...$params);
    }
}
