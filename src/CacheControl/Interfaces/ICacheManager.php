<?php
/**
 * Cache manager interface
 */

namespace Clickio\CacheControl\Interfaces;

/**
 * Cache manager interface
 *
 * @package CacheControl\interfaces
 */
interface ICacheManager
{
    /**
     * Getter
     * Full list of available services
     *
     * @return array
     */
    public function getServices(): array;
}