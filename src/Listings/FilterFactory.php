<?php

/**
 * Filter factory
 */

namespace Clickio\Listings;

use Clickio\Listings\Interfaces\IFilter;
use Clickio\Logger\Logger;
use Clickio\Utils\FileSystem;
use Clickio\Utils\SafeAccess;

/**
 * Filter factory
 *
 * @package Listings
 */
class FilterFactory
{
    /**
     * Only one discover
     *
     * @var bool
     */
    protected static $discovered = false;

    /**
     * Filter types
     *
     * @var array
     */
    protected static $types = [];

    /**
     * Available filters
     *
     * @var array
     */
    protected static $filters = [];

    /**
     * Find filters
     *
     * @return void
     */
    protected static function discover()
    {
        if (static::$discovered) {
            return ;
        }

        $dir_path = sprintf("%s/Filters", dirname(__FILE__));
        foreach (FileSystem::scandir($dir_path) as $target) {
            $cls = sprintf("%s\%s\%s", __NAMESPACE__, "Filters", basename($target, '.php'));
            $implements = class_implements($cls, true);
            if (!empty($implements) && in_array(IFilter::class, $implements)) {
                static::$filters[] = $cls;
            }
        }

        static::$discovered = true;
    }

    /**
     * Factory method.
     * Create new Filter.
     *
     * @param string $type filter type
     * @param array $args filter arguents
     *
     * @return IFilter
     */
    public static function create(string $type, array $args = []): IFilter
    {
        if (!static::$discovered) {
            static::discover();
        }

        if (in_array($type, static::$filters)) {
            $domain = SafeAccess::fromArray($_SERVER, 'HTTP_HOST', 'string', 'localhost');
            $logger = Logger::getLogger($domain);
            array_unshift($args, $logger);
            $inst = new $type(...$args);
            return $inst;
        }

        throw new \Exception("Filter $type doesn't exists");
    }

    /**
     * Get all filters
     *
     * @return array
     */
    public static function getFilters(): array
    {
        if (!static::$discovered) {
            static::discover();
        }

        return static::$filters;
    }
}
