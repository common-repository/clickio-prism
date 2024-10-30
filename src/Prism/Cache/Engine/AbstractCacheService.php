<?php

/**
 * Abstract Cache service
 */

namespace Clickio\Prism\Cache\Engine;

use Clickio\Logger\LoggerAccess;

/**
 * Base cache service
 *
 * @package Prism\Cache\Engine
 */
abstract class AbstractCacheService
{
    /**
     * Logger trait
     */
    use LoggerAccess;

    /**
     * Get cached data
     *
     * @param string $key cache key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return '';
    }

    /**
     * Put some data into cache
     *
     * @param string $key cache key
     * @param mixed $value cache value
     * @param ?int $lifetime custom cache lifetime
     *
     * @return bool
     */
    public function add(string $key, $value, $lifetime = null): bool
    {
        return true;
    }

    /**
     * Purge cache for a key
     *
     * @param string $key cache key
     *
     * @return bool
     */
    public function purge(string $key): bool
    {
        return true;
    }

    /**
     * Purge all
     *
     * @return bool
     */
    public function purgeAll(): bool
    {
        return true;
    }

    /**
     * Get cache size
     *
     * @return int
     */
    public function getSize(): int
    {
        return 0;
    }
}
