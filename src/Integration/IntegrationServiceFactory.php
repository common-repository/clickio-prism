<?php
/**
 * Integration service factory
 */

namespace Clickio\Integration;
use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\FileSystem;

/**
 * Integration service factory
 *
 * @package Integration
 */
class IntegrationServiceFactory
{
    /**
     * Integration services location
     *
     * @var string
     */
    const SERVICE_LOCATION = 'Services';

    /**
     * Available integration services
     *
     * @var array
     */
    protected static $services = [];

    /**
     * Create single service
     *
     * @param string $cls fully qualified class name
     * @param array $params service arguments
     *
     * @return IIntegrationService
     */
    public static function create(string $cls, array $params = []): IIntegrationService
    {
        if (empty(static::$services)) {
            static::discover();
        }

        if (in_array($cls, static::$services)) {
            return new $cls(...$params);
        }
        throw new \Exception("Integration service $cls not found");
    }

    /**
     * Discover all integration services
     *
     * @return void
     */
    public static function discover()
    {
        static::$services = [];

        $location = sprintf("%s/%s", dirname(__FILE__), static::SERVICE_LOCATION);
        foreach (FileSystem::scandir($location) as $file_name) {
            $service_class = sprintf("%s\%s\%s", __NAMESPACE__, static::SERVICE_LOCATION, basename($file_name, '.php'));
            $ifaces = class_implements($service_class);
            if (!empty($ifaces) && is_array($ifaces) && in_array(IIntegrationService::class, $ifaces)) {
                static::$services[$service_class::getAlias()] = $service_class;
            }
        }
    }

    /**
     * Getter
     * Get all founded services
     *
     * @return array
     */
    public static function getServices(): array
    {
        if (empty(static::$services)) {
            static::discover();
        }

        return static::$services;
    }

    /**
     * Get service by his alias
     *
     * @param string $alias service alias
     *
     * @return string
     */
    public static function getService(string $alias): string
    {
        if (empty(static::$services)) {
            static::discover();
        }

        if (array_key_exists($alias, static::$services)) {
            return static::$services[$alias];
        }

        throw new \Exception("Service $alias not found");
    }
}
