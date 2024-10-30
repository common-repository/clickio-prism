<?php
/**
 *  Integration service interface
 */

namespace Clickio\Integration\Interfaces;

/**
 * Integration service
 *
 * @package Integration\Interfaces
 */
interface IIntegrationService
{
    /**
     * Tets integration is available
     *
     * @return bool
     */
    public static function integration(): bool;

    /**
     * Check that service can work with plugin
     *
     * @param string $plugin_id plugin id
     *
     * @return bool
     */
    public static function canIntegrateWith(string $plugin_id): bool;

    /**
     * Get service alias
     *
     * @return string
     */
    public static function getAlias(): string;
}