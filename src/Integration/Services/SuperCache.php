<?php

/**
 * Super Cache
 */

namespace Clickio\Integration\Services;

use Clickio\ClickioPlugin;
use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with Super Cache
 *
 * @package Integration\Services
 */
final class SuperCache extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wp-super-cache/wp-cache.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'super_cache';

    /**
     * Disable plugin for a single request
     *
     * @return void
     */
    public static function setUpCacheCleaners()
    {
        if (!static::integration()) {
            return ;
        }

        $plugin = ClickioPlugin::getInstance();
        $cache = $plugin->getCache();
        if (array_key_exists('wp_delete_expired', $_REQUEST)) {
            $cache->execute_purge_no_id();
        }

        try {
            add_action("wp_cache_cleared", [$cache, 'execute_purge_no_id']);
            add_action("gc_cache", [static::class, 'addPurgeUrl'], 10, 2);
        } catch (\Exception $e) {
            // do nothing
        }
    }

    /**
     * Add url purge queue
     *
     * @param mixed $act action name
     * @param mixed $url url to be purged
     *
     * @return void
     */
    public static function addPurgeUrl($act, $url)
    {
        $plugin = ClickioPlugin::getInstance();
        $cache = $plugin->getCache();
        $cache->addPurgeUrl($url);
    }
}