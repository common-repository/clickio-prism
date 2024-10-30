<?php
/**
 * Extra content factory
 */

namespace Clickio\ExtraContent;

use Clickio\ExtraContent\Interfaces\IExtraContentService;
use Clickio\Utils\FileSystem;
use Exception;

/**
 * Service factory
 *
 * @package ExtraContent
 */
class ExtraContentServiceFactory
{

    /**
     * Service classes
     *
     * @var array
     */
    protected static $services = [];

    /**
     * Flag.
     * Prevent mulriple discovers
     *
     * @var bool
     */
    protected static $discovered = false;

    /**
     * Factory method
     *
     * @param string $name service alias
     * @param array $args service arguments
     *
     * @return IExtraContentService
     */
    public static function create(string $name, array $args = []): IExtraContentService
    {
        static::discover();

        if (array_key_exists($name, static::$services)) {
            $serv = static::$services[$name];
            return new $serv(...$args);
        }

        throw new Exception("Undefined service: '$name'");
    }

    /**
     * Create all services
     *
     * @return array
     */
    public static function createAll(): array
    {
        static::discover();

        $serv_list = [];
        foreach (array_keys(static::$services) as $serv_name) {
            $serv_list[] = static::create($serv_name);
        }
        return $serv_list;
    }

    /**
     * Get service class by name
     *
     * @param string $name service alias
     *
     * @return string
     */
    public static function getService(string $name): string
    {
        static::discover();

        if (array_key_exists($name, static::$services)) {
            return static::$services[$name];
        }
        throw new Exception("Service '$name' not found");
    }

    /**
     * Get available service
     *
     * @return array
     */
    public static function getAllServices(): array
    {
        static::discover();
        return array_keys(static::$services);
    }

    /**
     * Discover all services
     *
     * @return void
     */
    protected static function discover()
    {
        if (static::$discovered) {
            return ;
        }

        $service_location = sprintf("%s/Services", dirname(__FILE__));
        foreach (FileSystem::scandir($service_location) as $path) {
            $service_class = sprintf("%s\Services\%s", __NAMESPACE__, basename($path, '.php'));
            $ifaces = class_implements($service_class);
            if (in_array(IExtraContentService::class, $ifaces)) {
                $name = $service_class::getName();
                static::$services[$name] = $service_class;
            }
        }

        static::$discovered = true;
    }
}
