<?php

/**
 * WP Rocket
 */

namespace Clickio\Integration\Services;

use Clickio\ClickioPlugin;
use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with WP Rocket
 *
 * @package Integration\Services
 */
final class WPRocket extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wp-rocket/wp-rocket.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wp_rocket';

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

        try {
            add_action("before_rocket_clean_file", [$cache, 'addPurgeUrl']);
            add_action("before_rocket_clean_home", [$cache, 'addPurgeUrl']);
            add_action("before_rocket_clean_minify", [$cache, 'execute_purge_no_id']);
            add_action("before_rocket_clean_busting", [$cache, 'execute_purge_no_id']);
            add_action("before_rocket_clean_domain", [$cache, 'execute_purge_no_id']);
        } catch (\Exception $e) {
            // do nothing
        }
    }
}