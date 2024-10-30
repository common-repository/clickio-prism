<?php

/**
 * WP Subtitle
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with WP Subtitle
 *
 * @package Integration\Services
 */
final class WPSubtitle extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wp-subtitle/wp-subtitle.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpsubtitle';

    /**
     * Disable plugin for request
     *
     * @param int $post_id post id
     *
     * @return string
     */
    public static function getTheSubtitle(int $post_id): string
    {
        $subtitle = '';
        if (!static::integration()) {
            return $subtitle;
        }

        try {
            $args = [
                "before" => '',
                "after" => '',
                "post_id" => $post_id
            ];
            $subtitle = \apply_filters('plugins/wp_subtitle/get_subtitle', '', $args);
        } catch (\Exception $e) {
            // do nothing
        }
        return $subtitle;
    }
}