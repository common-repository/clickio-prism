<?php

/**
 * AD-inserter
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with ad-inserter
 *
 * @package Integration\Services
 */
final class ADInserter extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'ad-inserter/ad-inserter.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'ad_inserter';

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
            remove_action('wp_head', 'ai_amp_head_hook', 99999);
            remove_action('wp_head', 'ai_amp_css_hook_style',  99999);
            remove_action('wp_head', 'ai_wp_head_hook', 99999);
        } catch (\Exception $e) {
            // do nothing
        }
    }
}