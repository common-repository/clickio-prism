<?php
/**
 * Amp by AMP Project Team
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with Amp plugin
 *
 * @package Integration\Services
 */
final class Amp extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'amp/amp.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'amp';

    /**
     * Disable captcha check
     *
     * @return void
     */
    public static function addListners()
    {
        if (!static::integration()) {
            return ;
        }

        if (!defined('AMP__VERSION')) {
            return ;
        }

        if (version_compare(\AMP__VERSION, '2.1.0') < 0) {
            add_action('_clickio_getid_before_content', [static::class, 'fixAmpLinksBefor21']);
        }
    }

    /**
     * Fixed bug where amp links are not showing in get_id
     *
     * @return void
     */
    public static function fixAmpLinksBefor21()
    {
        if (function_exists('amp_add_frontend_actions')) {
            \amp_add_frontend_actions();
        }
    }
}
