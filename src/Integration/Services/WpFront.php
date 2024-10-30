<?php

/**
 * WPFront notification bar
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with WPFront notification bar
 *
 * @package Integration\Services
 */
final class WpFront extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wpfront-notification-bar/wpfront-notification-bar.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpfront';

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

        try {
            if (!defined('DOING_AJAX')) {
                define('DOING_AJAX', 1);
            } else if (!defined('XMLRPC_REQUEST')) {
                define('XMLRPC_REQUEST', 1);
            } else {
                $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
            }
        } catch (\Exception $e) {
            // do nothing
        }
    }
}