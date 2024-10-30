<?php

/**
 * W3 Total cache
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Logger\LoggerAccess;

/**
 * Integration with W3 Total Cache
 *
 * @package Integration\Services
 */
final class W3TotalCache extends AbstractIntegrationService implements IIntegrationService
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
    const PLUGIN_ID = 'w3-total-cache/w3-total-cache.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'w3total';

    /**
     * Disable plugin for a single request
     *
     * @return void
     */
    public static function disable()
    {
        if (!static::integration()) {
            return ;
        }

        add_filter('w3tc_can_cache', '__return_false');
        add_filter('w3tc_can_print_comment', '__return_false');
    }

    /**
     * When user already manually asked to remove another plugin's add in
     * we should clear this status
     *
     * @return void
     */
    public static function clearRemoveDropinStatus()
    {
        if (!static::integration()) {
            return ;
        }

        delete_transient('w3tc_remove_add_in_pgcache');
    }
}
