<?php

/**
 * WP external links
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Exception;

/**
 * Integration with WP External Links
 *
 * @package Integration\Services
 */
final class WPEL extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wp-external-links/wp-external-links.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'wpel';

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
            add_filter('wpel_apply_settings', '__return_false');
        } catch (\Exception $e) {
            // do nothing
        }
    }

    /**
     * Apply wpel links transformation
     *
     * @param string $content article content
     *
     * @return string
     */
    public static function apply($content)
    {
        if (!static::integration()) {
            return $content;
        }

        if (empty($content)) {
            return '';
        }

        if (!class_exists("\WPEL_Front")) {
            return $content;
        }

        try {
            $inst = \WPEL_Front::get_instance();
            $content = $inst->scan($content);
        } catch (Exception $e) {
            // silence is golden
        }

        return $content;
    }
}
