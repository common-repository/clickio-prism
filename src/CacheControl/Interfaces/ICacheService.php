<?php
/**
 * Cache service interface
 */

namespace Clickio\CacheControl\Interfaces;

/**
 * Cache service interface
 *
 * @package CacheControl\interfaces
 */
interface ICacheService
{
    /**
     * Entry point.
     * Start cache purging.
     *
     * @param array $urllist list of urls
     *
     * @return void
     */
    public function clear(array $urllist);
}