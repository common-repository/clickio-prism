<?php
/**
 * Category Order and Taxonomy Terms Order
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with Category Order and Taxonomy Terms Order plugin
 *
 * @package Integration\Services
 */
final class TaxonomyOrder extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'taxonomy-terms-order/taxonomy-terms-order.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'taxonomy_order';

    /**
     * Disable captcha check
     *
     * @return void
     */
    public static function disable()
    {
        if (!static::integration()) {
            return ;
        }

        add_action("to/get_terms_orderby/ignore", [static::class, 'filterIgnore'], 10, 3);
    }

    /**
     * Filter to ignore ordering
     * Always returns true
     *
     * @return bool
     */
    public static function filterIgnore()
    {
        return true;
    }
}