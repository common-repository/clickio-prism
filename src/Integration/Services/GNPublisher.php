<?php

/**
 * GN Publisher
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Integration\Interfaces\IWebSub;
use Clickio\Logger\LoggerAccess;

/**
 * Integration with GN Publisher
 *
 * @package Integration\Services
 */
final class GNPublisher extends AbstractIntegrationService implements IIntegrationService, IWebSub
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
    const PLUGIN_ID = 'gn-publisher/gn-publisher.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'gn_publisher';

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
