<?php

/**
 * Redis object cache
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with RedisObjectCache
 *
 * @package Integration\Services
 */
final class RedisObjectCache extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'redis-cache/redis-cache.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'redis_oc';

    /**
     * Disable plugin for a single request
     *
     * @return void
     */
    public static function disableDebugOut()
    {
        if (!static::integration()) {
            return ;
        }

        if (!defined('\WP_REDIS_DISABLE_COMMENT')) {
            define('WP_REDIS_DISABLE_COMMENT', true);
        }
    }
}