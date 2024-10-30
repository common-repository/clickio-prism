<?php

/**
 * Memberpress
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Options;

/**
 * Integration with Memberpress
 *
 * @package Integration\Services
 */
final class Memberpress extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'memberpress/memberpress.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'memberpress';

    /**
     * Disable rules for a single request
     *
     * @return void
     */
    public static function disablePostRules()
    {
        $force_content = Options::get("force_full_content");
        if (!static::integration() || empty($force_content)) {
            return ;
        }
        add_filter('mepr-pre-run-rule-content', '__return_false', 999);
    }

    /**
     * Check is post protected
     *
     * @param WP_Post $post post object
     *
     * @return bool
     */
    public static function isPostProtected($post): bool
    {
        if (!static::integration()
            || !class_exists("\MeprRule")
            || !method_exists("\MeprRule", 'get_rules')
        ) {
            return false;
        }

        $rules = \MeprRule::get_rules($post);

        return !empty($rules);
    }
}