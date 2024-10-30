<?php

/**
 * Invisible reCaptcha
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with Invisible reCaptcha
 *
 * @package Integration\Services
 */
final class InvisibleRecaptcha extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'invisible-recaptcha/invisible-recaptcha.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'invre';

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
            add_filter('google_invre_is_valid_request_filter', '__return_true', 9999999999);
        } catch (\Exception $e) {
            // do nothing
        }
    }
}