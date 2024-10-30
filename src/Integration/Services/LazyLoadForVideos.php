<?php
/**
 * Lazy load for videos
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with Lazy load for videos plugin
 *
 * @package Integration\Services
 */
final class LazyLoadForVideos extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'lazy-load-for-videos/codeispoetry.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'lazyload_videos';

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
        remove_filter('wp_enqueue_scripts', 'lazyload_videos_frontend');
        add_filter('lazyload_videos_should_scripts_be_loaded', '__return_false', 9999);
        add_filter('pre_option_lly_opt', [static::class, 'disablePluginOptions'], 99999);
        add_filter('pre_option_llv_opt', [static::class, 'disablePluginOptions'], 99999);
    }

    public static function disablePluginOptions()
    {
        return '1';
    }
}