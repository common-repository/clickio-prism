<?php

/**
 * QuickAdsense
 */

namespace Clickio\Integration\Services;

use Clickio\ClickioPlugin;
use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with QuickAdsense
 *
 * @package Integration\Services
 */
final class QuickAdsense extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'quick-adsense/quick-adsense.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'qadsens';

    /**
     * Disable plugin for request
     *
     * @return void
     */
    public static function registerSaveOptionsHandler()
    {
        if (!static::integration()) {
            return ;
        }

        add_action("update_option_quick_adsense_settings", [static::class, "clearCacheOnSave"]);
    }

    /**
     * Clear cache when Quick Adsens saved
     *
     * @return void
     */
    public static function clearCacheOnSave()
    {
        $plugin = ClickioPlugin::getInstance();
        $cache = $plugin->getCache();
        $cache->addPurgeUrl(home_url().'/?purge_all');

        $logger = $plugin->getLogger();
        $msg = sprintf("%s: settings saved, cache clearing starts", static::class);
        $logger->debug($msg);
    }
}