<?php
/**
 * Simple google recaptcha
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with Simple google reCaptcha plugin
 *
 * @package Integration\Services
 */
final class SecureImageWp extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'securimage-wp/securimage-wp.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'siwp';

    /**
     * Disable captcha check
     *
     * @return void
     */
    public static function disable()
    {
        if (!static::integration()) {
            return ;
        }

        remove_filter('pre_comment_approved', 'siwp_process_comment', 5);
    }
}