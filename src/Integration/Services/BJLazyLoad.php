<?php

/**
 * BJ Lazy Load
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Integration with BJ Lazy Load
 *
 * @package Integration\Services
 */
final class BJLazyLoad extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'bj-lazy-load/bj-lazy-load.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'bjll';

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
            add_filter('bj_lazy_load_run_filter', '__return_false');
        } catch (\Exception $e) {
            // do nothing
        }
    }
}