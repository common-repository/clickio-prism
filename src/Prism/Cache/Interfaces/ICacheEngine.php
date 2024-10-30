<?php

/**
 * Cache service interface
 */

namespace Clickio\Prism\Cache\Interfaces;

/**
 * Cache service interface
 *
 * @package Prism\Cache\Interfaces
 */
interface ICacheEngine
{
    /**
     * Max expiration time 30 days
     *
     * @var int
     */
    const CACHE_FILE_EXPIRE_MAX = 2592000;

    /**
     * Get cached data
     *
     * @param string $key cache key
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * Put some data into cache
     *
     * @param string $key cache key
     * @param mixed $value cache value
     *
     * @return bool
     */
    public function add(string $key, $value): bool;

    /**
     * Purge cache for a key
     *
     * @param string $key cache key
     *
     * @return bool
     */
    public function purge(string $key): bool;

    /**
     * Purge all
     *
     * @return bool
     */
    public function purgeAll(): bool;

    /**
     * Get cache size
     *
     * @return int
     */
    public function getSize(): int;
}
