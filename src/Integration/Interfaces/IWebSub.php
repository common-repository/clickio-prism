<?php

/**
 * Plugin can work with WebSub (PubsubHubbub)
 */

namespace Clickio\Integration\Interfaces;

/**
 * WebSub functionality
 *
 * @package Integration\Interfaces
 */
interface IWebSub
{
    /**
     * Check if websub is enabled
     *
     * @return bool
     */
    public static function isWebSubEnabled(): bool;
}
