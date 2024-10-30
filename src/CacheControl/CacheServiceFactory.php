<?php
/**
 * Cache servvice factory
 */

namespace Clickio\CacheControl;

use Clickio\Utils\FileSystem;

/**
 * Cache service factory
 *
 * @package CacheControll
 */
class CacheServiceFactory
{
    /**
     * Services folder
     *
     * @var string
     */
    const SERVICES_DIR = CLICKIO_PLUGIN_DIR."/src/CacheControl/Services";

    /**
     * Create cleaner service
     *
     * @param string $name service name
     *
     * @return ICacheService
     */
    public static function create(string $name): Interfaces\ICacheService
    {
        $ns_list = explode('\\', $name);
        if (count($ns_list) == 1) {
            $name = static::_convertToFQCN($name);
        }

        if (class_exists($name)) {
            $cls = new $name();
            return $cls;
        }

        throw new \Exception("Undefined cleaner $name");
    }

    /**
     * Convert natural name into "Fully qualified class name"
     * By historical reasons class names for cache services
     * uses natural names without namespace
     *
     * @param string $cls class name without namespace
     *
     * @return string
     */
    private static function _convertToFQCN(string $cls): string
    {
        return  sprintf("%s\%s\%s", __NAMESPACE__, "Services", $cls);
    }

    /**
     * Instantiate all services
     *
     * @return array
     */
    public static function createAll(): array
    {
        $services = [];
        foreach (FileSystem::scandir(static::SERVICES_DIR) as $file) {
            $name= basename($file, '.php');
            try{
                $services[] = static::create($name);
            } catch (\Exception $err){
                continue ;
            }
        }
        return $services;
    }
}
