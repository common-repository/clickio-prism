<?php

/**
 * WebSub
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Integration\Interfaces\IWebSub;
use Clickio\Logger\LoggerAccess;

/**
 * Integration with WebSub
 *
 * @package Integration\Services
 */
final class WebSub extends AbstractIntegrationService implements IIntegrationService, IWebSub
{
    /**
     * Logs access
     */
    use LoggerAccess;

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'pubsubhubbub/pubsubhubbub.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'websub';

    /**
     * Disable plugin for a single request
     *
     * @return void
     */
    public static function setupFilters()
    {
        if (!static::integration()) {
            return ;
        }

        add_filter('pubsubhubbub_feed_urls', [static::class, 'filterPingUrls']);
    }

    /**
     * DO NOT call this function directly!
     *
     * @param array $urls list of urls
     *
     * @return array
     */
    public static function filterPingUrls($urls)
    {
        // $types = apply_filters('pubsubhubbub_supported_feed_types', ['atom']);
        $types = ['atom', 'rss2'];
        $feed_urls = [];
        foreach ($types as $type) {
            $feed_urls[] = get_feed_link($type);
        }
        static::logDebug("Pubsubhubbub urls", $feed_urls);
        return $feed_urls;
    }

    /**
     * Check if websub is enabled
     *
     * @return bool
     */
    public static function isWebSubEnabled(): bool
    {
        if (!static::integration()) {
            return false;
        }

        return true;
    }
}
