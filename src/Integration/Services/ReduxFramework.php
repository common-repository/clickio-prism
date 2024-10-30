<?php

/**
 * Redux Framework
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\SafeAccess;

/**
 * Integration with redux framework
 *
 * @package Integration\Services
 */
final class ReduxFramework extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'redux-framework/redux-framework.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'redux';

    public static function integration(): bool
    {
        $parent_val = parent::integration();
        return class_exists('\Redux') || $parent_val;
    }

    /**
     * Get redux option
     *
     * @param string $opt_name redux option name
     *
     * @return void
     */
    public static function getOption(string $opt_name)
    {
        if (!static::integration()) {
            return ;
        }

        $sections = array_keys(\Redux::$sections);
        if (in_array($opt_name, $sections)) {
            return $GLOBALS[$opt_name];
        }
    }
}