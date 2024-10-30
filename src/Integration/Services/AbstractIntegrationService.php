<?php

/**
 * Abstract integration service
 */

namespace Clickio\Integration\Services;

use Clickio\Utils\Plugins;
use Exception;

/**
 * Base class for any integration service
 *
 * @package Integration\Services
 */
abstract class AbstractIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = '';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = '';

    /**
     * Test integration is available
     *
     * @return bool
     */
    public static function integration(): bool
    {
        if (empty(static::PLUGIN_ID)) {
            throw new Exception("Plugin id can't be empty");
        }

        $status = false;
        $list = static::getIntegrationList();
        foreach ($list as $plugin) {
            $plugin_active = Plugins::pluginIsActive($plugin);
            if ($plugin_active) {
                $status = true;
                break;
            }
        }
        return $status;
    }

    /**
     * Check that service can work with plugin
     *
     * @param string $plugin_id plugin id
     *
     * @return bool
     */
    public static function canIntegrateWith(string $plugin_id): bool
    {
        $list = static::getIntegrationList();
        return in_array($plugin_id, $list);
    }

    /**
     * Get list of plugins with which the service can integrate
     *
     * @return array
     */
    protected static function getIntegrationList(): array
    {
        return [static::PLUGIN_ID];
    }

    /**
     * Get service alias
     *
     * @return string
     */
    public static function getAlias(): string
    {
        if (empty(static::$alias)) {
            throw new Exception("Plugin alias can't be empty");
        }

        return static::$alias;
    }

}
