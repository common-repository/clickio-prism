<?php
/**
 * Simple google recaptcha
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\SafeAccess;

/**
 * Actions with Simple google reCaptcha plugin
 *
 * @package Integration\Services
 */
final class SGR extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'simple-google-recaptcha/simple-google-recaptcha.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'sgr';

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
        global $wp_filter;

        $hook_priority = '';
        $unique_id = '';

        $wp_hook = SafeAccess::fromArray($wp_filter, 'preprocess_comment', 'object', null);
        if (empty($wp_hook) || !property_exists($wp_hook, 'callbacks')) {
            return ;
        }

        foreach ($wp_hook->callbacks as $priority => $filter) {
            foreach ($filter as $key => $value) {
                if (preg_match('/sgr_verify/', $key) || preg_match('/verify/', $key)) {
                    $hook_priority = $priority;
                    $unique_id = $key;
                    break 2;
                }
            }
        }


        if (empty($hook_priority) || empty($unique_id)) {
            return ;
        }

        $priority_exists = SafeAccess::arrayKeyExists($hook_priority, $wp_hook->callbacks);
        if (!$priority_exists) {
            return ;
        }

        $unique_id_exists = SafeAccess::arrayKeyExists($unique_id, $wp_hook->callbacks[$hook_priority]);
        if (!$unique_id_exists) {
            return ;
        }

        unset($wp_hook->callbacks[$hook_priority][$unique_id]);
    }
}