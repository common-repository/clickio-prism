<?php

/**
 * DocketCache
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with DocketCache
 *
 * @package Integration\Services
 */
final class DocketCache extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'docket-cache/docket-cache.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'docket_cache';

    /**
     * Disable plugin signature
     *
     * @return void
     */
    public static function disable()
    {
        if (!static::integration()) {
            return ;
        }

        add_filter('docketcache/filter/signature/htmlfooter', '__return_empty_string', PHP_INT_MAX);
        add_filter('docketcache/filter/skipposttype/postcache', '__return_true', PHP_INT_MAX);
    }
}
