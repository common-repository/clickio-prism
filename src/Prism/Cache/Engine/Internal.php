<?php

/**
 * Internal cache
 */

namespace Clickio\Prism\Cache\Engine;

use Clickio\Prism\Cache\Interfaces\ICacheEngine;
use Clickio\Utils\FileSystem;

/**
 * Internal caches
 *
 * @package Prism\Cache\Engine
 */
class Internal extends Files implements ICacheEngine
{

    /**
     * Path to cache dir
     *
     * @var string
     */
    protected $cache_dir = 'wp-content/cache/clickio/internal';

    /**
     * Purge data
     *
     * @param string $key cache key
     *
     * @return boolean
     */
    public function purge(string $key): bool
    {
        return true;
    }

    /**
     * Purge all cache
     *
     * @return bool
     */
    public function purgeAll():bool
    {
        $path = ABSPATH . $this->cache_dir;
        if (!is_dir($path)) {
            return true;
        }

        return FileSystem::rrmdir($path);
    }
}
