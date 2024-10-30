<?php
/**
 * Wp Category Permalink
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\SafeAccess;

/**
 * Wp Category Permalink
 *
 * @package Integration\Services
 */
class WpCategoryPermalink extends AbstractIntegrationService implements IIntegrationService
{
    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'wp-category-permalink/wp-category-permalink.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'category_permalink';

    /**
     * Get post main category
     *
     * @param int $post_id post id
     *
     * @return array
     */
    public static function getPostMainCategoryID(int $post_id): int
    {
        if (!static::integration()) {
            return 0;
        }

        $category_key = get_post_meta($post_id, '_category_permalink', true);
        $category_id = SafeAccess::fromArray($category_key, 'category', 'mixed', 0);
        if (empty($category_id)) {
            $category_id = 0;
        }
        return $category_id;
    }
}