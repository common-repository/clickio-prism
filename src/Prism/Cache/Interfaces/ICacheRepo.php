<?php

/**
 * Cache repository interface
 */

namespace Clickio\Prism\Cache\Interfaces;

/**
 * Cache repository
 *
 * @package Prism\Cache\Interfaces
 */
interface ICacheRepo
{
    /**
     * Get value from cache
     *
     * @param string $key cache key
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * Put data into storage
     *
     * @param string $key cache key
     * @param mixed $value data to be cached
     *
     * @return bool
     */
    public function set(string $key, $value): bool;

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
    public function getCacheSize(): int;
}
