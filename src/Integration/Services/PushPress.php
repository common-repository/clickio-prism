<?php

/**
 * PushPress
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Integration\Interfaces\IWebSub;
use Clickio\Logger\LoggerAccess;

/**
 * Integration with PushPress
 *
 * @package Integration\Services
 */
final class PushPress extends AbstractIntegrationService implements IIntegrationService, IWebSub
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
    const PLUGIN_ID = 'pushpress/pushpress.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'pushpress';

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
