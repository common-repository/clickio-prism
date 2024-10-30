<?php

/**
 * WP external links
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\Plugins;

/**
 * Integration with WP External Links
 *
 * @package Integration\Services
 */
final class AIOSEOP extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'all-in-one-seo-pack/all_in_one_seo_pack.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'aiseop';

    /**
     * Disable plugin for a single request
     *
     * @return void
     */
    public static function getTitle()
    {
        if (!static::integration()) {
            return "";
        }

        ob_start();
        wp_title();
        $title = ob_get_contents();
        ob_end_clean();
        if (empty($title)) {
            $title = "";
        }
        return $title;
    }
}