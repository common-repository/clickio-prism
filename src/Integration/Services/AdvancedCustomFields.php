<?php
/**
 * Advanced Custom fields
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;

/**
 * Actions with Advanced Custom fields
 *
 * @package Integration\Services
 */
final class AdvancedCustomFields extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'advanced-custom-fields/acf.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'acf';

    /**
     * Get all custom fields saved in post
     *
     * @param int $post_id post id
     *
     * @return array
     */
    public static function getPostFields(int $post_id = 0)
    {
        if (!static::integration() || !function_exists('\get_field_objects')) {
            return [];
        }

        $obj_list = \get_field_objects($post_id);
        if (empty($obj_list)) {
            $obj_list = [];
        }
        return $obj_list;
    }
}