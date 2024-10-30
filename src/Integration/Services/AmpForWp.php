<?php
/**
 * Accelerated mobile pages
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\ClickioPlugin;

/**
 * Actions with Accelerated mobile pages
 *
 * @package Integration\Services
 */
final class AmpForWp extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'accelerated-mobile-pages/accelerated-moblie-pages.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'ampforwp';

    /**
     * Disable captcha check
     *
     * @return void
     */
    public static function removeGetIdFromLinks()
    {
        if (!static::integration()) {
            return ;
        }
        $plugin = ClickioPlugin::getInstance();
        add_filter("amp_get_permalink", [$plugin, "omitLinksJunk"]);
        add_filter("ampforwp_modify_rel_canonical", [$plugin, "omitLinksJunk"]);
    }
}