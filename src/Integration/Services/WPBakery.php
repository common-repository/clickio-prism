<?php

/**
 * WPBakery Page Builder
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with WPBakery Page Builder
 *
 * @package Integration\Services
 */
final class WPBakery extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'js_composer/js_composer.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpb';

    /**
     * Disable plugin for a single request
     *
     * @return void
     */
    public static function registerShortcodes()
    {
        if (!static::integration()) {
            return "";
        }

        if (!class_exists("\WPBMap", false)) {
            return ;
        }

        if (!method_exists("\WPBMap", "addAllMappedShortcodes")) {
            return ;
        }

        \WPBMap::addAllMappedShortcodes();
    }
}