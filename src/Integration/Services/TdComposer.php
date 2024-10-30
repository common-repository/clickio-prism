<?php

/**
 * Integration with tagDiv Composer
 */

namespace Clickio\Integration\Services;

use Clickio\Integration\Interfaces\IIntegrationService;
use Clickio\Utils\LocationType;
use Clickio\Utils\SafeAccess;
use ReflectionClass;

/**
 * Integration with tagDiv Composer
 *
 * @package Integration\Services
 */
final class TdComposer extends AbstractIntegrationService implements IIntegrationService
{

    /**
     * Plugin unique key
     * Directory name in WP plugins catalog
     * is unique and cannot be modified
     *
     * @var string
     */
    const PLUGIN_ID = 'td-composer/td-composer.php';

    /**
     * Service alias
     *
     * @var string
     */
    protected static $alias = 'td_composer';

    /**
     * Get post primary category
     *
     * @param int $post_id WP_Post->ID
     *
     * @return int|string
     */
    public static function getPrimaryTerm(int $post_id)
    {
        if (!static::integration()) {
            return 0;
        }

        $meta = get_post_meta($post_id, 'td_post_theme_settings', true);
        $meta_field = SafeAccess::fromArray($meta, 'td_primary_cat', 'mixed', 0);
        return $meta_field;
    }

    /**
     * Breadcrumbs
     *
     * @param ?WP_Post $post post instance
     *
     * @return array
     */
    public static function getBreadcrumbs($post): array
    {

        if (!static::integration()
            || !class_exists('\td_page_generator')
            || !class_exists('\td_global')
            || !method_exists('\td_global', 'load_single_post')
        ) {
            return [];
        }
        $breadcrumbs = [];

        $ref_cls = new ReflectionClass(\td_page_generator::class);
        $ref_method = null;
        if (LocationType::isPost() && !empty($post)) {
            if (!method_exists(\td_page_generator::class, 'single_breadcrumbs_array')) {
                return $breadcrumbs;
            }
            \td_global::load_single_post($post);
            $ref_method = $ref_cls->getMethod('single_breadcrumbs_array');
            $ref_method->setAccessible(true);
            $breadcrumbs = $ref_method->invoke(null, '');
            if( is_array($breadcrumbs)){
                array_pop($breadcrumbs);
            } else {
                $breadcrumbs =[];
            }
        } else if (LocationType::isCategory()) {
            if (!method_exists(\td_page_generator::class, 'category_breadcrumbs_array')) {
                return $breadcrumbs;
            }
            $ref_method = $ref_cls->getMethod('category_breadcrumbs_array');
            $current_category_id  = get_query_var('cat');
            $current_category_obj = get_category($current_category_id);
            $ref_method->setAccessible(true);
            $breadcrumbs = $ref_method->invoke(null, $current_category_obj);
        }

        if (empty($breadcrumbs)) {
            return [];
        }

        return $breadcrumbs;
    }

    /**
     * Get post subtitle
     *
     * @param int $post_id post ID
     *
     * @return string
     */
    public static function getSubtitle(int $post_id): string
    {
        $meta = get_post_meta($post_id, 'td_post_theme_settings', true);
        $meta_field = SafeAccess::fromArray($meta, 'td_subtitle', 'mixed', 0);
        if (empty($meta_field)) {
            $meta_field = '';
        }
        return $meta_field;
    }
}