<?php

/**
 * OneSignal
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with OnseSignal
 *
 * @package Integration\Services
 */
final class OneSignal extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'onesignal-free-web-push-notifications/onesignal.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'onesignal';

    /**
     * Setup event listners
     *
     * @return void
     */
    public static function setupListners()
    {
        add_action('_clickio_after_plugins_extra_generated', [static::class, 'getInitScript'], 10, 1);
        add_action('onesignal_get_settings', [static::class, 'getSttings'], 10, 1);
    }

    /**
     * Get initial js script
     *
     * @param mixed $extra_inst Extra content instance
     *
     * @return void
     */
    public static function getInitScript($extra_inst)
    {

        if (!static::integration()) {
            return ;
        }

        if (!class_exists("\OneSignal_Public") || !method_exists("\OneSignal_Public", "onesignal_header")) {
            return ;
        }

        ob_start();
        \OneSignal_Public::onesignal_header();
        $js_src = ob_get_contents();
        ob_end_clean();
        if (empty($js_src)) {
            $js_src = '';
        }

        $plugin_id = str_replace(".php", '_php', static::PLUGIN_ID);
        $tag = sprintf("%s_wp_footer_99", $plugin_id);
        $extra_inst->pushContent('content', $plugin_id, $tag, $js_src, 'wp_footer');
    }

    /**
     * Modify service worker scope
     *
     * @param array $defaults onesignal params
     *
     * @return array
     */
    public static function getSttings($defaults)
    {
        $defaults['onesignal_sw_js'] = true;
        return $defaults;
    }
}
