<?php

/**
 * AddFunc Head & Footer Code
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with AddFunc Head & Footer Code
 *
 * @package Integration\Services
 */
final class AddFuncHeaderFooter extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'addfunc-head-footer-code/addfunc-head-footer-code.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'addfunc_header_footer';

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
            remove_action('wp_head', 'aFHFCBuffRec');
            remove_action('wp_print_footer_scripts', 'aFHFCBuffPlay');
        } catch (\Exception $e) {
            // do nothing
        }
    }
}