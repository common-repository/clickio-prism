<?php
/**
 * Seo
 */

namespace Clickio\Utils;

use Clickio\Integration\IntegrationServiceFactory;
use Clickio\Integration\Services\GenesisFramework;
use Clickio\Integration\Services\WPSEO;
use Clickio\Utils\LocationType;

/**
 * Seo
 *
 * @package Utils
 */
class Seo
{

    protected static $wp_head = '';

    /**
     * Get title tag
     *
     * @return string
     */
    public static function getTitle(): string
    {
        return wp_get_document_title();
    }

    /**
     * Meta description
     *
     * @return string
     */
    public static function getMetaDescription(): string
    {
        $desc = '';

        if (WPSEO::integration()) {
            $desc = WPSEO::getMetaDescription();
        } else {
            if (GenesisFramework::integration()) {
                global $wp_query;
                $category = $wp_query->get_queried_object();
                if (!empty($category)
                    && is_object($category)
                    && property_exists($category, "term_id")
                    && !empty($category->term_id)
                ) {
                    $desc = GenesisFramework::getTermMeta('description', $category->term_id);
                }
            } else {
                $desc = static::_getDescription();
            }
        }
        return $desc;
    }

    /**
     * Custom meta description
     *
     * @return string
     */
    private static function _getDescription(): string
    {
        global $wp_query;
        $any_obj = $wp_query->get_queried_object();
        $desc = '';
        if (LocationType::isHome()) {
            $desc = get_bloginfo('description');
        } elseif (LocationType::isCategory()) {
            $desc = str_replace("&nbsp;", ' ', $any_obj->category_description);
        } elseif (LocationType::isAuthor()) {
            $user = get_userdata($any_obj->ID);
            $desc = str_replace("&nbsp;", ' ', $user->description);
        } elseif (LocationType::isTag()) {
            $desc = str_replace("&nbsp;", ' ', $any_obj->description);
        } elseif (LocationType::isPost() || LocationType::isPage()) {
            $desc = get_the_excerpt();
        }
        return $desc;
    }

    /**
     * Get raw header
     *
     * @return array
     */
    public static function getHead(): string
    {
        $raw_head = '';
        static::$wp_head = '';
        $service = IntegrationServiceFactory::getService('ad_inserter');
        $service::disable();
        do_action("_clickio_before_raw_head");
        try{
            ob_start([static::class, '_wpHeadBufferingCallback']);
            wp_head();
            while (ob_get_length()) {
                ob_get_clean();
            }

            while (ob_get_level()) {
                ob_end_clean();
            }
            $aiseop = IntegrationServiceFactory::getService('aiseop');
            $title = $aiseop::getTitle();

            $raw_head = str_replace("&#038;", "&", static::$wp_head);

            if (!empty($title)) {
                $raw_head = preg_replace('/<title>.*<\/title>/', "<title>".$title."</title>", $raw_head);
            }

            if (!get_theme_support('title-tag')) {
                ob_start();
                wp_title('');
                $title = ob_get_clean();
                @ob_end_clean();
                $raw_head = sprintf("<title>%s</title>%s", $title, $raw_head);
            }
        } catch (\Exception $err) {
            return '';
        }

        if (empty($raw_head)) {
            $raw_head = '';
        }
        return $raw_head;
    }

    /**
     * Top level category
     *
     * @param int $post_id post id
     *
     * @return object
     */
    public static function getTopLevelCategory(int $post_id = null)
    {
        $cat_list = get_the_category($post_id);
        if (empty($cat_list)) {
            return  [];
        }

        $existed_categories = array_reduce(
            $cat_list,
            function ($prev, $next) {
                $prev[] = $next->term_id;
                return $prev;
            },
            []
        );
        $category = array_shift($cat_list);
        $top_category = $category;
        while ($category->parent) {
            $category = get_category($category->parent, 'OBJECT');
            if (in_array($category->term_id, $existed_categories)) {
                $top_category = $category;
            }
        }
        return $top_category;
    }

    /**
     * Join all buffer chunks together
     *
     * @param string $buffer current buffer chunk
     *
     * @return string
     */
    private static function _wpHeadBufferingCallback($buffer)
    {
        static::$wp_head .= $buffer;
        return '';
    }
}
