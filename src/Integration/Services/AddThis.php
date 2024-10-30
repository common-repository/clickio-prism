<?php

/**
 * AddThis
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with addthis
 *
 * @package Integration\Services
 */
final class AddThis extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'addthis/addthis_social_widget.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'addthis';

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

        add_filter('addthis_sharing_buttons_enable', '__return_false');
        add_filter('addthis_sharing_buttons_below_enable', '__return_false');
        add_filter('addthis_sharing_buttons_above_enable', '__return_false');
        add_filter("get_the_excerpt", [static::class, 'getTheExcerpt'], 999999);
    }

    /**
     * Cut all comments from excerpt
     *
     * @param string $excerpt excerpt text
     *
     * @return string
     */
    public static function getTheExcerpt($excerpt)
    {
        $excerpt = preg_replace("/<!--.*?-->/Uism", "", $excerpt);
        return $excerpt;
    }
}